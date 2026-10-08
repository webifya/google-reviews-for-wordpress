<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Locations {
    public static function all(): array { global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_locations ORDER BY id DESC", ARRAY_A); }
    public static function get(int $id): ?array { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}grw_locations WHERE id=%d", $id), ARRAY_A); }
    public static function save(array $input) {
        global $wpdb;
        foreach ($input as $value) { if (!is_scalar($value) && $value !== null) { return Security::error('Location fields must contain scalar values'); } }
        $id = absint($input['id'] ?? 0); $old = $id ? self::get($id) : null;
        if ($id && !$old) { return Security::error(__('Location not found.', 'google-reviews-for-wordpress'),404); }
        $previous=$old?json_decode($old['data'],true):[];
        $input=array_merge($previous?:[], $old?['name'=>$old['name']]:[], $input);
        $name = sanitize_text_field($input['name'] ?? ($input['business_name']??''));
        if (!$name) { return Security::error(__('Location name is required.', 'google-reviews-for-wordpress')); }
        $provider = sanitize_key($input['provider'] ?? 'public');
        if (!isset(Sources::all()[$provider])) { return Security::error(__('Unknown provider.', 'google-reviews-for-wordpress')); }
        $data = ['provider'=>$provider,'active'=>!array_key_exists('active',$input)||!empty($input['active']),'frequency'=>in_array((int)($input['frequency']??86400),[0,43200,86400,172800,604800],true)?(int)($input['frequency']??86400):86400];
        foreach (['business_name','address','country','place_id','cid','google_parent'] as $key) { $data[$key] = sanitize_text_field($input[$key] ?? ''); }
        if ($provider==='google_business' && (!preg_match('/^accounts\/\d+\/locations\/\d+$/',$data['google_parent']) || empty($input['identity_confirmed']))) { return Security::error('Select your owned business and confirm its identity'); }
        $data['identity_confirmed']=!empty($input['identity_confirmed']);
        if ($data['cid'] && !preg_match('/^\d{1,24}$/',$data['cid'])) { return Security::error(__('CID must be numeric.', 'google-reviews-for-wordpress')); }
        if ($data['place_id'] && !preg_match('/^[A-Za-z0-9_-]{5,200}$/', $data['place_id'])) { return Security::error(__('Invalid Place ID.', 'google-reviews-for-wordpress')); }
        foreach (['website','logo'] as $key) { $data[$key] = Security::url($input[$key]??''); }
        if ($provider==='google_places' && !$data['place_id']) { return Security::error('Google Places requires a Place ID.'); }
        $data['attachment_id'] = absint($input['attachment_id']??0);
        $data['authorized'] = !empty($input['authorized']);
        if ($provider==='local_json' && (!$data['authorized'] || !$data['attachment_id'])) { return Security::error(__('Select a JSON media attachment and confirm redistribution rights.', 'google-reviews-for-wordpress')); }
        $data = apply_filters('grw_location_config', array_merge($previous?:[],$data), $input, $old);
        $data['reviews_url']=Security::maps($input['reviews_url']??'');
        if (!empty($input['reviews_url']) && !$data['reviews_url']) { return Security::error('Use a supported HTTPS Google Maps listing or reviews URL.'); }
        $data['listing_confirmed']=!empty($input['listing_confirmed']);
        if (strlen((string)($input['maps_url']??''))>2048) { return Security::error('Google Maps URL: use a link of at most 2048 characters.'); }
        $data['maps_url'] = Security::maps($input['maps_url']??''); $data['embed_url'] = Security::embed($input['embed_url']??'');
        if (!empty($input['maps_url']) && !$data['maps_url']) { return Security::error(__('Google Maps URL: use a supported HTTPS Google Maps link.', 'google-reviews-for-wordpress')); }
        if (!empty($input['embed_url']) && !$data['embed_url']) { return Security::error(__('Official Map Embed: paste the code or src from Google Maps Share → Embed a map.', 'google-reviews-for-wordpress')); }
        $adapter = Sources::all()[$provider];
        $unchanged=$old && $previous===$data;
        $data['connection_mode']=$provider==='google_business'?'owner':'public';
        $data['listing_status']=$data['maps_url']?'linked':($data['business_name']?'details_saved':'not_linked');
        $data['map_status']=$data['embed_url']?'configured':'not_configured';
        $data['review_source_status']=$adapter->supports_sync()?'configured':($provider==='public'||$provider==='embed'?'unavailable':'manual_import');
        $status = $adapter->supports_sync() ? 'action_required' : ($provider === 'embed' ? 'unsupported_source' : 'manual_import_only');
        $can_schedule=$adapter->supports_sync() && $provider!=='google_business';
        $row=['name'=>$name,'active'=>$data['active']?1:0,'data'=>wp_json_encode($data),'status'=>$status,'next_sync'=>$can_schedule && $data['active'] && $data['frequency'] ? time()+$data['frequency']:0];
        if ($unchanged || ($old && array_intersect_key($previous,array_flip(['provider','active','frequency','attachment_id','authorized','google_parent']))===array_intersect_key($data,array_flip(['provider','active','frequency','attachment_id','authorized','google_parent'])))) { $row['status']=$old['status']; $row['next_sync']=$old['next_sync']; }
        if ($old && array_intersect_key($previous,array_flip(['provider','attachment_id','google_parent']))!==array_intersect_key($data,array_flip(['provider','attachment_id','google_parent']))) { delete_option('grw_cursor_'.$id); }
        $ok = $id ? $wpdb->update($wpdb->prefix.'grw_locations',$row,['id'=>$id]) : $wpdb->insert($wpdb->prefix.'grw_locations',$row);
         $saved_id=$id ?: (int)$wpdb->insert_id;
        if ($old && (($previous['place_id']??'')!==$data['place_id'] || ($previous['provider']??'')!==$provider)) { delete_option('grw_places_validation_'.$id); }
        Cache::invalidate();
        return $ok === false ? Security::error(__('Could not save location.', 'google-reviews-for-wordpress'),500) :  $saved_id;
    }
    /** Presentation is derived for legacy rows without rewriting their stored configuration. */
    public static function presentation(array $location): array {
        $d=json_decode($location['data'],true)?:[]; $adapter=Sources::all()[$d['provider']??'import']??null;
        $sync=$adapter && $adapter->supports_sync();
        $labels=['sync_failed'=>'Connection Requires Attention','action_required'=>'Review Source Requires Validation','last_sync_successful'=>'Review Sync Available','synced'=>'Review Sync Available','syncing'=>'Review Sync in Progress'];
        return ['listing'=>Security::maps($d['maps_url']??'')?'Public Listing Linked':(!empty($d['business_name'])?'Business Details Saved':'Listing Link Needed'),
            'map'=>Security::embed($d['embed_url']??'')?'Map Embed Ready':'Map Embed Not Added',
            'source'=>$adapter?$adapter->label():'Review Source Requires Attention',
            'sync'=>$sync?($labels[$location['status']]??'Review Sync Available'):'Review Sync Unavailable',
            'scheduled'=>$sync && !empty($location['next_sync']), 'owner'=>($d['provider']??'')==='google_business'];
    }
    public static function listing_url(array $data): string {
        $saved=Security::maps($data['maps_url']??''); if ($saved) { return $saved; }
        if (!empty($data['cid']) && preg_match('/^\d{1,24}$/',$data['cid'])) { return 'https://www.google.com/maps?cid='.$data['cid']; }
        $query=trim(($data['business_name']??'').' '.($data['address']??''));
        if (!$query) { return ''; }
        return 'https://www.google.com/maps/search/?'.http_build_query(['api'=>1,'query'=>$query]+(!empty($data['place_id'])?['query_place_id'=>$data['place_id']]:[]),'','&',PHP_QUERY_RFC3986);
    }
}
