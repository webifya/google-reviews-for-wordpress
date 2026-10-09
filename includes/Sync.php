<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Sync {
    public static function log(int $id,string $message): void { global $wpdb; $wpdb->insert($wpdb->prefix.'grw_logs',['location_id'=>$id,'message'=>substr(sanitize_text_field($message),0,2000),'created_at'=>current_time('mysql',true)]); }
    public static function tick(): void {
        global $wpdb; Scraper::expire();
        $due=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}grw_locations WHERE next_sync>0 AND next_sync<=%d ORDER BY next_sync LIMIT 5",time()));
        foreach ($due as $id) { $location=Locations::get((int)$id); if ($location && !Sources::eligible($location)) { $wpdb->update($wpdb->prefix.'grw_locations',['next_sync'=>0],['id'=>$id]); continue; } self::run((int)$id); }
        $s=get_option('grw_settings',[]); GoogleBusiness::cleanup(); $days=max(1,min(730,absint($s['retention']??90)));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}grw_analytics WHERE day < %s",gmdate('Y-m-d',time()-$days*DAY_IN_SECONDS)));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}grw_logs WHERE created_at < %s",gmdate('Y-m-d H:i:s',time()-90*DAY_IN_SECONDS)));
    }
    public static function run(int $id) {
        global $wpdb; $location=Locations::get($id);
        if (!$location) { return Security::error('Unknown location'); }
        $data=json_decode($location['data'],true); $provider=Sources::all()[$data['provider']]??null;
        if (!Sources::eligible($location)) { return Security::error('Action required: this location has no active authorized synchronization provider.'); }
        if (($data['provider']??'')==='google_scraper') { return Scraper::queue($location); }
        $key='grw_lock_'.$id; $now=time();
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND CAST(option_value AS UNSIGNED)<%d",$key,$now-600)); wp_cache_delete($key,'options');
        if (!add_option($key,$now,'','no')) { return Security::error('Synchronization already running',409); }
        $table=$wpdb->prefix.'grw_locations'; $started=microtime(true); $binding=Sources::binding($location);
        $cursor=get_option('grw_cursor_'.$id,null); $progress=get_option('grw_sync_progress_'.$id,[]);
        if ($cursor===null || ($progress['binding']??'')!==$binding) { $cursor=null; $progress=['binding'=>$binding,'counts'=>['added'=>0,'updated'=>0,'unchanged'=>0,'retrieved'=>0,'pages'=>0,'removed'=>0,'expired'=>0],'seen'=>[],'duration_ms'=>0]; delete_option('grw_cursor_'.$id); }
        $counts=$progress['counts']; $seen=$progress['seen'];
        try {
            $wpdb->update($table,['status'=>'syncing','last_attempt'=>current_time('mysql',true)],['id'=>$id]);
            $complete=false;
            for ($page=0;$page<5;$page++) {
                $batch=$provider->fetch($location,$cursor);
                if (is_wp_error($batch)) { throw new \RuntimeException($batch->get_error_message()); }
                if (!is_array($batch) || !isset($batch['reviews']) || !is_array($batch['reviews']) || count($batch['reviews'])>500) { throw new \RuntimeException('Invalid provider batch (maximum 500 rows per page)'); }
                $next=$batch['cursor']??null;
                if ($next!==null && (!is_string($next) || strlen($next)>2048 || $next===$cursor || in_array(hash('sha256',$next),$seen,true))) { throw new \RuntimeException('Invalid pagination cursor'); }
                $type=Sources::capabilities($data['provider'])['storage']==='licensed_permanent'?'licensed':'adapter';
                $provenance=$type==='licensed'?['provider_id'=>$data['provider'],'source_binding'=>$binding,'license_reference'=>Sources::capabilities($data['provider'])['license_reference']]:[];
                // Validate the entire page before writing; stable IDs prevent edited text becoming duplicate records.
                foreach ($batch['reviews'] as $row) {
                    if (!is_array($row) || ($type==='licensed' && empty($row['external_id']) && empty($row['review_id'])) || is_wp_error(Reviews::normalize($row,$id,$type,true,$type==='licensed',$provenance))) { throw new \RuntimeException('Invalid provider row'); }
                }
                $removed=$batch['removed_ids']??[];
                if (!is_array($removed) || count($removed)>500 || ($removed && ($type!=='licensed' || ($provider->policy()['deletions']??false)!==true))) { throw new \RuntimeException('Deletion signals require a documented provider contract'); }
                foreach ($removed as $external) { if (!is_string($external) || !$external || strlen($external)>190 || sanitize_text_field($external)!==$external) { throw new \RuntimeException('Invalid deletion identity'); } }
                $r=Reviews::import($batch['reviews'],$id,$type,$provenance);
                if ($r['errors']) { throw new \RuntimeException('Provider returned invalid review rows'); }
                foreach (['added','updated','unchanged'] as $k) { $counts[$k]+=$r[$k]; }
                if ($removed) {
                    $sql="DELETE FROM {$wpdb->prefix}grw_reviews WHERE location_id=%d AND source_type='licensed' AND provider_id=%s AND source_binding=%s AND external_id IN (".implode(',',array_fill(0,count($removed),'%s')).')';
                    $deleted=$wpdb->query($wpdb->prepare($sql,$id,$data['provider'],$binding,...$removed));
                    if ($deleted===false) { throw new \RuntimeException('Database deletion failed'); }
                    $counts['removed']=($counts['removed']??0)+$deleted; if ($deleted) { Cache::invalidate(); }
                }
                $counts['retrieved']+=count($batch['reviews']); $counts['pages']++;
                $cursor=$next;
                if ($cursor===null) { $complete=true; delete_option('grw_cursor_'.$id); delete_option('grw_sync_progress_'.$id); break; }
                $seen[]=hash('sha256',$cursor); $seen=array_slice($seen,-100);
                update_option('grw_sync_progress_'.$id,['binding'=>$binding,'counts'=>$counts,'seen'=>$seen,'duration_ms'=>$progress['duration_ms']+(int)round((microtime(true)-$started)*1000)],false);
                update_option('grw_cursor_'.$id,$cursor,false);
            }
            if ($complete && Sources::capabilities($data['provider'])['storage']==='licensed_permanent' && !$counts['retrieved'] && empty($counts['removed'])) { throw new \RuntimeException('Empty licensed collection cannot validate a connection'); }
            $wpdb->update($table,['status'=>$complete?'last_sync_successful':'syncing','last_success'=>$complete?current_time('mysql',true):$location['last_success'],'failures'=>0,'next_sync'=>$complete?(!empty($data['frequency'])?time()+$data['frequency']:0):time()+3600],['id'=>$id]);
            if ($complete && Sources::capabilities($data['provider'])['storage']==='licensed_permanent') { $grants=get_option('grw_license_grants',[]); $reference=Sources::capabilities($data['provider'])['license_reference']; $grants[$id][$data['provider']]=['reference'=>$reference,'binding'=>$binding,'validated_at'=>current_time('mysql',true)]; update_option('grw_license_grants',$grants,false); }
            $counts['duration_ms']=$progress['duration_ms']+(int)round((microtime(true)-$started)*1000); $counts['complete']=$complete; $counts['duplicates']=$counts['unchanged']; $counts['removed']=$counts['removed']??0; $counts['expired']=0;
            update_option('grw_sync_counts_'.$id,$counts,false);
            delete_option('grw_sync_error_'.$id); self::log($id,wp_json_encode($counts)); return $counts;
        } catch (\Throwable $e) {
            $code=is_wp_error($batch??null)?$batch->get_error_code():'invalid_provider_response';
            $reason=in_array($code,['grw_error','provider_auth','provider_rate_limit','provider_quota','provider_network','provider_identity','provider_license','invalid_provider_response'],true)?$code:'provider_error';
            $failures=(int)$location['failures']+1;
            $wpdb->update($table,['status'=>'sync_failed','failures'=>$failures,'next_sync'=>!empty($data['frequency'])?time()+($failures<=3?min(21600,900*(2**($failures-1))):$data['frequency']):0],['id'=>$id]);
            update_option('grw_sync_error_'.$id,['code'=>$reason,'at'=>current_time('mysql',true)],false);
            self::log($id,'Sync failed ('.$reason.'); previous data preserved.'); return Security::error(Sources::error_message($reason));
        } finally { delete_option($key); }
    }
}
