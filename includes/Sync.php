<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Sync {
    public static function log(int $id,string $message): void { global $wpdb; $wpdb->insert($wpdb->prefix.'grw_logs',['location_id'=>$id,'message'=>substr(sanitize_text_field($message),0,2000),'created_at'=>current_time('mysql',true)]); }
    public static function tick(): void {
        global $wpdb;
        $due=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}grw_locations WHERE next_sync>0 AND next_sync<=%d ORDER BY next_sync LIMIT 5",time()));
        foreach ($due as $id) { self::run((int)$id); }
        $s=get_option('grw_settings',[]); $days=max(1,min(730,absint($s['retention']??90)));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}grw_analytics WHERE day < %s",gmdate('Y-m-d',time()-$days*DAY_IN_SECONDS)));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}grw_logs WHERE created_at < %s",gmdate('Y-m-d H:i:s',time()-90*DAY_IN_SECONDS)));
    }
    public static function run(int $id) {
        global $wpdb; $location=Locations::get($id);
        if (!$location) { return Security::error('Unknown location'); }
        $data=json_decode($location['data'],true); $provider=Sources::all()[$data['provider']]??null;
        if (!$provider || !$provider->supports_sync() || empty($data['active'])) { return Security::error('Action required: this location has no active authorized synchronization provider.'); }
        $key='grw_lock_'.$id; $now=time();
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND CAST(option_value AS UNSIGNED)<%d",$key,$now-600)); wp_cache_delete($key,'options');
        if (!add_option($key,$now,'','no')) { return Security::error('Synchronization already running',409); }
        $table=$wpdb->prefix.'grw_locations'; $counts=['added'=>0,'updated'=>0,'unchanged'=>0];
        try {
            $wpdb->update($table,['status'=>'syncing','last_attempt'=>current_time('mysql',true)],['id'=>$id]);
            $cursor=get_option('grw_cursor_'.$id,null); $complete=false;
            for ($page=0;$page<5;$page++) {
                $batch=$provider->fetch($location,$cursor);
                if (is_wp_error($batch)) { throw new \RuntimeException($batch->get_error_message()); }
                if (!is_array($batch) || !isset($batch['reviews']) || !is_array($batch['reviews']) || count($batch['reviews'])>500) { throw new \RuntimeException('Invalid provider batch (maximum 500 rows per page)'); }
                $r=Reviews::import($batch['reviews'],$id,'adapter');
                if ($r['errors']) { throw new \RuntimeException('Provider returned invalid review rows'); }
                foreach ($counts as $k=>$v) { $counts[$k]+=$r[$k]; }
                $next=$batch['cursor']??null;
                if ($next!==null && (!is_string($next) || strlen($next)>2048 || $next===$cursor)) { throw new \RuntimeException('Invalid pagination cursor'); }
                $cursor=$next;
                if ($cursor===null) { $complete=true; delete_option('grw_cursor_'.$id); break; }
                update_option('grw_cursor_'.$id,$cursor,false);
            }
            $wpdb->update($table,['status'=>$complete?'last_sync_successful':'syncing','last_success'=>$complete?current_time('mysql',true):$location['last_success'],'failures'=>0,'next_sync'=>$complete?(!empty($data['frequency'])?time()+$data['frequency']:0):time()+3600],['id'=>$id]);
            self::log($id,wp_json_encode($counts)); return $counts;
        } catch (\Throwable $e) {
            $failures=(int)$location['failures']+1;
            $wpdb->update($table,['status'=>'sync_failed','failures'=>$failures,'next_sync'=>!empty($data['frequency'])?time()+($failures<=3?min(21600,900*(2**($failures-1))):$data['frequency']):0],['id'=>$id]);
            self::log($id,'Sync failed; previous data preserved.'); return Security::error('Synchronization failed; see provider configuration.');
        } finally { delete_option($key); }
    }
}
