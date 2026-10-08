<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Analytics {
    public const EVENTS=['impression','unique','interaction','readmore','previous','next','outbound','maps'];
    public static function collect(\WP_REST_Request $request) {
        global $wpdb;
        $settings=get_option('grw_settings',[]);
        if (empty($settings['analytics']) || Security::can() || (!empty($settings['consent_required']) && !$request->get_param('consent'))) { return ['accepted'=>0]; }
        $origin=$request->get_header('origin');
        if ($origin && wp_parse_url($origin,PHP_URL_HOST)!==wp_parse_url(home_url(),PHP_URL_HOST)) { return Security::error('Origin denied',403); }
        $rows=$request->get_param('events'); $token=(string)$request->get_param('session');
        if (!is_array($rows) || count($rows)>30 || !preg_match('/^[a-f0-9]{32}$/',$token)) { return Security::error('Invalid event batch'); }
        // Random per-session token is hashed and expires; no IP, cookie or account identifier is stored.
        $key='grw_rate_'.hash_hmac('sha256',$token,wp_salt()); $bucket=get_transient($key) ?: ['time'=>time(),'count'=>0];
        if ($bucket['count']>=120) { return Security::error('Rate limit',429); }
        $bucket['count']+=count($rows); set_transient($key,$bucket,60);
        $accepted=0; $group=[];
        foreach ($rows as $e) {
            if (!is_array($e)) { continue; }
            $id=absint($e['widget']??0); $type=sanitize_key($e['event']??''); $widget=Widgets::get($id);
            if (!$widget || !in_array($type,self::EVENTS,true)) { continue; }
            $c=Widgets::sanitize(json_decode($widget['config'],true)); if (!$c['analytics']) { continue; }
            if (in_array($type,['impression','unique'],true)) {
                $dedup='grw_seen_'.hash_hmac('sha256',$token.':'.$id.':'.$type,wp_salt());
                if (get_transient($dedup)) { continue; } set_transient($dedup,1,DAY_IN_SECONDS);
            }
            $locations=$c['locations'];
            if (!$locations) { $locations=array_map(fn($l)=>(int)$l['id'],Locations::all()); }
            // Location zero is the canonical widget aggregate. Per-location totals describe widget visibility, not card visibility.
            foreach (array_merge([0],$locations) as $loc) { $k=$id.':'.$loc.':'.$type; $group[$k]=($group[$k]??0)+1; }
            $accepted++;
        }
        foreach ($group as $key=>$n) { [$id,$loc,$type]=explode(':',$key); $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}grw_analytics (day,widget_id,location_id,event,total) VALUES (%s,%d,%d,%s,%d) ON DUPLICATE KEY UPDATE total=total+VALUES(total)",current_time('Y-m-d'),$id,$loc,$type,$n)); }
        return ['accepted'=>$accepted];
    }
    public static function report(string $from,string $to): array {
        global $wpdb;
        foreach ([$from,$to] as $d) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)) { return []; } }
        return $wpdb->get_results($wpdb->prepare("SELECT day,widget_id,location_id,event,SUM(total) total FROM {$wpdb->prefix}grw_analytics WHERE day BETWEEN %s AND %s GROUP BY day,widget_id,location_id,event ORDER BY day",$from,$to),ARRAY_A);
    }
}
