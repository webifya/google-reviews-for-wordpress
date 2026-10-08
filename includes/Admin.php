<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Admin {
    public static function menu(): void {
        add_menu_page(__('Google Reviews','google-reviews-for-wordpress'),__('Google Reviews','google-reviews-for-wordpress'),'manage_options','grw',[self::class,'page'],'dashicons-star-filled',58);
        foreach (['dashboard'=>'Dashboard','locations'=>'Locations','reviews'=>'All Reviews','widgets'=>'Review Widgets','analytics'=>'Analytics','sync'=>'Synchronization','settings'=>'Settings','tools'=>'Tools & Diagnostics'] as $slug=>$title) { add_submenu_page('grw',__($title,'google-reviews-for-wordpress'),__($title,'google-reviews-for-wordpress'),'manage_options',$slug==='dashboard'?'grw':'grw-'.$slug,[self::class,'page']); }
    }
    public static function assets(string $hook): void {
        if (!str_contains($hook,'grw')) { return; }
        wp_enqueue_style('grw-admin',plugins_url('assets/admin.css',GRW_FILE),[],GRW_VERSION);
        Renderer::assets();
        wp_enqueue_script('grw-admin',plugins_url('assets/admin.js',GRW_FILE),['wp-i18n'],GRW_VERSION,true);
        wp_set_script_translations('grw-admin','google-reviews-for-wordpress',dirname(GRW_FILE).'/languages');
        wp_localize_script('grw-admin','GRW_ADMIN',['endpoint'=>rest_url('grw/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'page'=>sanitize_key($_GET['page']??'grw'),'defaults'=>Widgets::defaults(),'presets'=>Widgets::presets(),'providers'=>array_map(fn($p)=>['label'=>$p->label(),'sync'=>$p->supports_sync()],Sources::all()),'strings'=>['save'=>__('Save','google-reviews-for-wordpress'),'error'=>__('Request failed','google-reviews-for-wordpress')]]);
    }
    public static function page(): void { if (!Security::can()) { return; } echo '<div class="wrap grw-admin"><h1>'.esc_html__('Google Reviews','google-reviews-for-wordpress').'</h1><div id="grw-admin-app"></div><noscript>'.esc_html__('Enable JavaScript to use the visual editor.','google-reviews-for-wordpress').'</noscript></div>'; }
    public static function settings(array $s): array { return ['analytics'=>filter_var(is_scalar($s['analytics']??null)?$s['analytics']:false,FILTER_VALIDATE_BOOLEAN),'consent_required'=>filter_var(is_scalar($s['consent_required']??null)?$s['consent_required']:false,FILTER_VALIDATE_BOOLEAN),'delete_data'=>filter_var(is_scalar($s['delete_data']??null)?$s['delete_data']:false,FILTER_VALIDATE_BOOLEAN),'retention'=>max(1,min(730,absint(is_scalar($s['retention']??null)?$s['retention']:90)))]; }
    public static function routes(): void {
        foreach (['state','location','widget','preview','import','moderate','manual','sync','google','bulk','settings','tools','export'] as $action) {
            register_rest_route('grw/v1','/'.$action,['methods'=>$action==='state'?'GET':'POST','permission_callback'=>[Security::class,'can'],'callback'=>fn($r)=>self::handle($action,$r)]);
        }
        register_rest_route('grw/v1','/events',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>[Analytics::class,'collect'],'args'=>[]]);
    }
    private static function state(\WP_REST_Request $r): array {
        global $wpdb; $settings=get_option('grw_settings',[]);
        $page=max(1,absint($r->get_param('page')??1));
        $counts=$wpdb->get_results("SELECT location_id,COUNT(*) total FROM {$wpdb->prefix}grw_reviews GROUP BY location_id",OBJECT_K);
        $locations=Locations::all(); foreach ($locations as &$location) { $location['saved_count']=(int)($counts[$location['id']]->total??0); } unset($location);
        $filters=['admin'=>true,'search'=>sanitize_text_field($r->get_param('search')??''),'locations'=>Security::ids($r->get_param('locations')??[]),'visibility'=>sanitize_text_field($r->get_param('visibility')??''),'source'=>sanitize_key($r->get_param('source')??''),'minimum'=>absint($r->get_param('minimum')??0),'sort'=>sanitize_key($r->get_param('sort')??'latest'),'from'=>sanitize_text_field($r->get_param('from')??''),'to'=>sanitize_text_field($r->get_param('to')??'')];
        return ['locations'=>$locations,'widgets'=>Widgets::all(),'reviews'=>Reviews::query($filters,$page,50),'review_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews"),'average'=>$wpdb->get_var("SELECT AVG(rating) FROM {$wpdb->prefix}grw_reviews WHERE published=1"),'settings'=>$settings,'analytics'=>Analytics::report($r->get_param('from')?:wp_date('Y-m-d',time()-29*DAY_IN_SECONDS),$r->get_param('to')?:current_time('Y-m-d')),'logs'=>$wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_logs ORDER BY id DESC LIMIT 50",ARRAY_A),'google'=>GoogleBusiness::status(),'today'=>current_time('Y-m-d'),'diagnostics'=>['next_tick'=>wp_next_scheduled('grw_tick'),'cron_disabled'=>defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON]+(($r->get_param('diagnostics'))?self::diagnostics():[]),'onboarding'=>(bool)get_option('grw_onboarding')];
    }
    public static function diagnostics(): array {
        global $wpdb;
        $tables=[]; foreach (['locations','reviews','widgets','analytics','logs'] as $name) { $table=$wpdb->prefix.'grw_'.$name; $tables[$name]=['exists'=>$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))===$table,'rows'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $table")]; }
        return ['plugin'=>GRW_VERSION,'wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'database'=>$wpdb->db_version(),'cron_disabled'=>defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON,'next_tick'=>wp_next_scheduled('grw_tick'),'tables'=>$tables,'providers'=>array_map(fn($p)=>['label'=>$p->label(),'sync'=>$p->supports_sync()],Sources::all())];
    }
    public static function handle(string $action,\WP_REST_Request $r) {
        global $wpdb; $in=$r->get_json_params() ?: $r->get_params(); if (!is_array($in)) { return Security::error('Expected an object payload'); }
        foreach ($in as $key=>$value) { if (!in_array($key,['config','rows','ids','settings','locations'],true) && $value!==null && !is_scalar($value)) { return Security::error('Invalid field type: '.sanitize_key($key)); } }
        switch ($action) {
            case 'state': return self::state($r);
            case 'location':
                if (($in['action']??'')==='delete') {
                    $id=absint($in['id']??0); if (!Locations::get($id)) { return Security::error('Location not found'); }
                    if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews WHERE location_id=%d",$id))) { return Security::error('Hide this location or remove/reassign its saved reviews before deletion'); }
                    $wpdb->delete($wpdb->prefix.'grw_locations',['id'=>$id]); delete_option('grw_cursor_'.$id); Cache::invalidate(); GoogleBusiness::cleanup(true); return ['deleted'=>true];
                }
                return Locations::save($in);
            case 'widget':
                if (($in['action']??'')==='delete') { $wpdb->delete($wpdb->prefix.'grw_widgets',['id'=>absint($in['id']??0)]); return ['deleted'=>true]; }
                if (!empty($in['duplicate'])) { $w=Widgets::get(absint($in['id']??0)); if (!$w) { return Security::error('Unknown widget'); } $in=['name'=>$w['name'].' copy','config'=>json_decode($w['config'],true)]; }
                return Widgets::save($in);
            case 'preview':
                if (!is_array($in['config']??[])) { return Security::error('Widget config must be an object'); }
                $id=absint($in['id']??0); if ($id && !Widgets::get($id)) { return Security::error('Widget not found'); }
                return ['html'=>Renderer::render($id,$in['config']??[],true),'css'=>plugins_url('assets/frontend.css',GRW_FILE).'?ver='.GRW_VERSION,'js'=>plugins_url('assets/frontend.js',GRW_FILE).'?ver='.GRW_VERSION];
            case 'import':
                if (empty($in['authorized'])) { return Security::error('Confirm permission to republish this data'); }
                if (!isset($in['rows']) || !is_array($in['rows']) || count($in['rows'])>500) { return Security::error('Submit a batch of at most 500 rows'); }
                $result=Reviews::import($in['rows'],absint($in['location']??0)); Sync::log(absint($in['location']??0),'Import: '.wp_json_encode(['added'=>$result['added'],'updated'=>$result['updated'],'errors'=>count($result['errors'])])); return $result;
            case 'manual':
                if (empty($in['authorized'])) { return Security::error('Confirm publishing rights'); }
                $id=absint($in['id']??0);
                if ($id) { $old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}grw_reviews WHERE id=%d",$id),ARRAY_A); if (!$old || $old['source_type']!=='manual') { return Security::error('Only manual testimonials can be edited'); } $in['external_id']=$old['external_id']; if (absint($in['location']??0)!==(int)$old['location_id']) { return Security::error('Keep the original location when editing a testimonial'); } }
                else { $in['external_id']=wp_generate_uuid4(); }
                return Reviews::import([$in],absint($in['location']??0),'manual');
            case 'moderate':
                $act=sanitize_key($in['action']??''); if (!in_array($act,['hide','show','feature','unfeature','delete'],true)) { return Security::error('Invalid moderation action'); }
                return ['success'=>Reviews::moderate(absint($in['id']??0),$act)];
            case 'sync':
                if (!empty($in['all'])) { $out=[]; foreach (Locations::all() as $l) { $d=json_decode($l['data'],true); $p=Sources::all()[$d['provider']]??null; if ($p && $p->supports_sync() && !empty($d['active'])) { $wpdb->update($wpdb->prefix.'grw_locations',['next_sync'=>time()],['id'=>$l['id']]); $out[]=$l['id']; } } return ['queued'=>$out]; }
                return Sync::run(absint($in['id']??0));
            case 'google':
                $ga=sanitize_key($in['action']??'status');
                if ($ga==='configure') { return GoogleBusiness::configure($in); }
                if ($ga==='authorize') { return GoogleBusiness::authorize(); }
                if ($ga==='disconnect') { return GoogleBusiness::disconnect(); }
                $cursor=$in['cursor']??null; if ($cursor!==null && (!is_string($cursor) || strlen($cursor)>2048)) { return Security::error('Invalid Google page cursor'); }
                if ($ga==='accounts') { return GoogleBusiness::accounts($cursor); }
                if ($ga==='locations') { return GoogleBusiness::locations(is_string($in['account']??null)?$in['account']:'',$cursor); }
                if ($ga==='reviews') { return GoogleBusiness::reviews(absint($in['id']??0),$cursor); }
                return GoogleBusiness::status();
            case 'bulk':
                $ids=Security::ids($in['ids']??[]); $act=sanitize_key($in['action']??'');
                if (count($ids)>100 || !$ids) { return Security::error('Select 1–100 saved reviews'); }
                if (in_array($act,['hide','show','feature','unfeature'],true)) { foreach ($ids as $id) { Reviews::moderate($id,$act); } return ['processed'=>count($ids)]; }
                if ($act==='reassign') { $target=absint($in['location']??0); if (!Locations::get($target)) { return Security::error('Unknown target location'); }
                    $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}grw_reviews WHERE id IN (".implode(',',array_fill(0,count($ids),'%d')).")",...$ids),ARRAY_A);
                    foreach ($rows as $row) { if ($row['source_type']!=='manual') { return Security::error('Only manual testimonials can be reassigned; third-party source identity is protected'); } }
                    foreach ($rows as $row) { $data=Reviews::normalize($row,$target,'manual'); if (is_wp_error($data)) { return $data; } if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}grw_reviews WHERE identity=%s AND id<>%d",$data['identity'],$row['id']))) { return Security::error('Reassignment would duplicate a testimonial'); } }
                    foreach ($rows as $row) { $data=Reviews::normalize($row,$target,'manual'); $wpdb->update($wpdb->prefix.'grw_reviews',['location_id'=>$target,'identity'=>$data['identity']],['id'=>$row['id']]); } Cache::invalidate(); Sync::log($target,'Manual testimonial reassignment: '.count($rows)); return ['processed'=>count($rows)];
                }
                return Security::error('Unsupported bulk action');
            case 'settings': update_option('grw_settings',self::settings($in),false); return ['saved'=>true];
            case 'tools':
                if (($in['action']??'')==='resolve_maps') { return Security::resolve_maps(is_string($in['url']??null)?$in['url']:''); }
                if (($in['action']??'')==='test_connection') { $loc=Locations::get(absint($in['id']??0)); if (!$loc) { return Security::error('Unknown location'); } $d=json_decode($loc['data'],true); $provider=Sources::all()[$d['provider']]??null; if (!$provider || !$provider->supports_sync()) { return Security::error('No authorized individual-review retrieval is available for this source.'); } $batch=$provider->fetch($loc,null); return is_wp_error($batch)?$batch:['success'=>true,'first_page_reviews'=>count($batch['reviews']??[])]; }
                elseif (($in['action']??'')==='onboarding_done') { update_option('grw_onboarding',false); }
                elseif (($in['action']??'')==='repair') { Installer::activate(); }
                elseif (($in['action']??'')==='import_settings') { if (!isset($in['settings']) || !is_array($in['settings'])) { return Security::error('Invalid settings'); } update_option('grw_settings',self::settings($in['settings'])); }
                elseif (($in['action']??'')==='clear_cache') { Cache::invalidate(); GoogleBusiness::cleanup(true); }
                elseif (($in['action']??'')==='reset_analytics') { if (($in['confirmation']??'')!=='RESET') { return Security::error('Type RESET to confirm analytics deletion'); } $wpdb->query("DELETE FROM {$wpdb->prefix}grw_analytics"); }
                return self::diagnostics();
            case 'export':
                if (($in['type']??'')==='reviews') { return Reviews::query(['admin'=>true],max(1,absint($in['page']??1)),500); }
                if (($in['type']??'')==='analytics') { return Analytics::report($in['from']??wp_date('Y-m-d',time()-29*DAY_IN_SECONDS),$in['to']??current_time('Y-m-d')); }
                return ['settings'=>get_option('grw_settings',[]),'widgets'=>Widgets::all()];
        }
        return Security::error('Unknown action');
    }
}
