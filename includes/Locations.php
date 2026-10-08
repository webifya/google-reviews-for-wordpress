<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Locations {
    public static function all(): array { global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_locations ORDER BY id DESC", ARRAY_A); }
    public static function get(int $id): ?array { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}grw_locations WHERE id=%d", $id), ARRAY_A); }
    public static function save(array $input) {
        global $wpdb;
        $id = absint($input['id'] ?? 0); $old = $id ? self::get($id) : null;
        if ($id && !$old) { return Security::error(__('Location not found.', 'google-reviews-for-wordpress'),404); }
        $name = sanitize_text_field($input['name'] ?? '');
        if (!$name) { return Security::error(__('Location name is required.', 'google-reviews-for-wordpress')); }
        $provider = sanitize_key($input['provider'] ?? 'import');
        if (!isset(Sources::all()[$provider])) { return Security::error(__('Unknown provider.', 'google-reviews-for-wordpress')); }
        $data = ['provider'=>$provider,'active'=>!empty($input['active']),'frequency'=>in_array((int)($input['frequency']??86400),[0,43200,86400,172800,604800],true)?(int)($input['frequency']??86400):86400];
        foreach (['business_name','address','place_id','cid'] as $key) { $data[$key] = sanitize_text_field($input[$key] ?? ''); }
        if ($data['place_id'] && !preg_match('/^[A-Za-z0-9_-]{5,200}$/', $data['place_id'])) { return Security::error(__('Invalid Place ID.', 'google-reviews-for-wordpress')); }
        foreach (['website','logo'] as $key) { $data[$key] = Security::url($input[$key]??''); }
        $data['attachment_id'] = absint($input['attachment_id']??0);
        $data['authorized'] = !empty($input['authorized']);
        if ($provider==='local_json' && (!$data['authorized'] || !$data['attachment_id'])) { return Security::error(__('Select a JSON media attachment and confirm redistribution rights.', 'google-reviews-for-wordpress')); }
        $data = apply_filters('grw_location_config', $data, $input, $old);
        $data['maps_url'] = Security::maps($input['maps_url']??''); $data['embed_url'] = Security::embed($input['embed_url']??'');
        if (!empty($input['maps_url']) && !$data['maps_url']) { return Security::error(__('Use a supported HTTPS Google Maps URL.', 'google-reviews-for-wordpress')); }
        if (!empty($input['embed_url']) && !$data['embed_url']) { return Security::error(__('Paste the iframe src from Google Maps Share → Embed a map.', 'google-reviews-for-wordpress')); }
        $adapter = Sources::all()[$provider];
        $status = $adapter->supports_sync() ? 'action_required' : ($provider === 'embed' ? 'unsupported_source' : 'manual_import_only');
        $row=['name'=>$name,'active'=>$data['active']?1:0,'data'=>wp_json_encode($data),'status'=>$status,'next_sync'=>$adapter->supports_sync() && $data['active'] && $data['frequency'] ? time()+$data['frequency']:0];
        $ok = $id ? $wpdb->update($wpdb->prefix.'grw_locations',$row,['id'=>$id]) : $wpdb->insert($wpdb->prefix.'grw_locations',$row);
        return $ok === false ? Security::error(__('Could not save location.', 'google-reviews-for-wordpress'),500) : ($id ?: (int)$wpdb->insert_id);
    }
}
