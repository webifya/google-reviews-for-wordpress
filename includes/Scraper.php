<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }

/** Public-page prototype. Administrator opt-in is not a Google storage license. */
final class ScraperSource implements SourceAdapter {
    public function label(): string { return __('Google Maps browser collector · experimental', 'google-reviews-for-wordpress'); }
    public function supports_sync(): bool { return true; }
    public function fetch(array $location, ?string $cursor) { return Security::error('Browser jobs use the worker queue, not synchronous HTTP retrieval.'); }
}

final class Scraper {
    private static function listing(array $d): string {
        $saved=Security::maps($d['maps_url']??'');
        if ($saved && Places::id($saved)===$d['place_id'] && wp_parse_url($saved,PHP_URL_HOST)==='www.google.com') { return $saved; }
        return 'https://www.google.com/maps/search/?'.http_build_query(['api'=>1,'query'=>$d['business_name']??$d['place_id'],'query_place_id'=>$d['place_id'],'hl'=>'en'],'','&',PHP_QUERY_RFC3986);
    }
    public static function status(): array {
        $last=(int)get_option('grw_browser_heartbeat',0);
        $local=LocalCollector::status();
        return ['last_seen'=>$last,'online'=>$local['mode']==='local'?$local['ready']:$last>time()-660,'mode'=>'public_page_prototype','local'=>$local];
    }
    public static function connection(array $location,int $count): array {
        $d=json_decode($location['data'],true)?:[]; $worker=self::status(); $connected=!empty($location['last_success'])&&$count>0;
        $scheduled=Sources::eligible($location)&&!empty($d['frequency'])&&!empty($location['next_sync']);
        $error=get_option('grw_sync_error_'.$location['id'],[]); if ($error) { $error['message']=Sources::error_message($error['code']??''); }
        $status=match ($location['status']) {
            'browser_queued'=>'Waiting for browser worker',
            'browser_running'=>'Browser collection running',
            'sync_failed'=>$connected?'Browser collection stopped · stored reviews retained':'Browser collection stopped · retry required',
            default=>$connected?($scheduled?($worker['online']?'Browser collection connected · scheduled':'Schedule enabled · browser worker offline'):'Browser collection connected · manual checks'):'Browser collection not yet tested',
        };
        return ['capabilities'=>Sources::capabilities('google_scraper'),'connected'=>$connected,'accessible_count'=>$count,'storage_label'=>'Experimental public-page collection','daily_active'=>$connected&&$scheduled&&$worker['online']&&($d['frequency']??0)===86400,'scheduled_active'=>$scheduled,'frequency'=>$d['frequency']??259200,'eligible_daily'=>Sources::eligible($location),'status'=>$status,'browser'=>$worker,'last_validation'=>$location['last_success'],'last_error'=>$error,'last_counts'=>get_option('grw_sync_counts_'.$location['id'],[])];
    }
    public static function queue(array $location) {
        global $wpdb; $d=json_decode($location['data'],true)?:[]; $id=(int)$location['id'];
        if (($d['provider']??'')!=='google_scraper' || empty($d['scraper_enabled']) || empty($d['active']) || empty($d['place_id'])) { return Security::error('Select the browser collector, supply a Place ID, and enable experimental collection.'); }
        $runtime=LocalCollector::status();
        if ($runtime['mode']==='local' && !$runtime['ready']) { return Security::error($runtime['message'],503); }
        $key='grw_browser_job_'.$id; $job=get_option($key,[]);
        if ($job && ($job['expires']??0)>time() && ($job['binding']??'')===Sources::binding($location)) { LocalCollector::schedule(); return ['queued'=>true,'id'=>$id]; }
        $new=['state'=>'queued','binding'=>Sources::binding($location),'expires'=>time()+DAY_IN_SECONDS];
        if ($job) { delete_option($key); }
        if (!add_option($key,$new,'','no')) { return Security::error('A browser collection job is already queued.',409); }
        $wpdb->update($wpdb->prefix.'grw_locations',['status'=>'browser_queued','next_sync'=>0],['id'=>$id]);
        LocalCollector::schedule();
        return ['queued'=>true,'id'=>$id,'worker_online'=>self::status()['online']];
    }
    public static function expire(): void {
        global $wpdb;
        foreach (Locations::all() as $l) {
            $job=get_option('grw_browser_job_'.$l['id'],[]);
            if ($job && ($job['expires']??0)<=time()) {
                delete_option('grw_browser_job_'.$l['id']);
                delete_option('grw_browser_claim_'.$l['id']); delete_option('grw_browser_result_'.$l['id']);
                $wpdb->update($wpdb->prefix.'grw_locations',['status'=>'sync_failed','next_sync'=>0],['id'=>$l['id']]);
                update_option('grw_sync_error_'.$l['id'],['code'=>'browser_timeout','at'=>current_time('mysql',true)],false);
            }
        }
    }
    public static function handle(array $in) {
        global $wpdb;
        if (($in['action']??'status')==='status') { return self::status(); }
        if (($in['action']??'')==='claim') {
            if (($in['transport']??'')!=='local') { update_option('grw_browser_heartbeat',time(),false); } self::expire();
            foreach (Locations::all() as $l) {
                $id=(int)$l['id']; $key='grw_browser_job_'.$id; $job=get_option($key,[]); $d=json_decode($l['data'],true)?:[];
                if (!$job || ($job['state']??'')!=='queued' || !Sources::eligible($l) || ($d['provider']??'')!=='google_scraper') { continue; }
                if (($job['binding']??'')!==Sources::binding($l)) { delete_option($key); continue; }
                if (!add_option('grw_browser_claim_'.$id,time(),'','no')) { continue; }
                try {
                    $job=get_option($key,[]); if (($job['state']??'')!=='queued') { continue; }
                    $lease=bin2hex(random_bytes(32)); $job['lease_hash']=hash('sha256',$lease); $job['state']='running'; $job['expires']=time()+600; update_option($key,$job,false);
                    $wpdb->update($wpdb->prefix.'grw_locations',['status'=>'browser_running','last_attempt'=>current_time('mysql',true)],['id'=>$id]);
                    return ['job'=>['id'=>$id,'lease'=>$lease,'place_id'=>$d['place_id'],'business_name'=>$d['business_name']?:$l['name'],'url'=>self::listing($d),'maximum'=>500]];
                } finally { delete_option('grw_browser_claim_'.$id); }
            }
            return ['job'=>null];
        }
        if (($in['action']??'')!=='result') { return Security::error('Unknown browser action'); }
        $id=absint($in['id']??0); $l=Locations::get($id); $key='grw_browser_job_'.$id; $job=get_option($key,[]);
        if (!$l || ($job['state']??'')!=='running' || ($job['expires']??0)<=time() || !hash_equals($job['lease_hash']??'',hash('sha256',(string)($in['lease']??''))) || ($job['binding']??'')!==Sources::binding($l) || !Sources::eligible($l)) { return Security::error('Expired, changed, or invalid browser job',409); }
        if (!add_option('grw_browser_result_'.$id,time(),'','no')) { return Security::error('Browser result already processing',409); }
        try {
            $current=get_option($key,[]);
            if (($current['state']??'')!=='running' || !hash_equals($current['lease_hash']??'',hash('sha256',(string)($in['lease']??'')))) { return Security::error('Browser result already completed',409); }
            $d=json_decode($l['data'],true)?:[];
            if (($in['place_id']??'')!==$d['place_id']) { return Security::error('Browser business identity mismatch',409); }
            $reason=sanitize_key($in['reason']??'');
            if (!in_array($reason,['all_visible','limited_view','login_required','limit_reached','stalled','captcha','access_denied','layout_changed','network_error','identity_mismatch','runtime_error'],true)) { return Security::error('Unknown collection outcome'); }
            $rows=$in['rows']??[];
            if (!is_array($rows) || !array_is_list($rows) || count($rows)>500 || strlen(wp_json_encode($rows))>2*1024*1024) { return Security::error('Submit at most 500 bounded review rows'); }
            $bad=in_array($reason,['captcha','access_denied','layout_changed','network_error','identity_mismatch','runtime_error'],true) || !$rows;
            if ($bad) {
                $retry=in_array($reason,['network_error','layout_changed'],true)&&!empty($d['frequency'])?time()+$d['frequency']:0;
                $wpdb->update($wpdb->prefix.'grw_locations',['status'=>'sync_failed','next_sync'=>$retry,'failures'=>(int)$l['failures']+1],['id'=>$id]);
                update_option('grw_sync_error_'.$id,['code'=>'browser_'.$reason,'at'=>current_time('mysql',true)],false); delete_option($key);
                Sync::log($id,'Browser collection stopped: '.$reason.'; previous reviews preserved.');
                return ['saved'=>0,'stopped'=>true,'reason'=>$reason];
            }
            $provenance=['provider_id'=>'google_scraper','source_binding'=>Sources::binding($l)]; $clean=[]; $seen=[];
            foreach ($rows as $row) {
                if (!is_array($row) || !is_string($row['external_id']??null) || !preg_match('/^[A-Za-z0-9_-]{5,190}$/',$row['external_id']) || isset($seen[$row['external_id']])) { return Security::error('Invalid or duplicate public review ID'); }
                foreach ($row as $value) { if ($value!==null && !is_scalar($value)) { return Security::error('Public review fields must be scalar'); } }
                if (strlen((string)($row['reviewer']??''))>190 || strlen((string)($row['review_date_label']??''))>190 || strlen((string)($row['content']??''))>65536 || strlen((string)($row['response']??''))>65536) { return Security::error('Oversized public review fields'); }
                $seen[$row['external_id']]=true;
                $row['source_name']='Google Maps · public page'; $row['source_url']=Locations::listing_url($d); $row['permalink']='';
                if (is_wp_error(Reviews::normalize($row,$id,'scraped',true,false,$provenance))) { return Security::error('Invalid browser review row'); } $clean[]=$row;
            }
            $counts=Reviews::import($clean,$id,'scraped',$provenance); if ($counts['errors']) { return Security::error('Browser review database write failed',500); }
            $total=isset($in['advertised_total'])&&is_numeric($in['advertised_total'])&&$in['advertised_total']>=count($rows)?min(10000000,(int)$in['advertised_total']):null;
            $counts+=['retrieved'=>count($rows),'advertised_total'=>$total,'accessible_count'=>count($rows),'complete'=>$reason==='all_visible'&&$total!==null&&$total===count($rows),'reason'=>$reason,'duplicates'=>$counts['unchanged'],'checked_at'=>current_time('mysql',true),'duration_ms'=>max(0,min(600000,(int)($in['duration_ms']??0)))];
            update_option('grw_sync_counts_'.$id,$counts,false); delete_option('grw_sync_error_'.$id); delete_option($key);
            $wpdb->update($wpdb->prefix.'grw_locations',['status'=>'last_sync_successful','last_success'=>current_time('mysql',true),'failures'=>0,'next_sync'=>!empty($d['frequency'])?time()+$d['frequency']:0],['id'=>$id]);
            Sources::clear_scope(); Cache::invalidate(); Sync::log($id,'Browser collection: '.wp_json_encode($counts)); return $counts;
        } finally { delete_option('grw_browser_result_'.$id); }
    }
}
