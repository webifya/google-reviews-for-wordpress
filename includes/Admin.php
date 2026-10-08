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
        wp_localize_script('grw-admin','GRW_ADMIN',['endpoint'=>rest_url('grw/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'page'=>sanitize_key($_GET['page']??'grw'),'defaults'=>Widgets::defaults(),'providers'=>array_map(fn($p)=>['label'=>$p->label(),'sync'=>$p->supports_sync()],Sources::all()),'strings'=>['save'=>__('Save','google-reviews-for-wordpress'),'error'=>__('Request failed','google-reviews-for-wordpress')]]);
    }
    public static function page(): void { if (!Security::can()) { return; } echo '<div class="wrap grw-admin"><h1>'.esc_html__('Google Reviews','google-reviews-for-wordpress').'</h1><div id="grw-admin-app"></div><noscript>'.esc_html__('Enable JavaScript to use the visual editor.','google-reviews-for-wordpress').'</noscript></div>'; }
    public static function settings(array $s): array { return ['analytics'=>!empty($s['analytics']),'consent_required'=>!empty($s['consent_required']),'delete_data'=>!empty($s['delete_data']),'retention'=>max(1,min(730,absint($s['retention']??90)))]; }
    public static function routes(): void {
        foreach (['state','location','widget','preview','import','moderate','manual','sync','settings','tools','export'] as $action) {
            register_rest_route('grw/v1','/'.$action,['methods'=>$action==='state'?'GET':'POST','permission_callback'=>[Security::class,'can'],'callback'=>fn($r)=>self::handle($action,$r)]);
        }
        register_rest_route('grw/v1','/events',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>[Analytics::class,'collect'],'args'=>[]]);
    }
    private static function state(\WP_REST_Request $r): array {
        global $wpdb; $settings=get_option('grw_settings',[]);
        $page=max(1,absint($r->get_param('page')??1));
        $filters=['admin'=>true,'search'=>sanitize_text_field($r->get_param('search')??''),'locations'=>Security::ids($r->get_param('locations')??[]),'source'=>sanitize_key($r->get_param('source')??''),'minimum'=>absint($r->get_param('minimum')??0),'sort'=>sanitize_key($r->get_param('sort')??'latest'),'from'=>sanitize_text_field($r->get_param('from')??''),'to'=>sanitize_text_field($r->get_param('to')??'')];
        return ['locations'=>Locations::all(),'widgets'=>Widgets::all(),'reviews'=>Reviews::query($filters,$page,50),'review_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews"),'average'=>$wpdb->get_var("SELECT AVG(rating) FROM {$wpdb->prefix}grw_reviews WHERE published=1"),'settings'=>$settings,'analytics'=>Analytics::report($r->get_param('from')?:gmdate('Y-m-d',time()-29*DAY_IN_SECONDS),$r->get_param('to')?:gmdate('Y-m-d')),'logs'=>$wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_logs ORDER BY id DESC LIMIT 50",ARRAY_A),'diagnostics'=>self::diagnostics(),'onboarding'=>(bool)get_option('grw_onboarding')];
    }
    public static function diagnostics(): array {
        global $wpdb;
        $tables=[]; foreach (['locations','reviews','widgets','analytics','logs'] as $name) { $table=$wpdb->prefix.'grw_'.$name; $tables[$name]=['exists'=>$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))===$table,'rows'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $table")]; }
        return ['plugin'=>GRW_VERSION,'wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'database'=>$wpdb->db_version(),'cron_disabled'=>defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON,'next_tick'=>wp_next_scheduled('grw_tick'),'tables'=>$tables,'providers'=>array_map(fn($p)=>['label'=>$p->label(),'sync'=>$p->supports_sync()],Sources::all())];
    }
    public static function handle(string $action,\WP_REST_Request $r) {
        global $wpdb; $in=$r->get_json_params() ?: $r->get_params(); if (!is_array($in)) { return Security::error('Expected an object payload'); }
        switch ($action) {
            case 'state': return self::state($r);
            case 'location': return Locations::save($in);
            case 'widget':
                if (!empty($in['duplicate'])) { $w=Widgets::get(absint($in['id']??0)); if (!$w) { return Security::error('Unknown widget'); } $in=['name'=>$w['name'].' copy','config'=>json_decode($w['config'],true)]; }
                return Widgets::save($in);
            case 'preview':
                if (!is_array($in['config']??[])) { return Security::error('Widget config must be an object'); }
                $id=absint($in['id']??0); if (!Widgets::get($id)) { return Security::error('Save widget before preview'); }
                return ['html'=>Renderer::render($id,$in['config']??[],true)];
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
            case 'settings': update_option('grw_settings',self::settings($in),false); return ['saved'=>true];
            case 'tools':
                if (($in['action']??'')==='test_connection') { $loc=Locations::get(absint($in['id']??0)); if (!$loc) { return Security::error('Unknown location'); } $d=json_decode($loc['data'],true); $provider=Sources::all()[$d['provider']]??null; if (!$provider || !$provider->supports_sync()) { return Security::error('No authorized individual-review retrieval is available for this source.'); } $batch=$provider->fetch($loc,null); return is_wp_error($batch)?$batch:['success'=>true,'first_page_reviews'=>count($batch['reviews']??[])]; }
                elseif (($in['action']??'')==='onboarding_done') { update_option('grw_onboarding',false); }
                elseif (($in['action']??'')==='repair') { Installer::activate(); }
                elseif (($in['action']??'')==='import_settings') { if (!isset($in['settings']) || !is_array($in['settings'])) { return Security::error('Invalid settings'); } update_option('grw_settings',self::settings($in['settings'])); }
                elseif (($in['action']??'')==='clear_cache') { wp_cache_delete('grw','grw'); }
                return self::diagnostics();
            case 'export':
                if (($in['type']??'')==='reviews') { return Reviews::query(['admin'=>true],max(1,absint($in['page']??1)),500); }
                if (($in['type']??'')==='analytics') { return Analytics::report($in['from']??gmdate('Y-m-d',time()-29*DAY_IN_SECONDS),$in['to']??gmdate('Y-m-d')); }
                return ['settings'=>get_option('grw_settings',[]),'widgets'=>Widgets::all()];
        }
        return Security::error('Unknown action');
    }
}
