<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Admin {
    public static function menu(): void {
        add_menu_page(__('Google Reviews','google-reviews-for-wordpress'),__('Google Reviews','google-reviews-for-wordpress'),'manage_options','grw',[self::class,'page'],'dashicons-star-filled',58);
        foreach (['dashboard'=>'Dashboard','locations'=>'Locations','reviews'=>'Reviews','widgets'=>'Widgets','analytics'=>'Analytics','settings'=>'Settings'] as $slug=>$title) { add_submenu_page('grw',__($title,'google-reviews-for-wordpress'),__($title,'google-reviews-for-wordpress'),'manage_options',$slug==='dashboard'?'grw':'grw-'.$slug,[self::class,'page']); }
    }
    public static function secondary_menu(): void { foreach (['sync','integrations','tools'] as $slug) { add_submenu_page(null,'Google Reviews settings','Google Reviews settings','manage_options','grw-'.$slug,[self::class,'page']); } }
    public static function assets(string $hook): void {
        if (!str_contains($hook,'grw')) { return; }
        wp_enqueue_style('grw-admin',plugins_url('assets/admin.css',GRW_FILE),[],GRW_VERSION);
        Renderer::assets();
        wp_enqueue_script('grw-admin',plugins_url('assets/admin.js',GRW_FILE),['wp-i18n'],GRW_VERSION,true);
        wp_set_script_translations('grw-admin','google-reviews-for-wordpress',dirname(GRW_FILE).'/languages');
        wp_localize_script('grw-admin','GRW_ADMIN',['endpoint'=>rest_url('grw/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'user'=>get_current_user_id(),'page'=>sanitize_key($_GET['page']??'grw'),'defaults'=>Widgets::defaults(),'presets'=>Widgets::presets(),'providers'=>Sources::catalog(),'strings'=>['save'=>__('Save','google-reviews-for-wordpress'),'error'=>__('Request failed','google-reviews-for-wordpress')]]);
    }
    public static function page(): void { if (!Security::can()) { return; } echo '<div class="wrap grw-admin"><h1>'.esc_html__('Google Reviews','google-reviews-for-wordpress').'</h1><div id="grw-admin-app"></div><noscript>'.esc_html__('Enable JavaScript to use the visual editor.','google-reviews-for-wordpress').'</noscript></div>'; }
    public static function settings(array $s): array { return ['analytics'=>filter_var(is_scalar($s['analytics']??null)?$s['analytics']:false,FILTER_VALIDATE_BOOLEAN),'consent_required'=>filter_var(is_scalar($s['consent_required']??null)?$s['consent_required']:false,FILTER_VALIDATE_BOOLEAN),'delete_data'=>filter_var(is_scalar($s['delete_data']??null)?$s['delete_data']:false,FILTER_VALIDATE_BOOLEAN),'retention'=>max(1,min(730,absint(is_scalar($s['retention']??null)?$s['retention']:90)))]; }
    public static function routes(): void {
        foreach (['state','collector','scraper','identify','listing_preview','location','widget','preview','import','moderate','manual','sync','google','places','bulk','settings','tools','export'] as $action) {
            register_rest_route('grw/v1','/'.$action,['methods'=>$action==='state'?'GET':'POST','permission_callback'=>[Security::class,'can'],'callback'=>fn($r)=>self::handle($action,$r)]);
        }
        register_rest_route('grw/v1','/live-widget',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>[Places::class,'public_widget']]);
        register_rest_route('grw/v1','/events',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>[Analytics::class,'collect'],'args'=>[]]);
    }
    private static function state(\WP_REST_Request $r): array {
        global $wpdb; $settings=get_option('grw_settings',[]);
        $page=max(1,absint($r->get_param('page')??1));
        $counts=$wpdb->get_results("SELECT location_id,COUNT(*) total,SUM(CASE WHEN published=1 AND source_type NOT IN ('licensed','scraped') THEN 1 ELSE 0 END) displayable FROM {$wpdb->prefix}grw_reviews GROUP BY location_id",OBJECT_K);
        $locations=Locations::all(); $scope=Sources::scope(); $sql="SELECT r.location_id,COUNT(*) total,SUM(CASE WHEN r.published=1 THEN 1 ELSE 0 END) displayable FROM {$wpdb->prefix}grw_reviews r WHERE r.source_type IN ('licensed','scraped') AND ".$scope['where']." GROUP BY r.location_id"; $accessible=$wpdb->get_results($scope['args']?$wpdb->prepare($sql,...$scope['args']):$sql,OBJECT_K); foreach ($locations as &$location) { $location['review_connection']=Sources::connection($location,(int)($accessible[$location['id']]->total??0)); $location['saved_count']=(int)($counts[$location['id']]->total??0); $location['display_count']=(int)($accessible[$location['id']]->displayable??0)+(int)($counts[$location['id']]->displayable??0); $location['presentation']=Locations::presentation($location); $location['listing_url']=Locations::listing_url(json_decode($location['data'],true)?:[]); } unset($location);
        $filters=['admin'=>true,'search'=>sanitize_text_field($r->get_param('search')??''),'locations'=>Security::ids($r->get_param('locations')??[]),'visibility'=>sanitize_text_field($r->get_param('visibility')??''),'source'=>sanitize_key($r->get_param('source')??''),'minimum'=>absint($r->get_param('minimum')??0),'sort'=>sanitize_key($r->get_param('sort')??'latest'),'from'=>sanitize_text_field($r->get_param('from')??''),'to'=>sanitize_text_field($r->get_param('to')??'')];
        $ids=array_map(fn($l)=>(int)$l['id'],array_filter($locations,fn($l)=>in_array($l['review_connection']['capabilities']['storage'],['licensed_permanent','public_browser'],true)&&!empty($l['active'])));
        $requested=Security::ids($r->get_param('locations')??[]); $view_ids=$requested?array_values(array_intersect($ids,$requested)):$ids;
        $connected_filters=array_merge($filters,['source'=>'','locations'=>$view_ids,'connected'=>true]);
        $summary=['count'=>0,'sum'=>0];
        if ($ids) { $scope=Sources::scope($ids); $sql="SELECT COUNT(*) count,COALESCE(SUM(r.rating),0) sum FROM {$wpdb->prefix}grw_reviews r WHERE r.source_type IN ('licensed','scraped') AND ".$scope['where']; $summary=$wpdb->get_row($scope['args']?$wpdb->prepare($sql,...$scope['args']):$sql,ARRAY_A); }

        return ['connected_reviews'=>$view_ids?Reviews::query($connected_filters,$page,50):[],'review_summary'=>$summary,'locations'=>$locations,'widgets'=>Widgets::all(),'reviews'=>Reviews::query($filters,$page,50),'review_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews"),'total_views'=>(int)$wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$wpdb->prefix}grw_analytics WHERE event='impression' AND location_id=0"),'average'=>$wpdb->get_var("SELECT AVG(rating) FROM {$wpdb->prefix}grw_reviews WHERE published=1"),'settings'=>$settings,'analytics'=>Analytics::report($r->get_param('from')?:wp_date('Y-m-d',time()-29*DAY_IN_SECONDS),$r->get_param('to')?:current_time('Y-m-d')),'logs'=>$wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_logs ORDER BY id DESC LIMIT 50",ARRAY_A),'scraper'=>Scraper::status(),'places'=>Places::status(),'google'=>GoogleBusiness::status(),'today'=>current_time('Y-m-d'),'diagnostics'=>['plugin'=>GRW_VERSION,'next_tick'=>wp_next_scheduled('grw_tick'),'cron_disabled'=>defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON]+(($r->get_param('diagnostics'))?self::diagnostics():[]),'onboarding'=>(bool)get_option('grw_onboarding')];
    }
    public static function diagnostics(): array {
        global $wpdb;
        $tables=[]; foreach (['locations','reviews','widgets','analytics','logs'] as $name) { $table=$wpdb->prefix.'grw_'.$name; $tables[$name]=['exists'=>$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))===$table,'rows'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $table")]; }
        return ['plugin'=>GRW_VERSION,'wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'database'=>$wpdb->db_version(),'cron_disabled'=>defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON,'next_tick'=>wp_next_scheduled('grw_tick'),'tables'=>$tables,'providers'=>Sources::catalog()];
    }
    public static function handle(string $action,\WP_REST_Request $r) {
        global $wpdb; $in=$r->get_json_params() ?: $r->get_params(); if (!is_array($in)) { return Security::error('Expected an object payload'); }
        foreach ($in as $key=>$value) { if (!in_array($key,['config','rows','ids','settings','locations'],true) && $value!==null && !is_scalar($value)) { return Security::error('Invalid field type: '.sanitize_key($key)); } }
        switch ($action) {
            case 'state': return self::state($r);
            case 'collector': return LocalCollector::configure($in);
            case 'scraper': return Scraper::handle($in);
            case 'identify': return Security::identify((string)($in['input']??''));
            case 'listing_preview':
                $embed=Security::embed($in['embed_url']??'');
                if (!empty($in['embed_url']) && !$embed) { return Security::error('Use the official Google Maps Share → Embed a map iframe or its HTTPS src URL.'); }
                $data=['embed_url'=>$embed,'business_name'=>sanitize_text_field($in['business_name']??''),'address'=>sanitize_text_field($in['address']??''),'maps_url'=>Security::maps($in['maps_url']??''),'reviews_url'=>Security::maps($in['reviews_url']??'')];
                $id=absint($in['id']??0); $rows=$id?Reviews::query(['locations'=>[$id]],1,20):[];
                return ['embed_url'=>$embed,'map_html'=>MapDisplay::preview($data,$data['business_name']), 'reviews_html'=>$rows?Renderer::render(0,['locations'=>[$id],'show_map'=>false],true):'', 'review_count'=>count($rows),'saved_count'=>$id?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews WHERE location_id=%d",$id)):0,'css'=>plugins_url('assets/frontend.css',GRW_FILE).'?ver='.GRW_VERSION,'js'=>plugins_url('assets/frontend.js',GRW_FILE).'?ver='.GRW_VERSION];
            case 'location':
                if (($in['action']??'')==='delete') {
                    $id=absint($in['id']??0); if (!Locations::get($id)) { return Security::error('Location not found'); }
                    if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews WHERE location_id=%d",$id))) { return Security::error('Hide this location or remove/reassign its saved reviews before deletion'); }
                    $wpdb->delete($wpdb->prefix.'grw_locations',['id'=>$id]); delete_option('grw_cursor_'.$id); delete_option('grw_places_validation_'.$id); delete_option('grw_sync_counts_'.$id); delete_option('grw_sync_progress_'.$id); delete_option('grw_sync_error_'.$id); delete_option('grw_browser_job_'.$id); delete_option('grw_browser_claim_'.$id); delete_option('grw_browser_result_'.$id); Cache::invalidate(); GoogleBusiness::cleanup(true); return ['deleted'=>true];
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
                if (!empty($in['all'])) { $out=[]; foreach (Locations::all() as $l) { $d=json_decode($l['data'],true); $p=Sources::all()[$d['provider']]??null; if (Sources::eligible($l)) { $wpdb->update($wpdb->prefix.'grw_locations',['next_sync'=>time()],['id'=>$l['id']]); $out[]=$l['id']; } } return ['queued'=>$out]; }
                return Sync::run(absint($in['id']??0));
            case 'places':
                $pa=sanitize_key($in['action']??'status');
                if (in_array($pa,['configure','disconnect'],true)) { return Places::configure($in); }
                if ($pa==='lookup') { $result=Places::lookup((string)($in['input']??'')); return is_wp_error($result)?$result:new \WP_REST_Response($result,200,['Cache-Control'=>'no-store, private, max-age=0']); }
                if ($pa==='reviews') { $result=Places::retrieve(absint($in['id']??0)); return is_wp_error($result)?$result:new \WP_REST_Response($result,200,['Cache-Control'=>'no-store, private, max-age=0']); }
                return Places::status();
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
                if (($in['action']??'')==='test_connection') {
                    $loc=Locations::get(absint($in['id']??0)); if (!$loc || !Sources::eligible($loc) || Sources::capabilities((json_decode($loc['data'],true)?:[])['provider']??'')['storage']!=='licensed_permanent') { return Security::error('No authorized stored-review retrieval is available for this source.'); }
                    $result=Sync::run((int)$loc['id']); if (is_wp_error($result)) { return $result; }
                    $connection=Sources::connection(Locations::get((int)$loc['id']));
                    return ['success'=>$result['complete']&&$connection['connected'],'continuing'=>!$result['complete'],'accessible_count'=>$connection['accessible_count'],'counts'=>$result,'reviews'=>Reviews::query(['locations'=>[(int)$loc['id']],'source'=>'licensed','connected'=>true],1,5)];
                }
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
