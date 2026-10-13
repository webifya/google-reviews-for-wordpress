<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
/** Legacy records are retained; new writes are restricted to the project's Google business. */
final class Locations {
    public static function all(): array { global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_locations ORDER BY id DESC",ARRAY_A); }
    public static function get(int $id): ?array { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}grw_locations WHERE id=%d",$id),ARRAY_A); }
    public static function listing_url(array $d): string { return Security::maps($d['maps_url']??'')?:'https://www.google.com/maps/search/?api=1&query=Google&query_place_id='.rawurlencode(Project::PLACE_ID); }
    public static function save(array $in) {
        global $wpdb;
        foreach ($in as $value) { if (!is_scalar($value) && $value!==null) { return Security::error('Invalid business fields.'); } }
        $id=absint($in['id']??0); $old=$id?self::get($id):null; if ($id&&!$old) { return Security::error('Business not found.',404); }
        $data=array_merge($old?(json_decode($old['data'],true)?:[]):[],$in); unset($data['id'],$data['name']);
        if (($data['provider']??'')!=='google_business' || ($data['place_id']??'')!==Project::PLACE_ID || !preg_match('~^accounts/\d+/locations/\d+$~',$data['google_parent']??'') || empty($data['identity_confirmed'])) { return Security::error('This plugin connects only the verified Menjar Financial profile.'); }
        foreach (['business_name','address','country','google_parent'] as $k) { $data[$k]=sanitize_text_field($data[$k]??''); }
        $data['google_public']=filter_var($data['google_public']??false,FILTER_VALIDATE_BOOLEAN);$data['active']=filter_var($data['active']??true,FILTER_VALIDATE_BOOLEAN);$data['frequency']=0;$data['connection_mode']='manager';$data['maps_url']=Security::maps($data['maps_url']??'');
        $row=['name'=>'Menjar Financial','active'=>$data['active']?1:0,'data'=>wp_json_encode($data),'status'=>'manager_api','next_sync'=>0];
        $ok=$id?$wpdb->update($wpdb->prefix.'grw_locations',$row,['id'=>$id]):$wpdb->insert($wpdb->prefix.'grw_locations',$row);Cache::invalidate();return $ok===false?Security::error('Could not save Menjar Financial.',500):($id?:(int)$wpdb->insert_id);
    }
}
