<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Admin {
    public static function menu(): void {
        add_menu_page('Menjar Financial Reviews','Menjar Reviews','manage_options','grw',[self::class,'page'],'dashicons-star-filled',58);
        foreach (['grw'=>'Connection','grw-reviews'=>'Reviews','grw-style'=>'Style Setup'] as $slug=>$label) { add_submenu_page('grw',$label,$label,'manage_options',$slug,[self::class,'page']); }
    }
    public static function assets(string $hook): void {
        if (!str_contains($hook,'grw')) { return; }
        wp_enqueue_style('grw-admin',plugins_url('assets/admin.css',GRW_FILE),[],GRW_VERSION);Renderer::assets();
        wp_enqueue_script('grw-admin',plugins_url('assets/admin.js',GRW_FILE),['wp-i18n'],GRW_VERSION,true);
        wp_localize_script('grw-admin','GRW_ADMIN',['endpoint'=>rest_url('grw/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'page'=>sanitize_key($_GET['page']??'grw'),'defaults'=>Project::defaults(),'presets'=>Widgets::presets()]);
    }
    public static function page(): void { if (Security::can()) { echo '<div class="wrap grw-admin"><h1>Menjar Financial Reviews</h1><div id="grw-admin-app"></div><noscript>Enable JavaScript to use the style editor.</noscript></div>'; } }
    public static function routes(): void {
        foreach (['state','google','widget','preview'] as $action) { register_rest_route('grw/v1','/'.$action,['methods'=>$action==='state'?'GET':'POST','permission_callback'=>[Security::class,'can'],'callback'=>fn($r)=>self::handle($action,$r)]); }
        register_rest_route('grw/v1','/business-widget',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>[GoogleBusiness::class,'public_widget']]);
    }
    public static function handle(string $action,\WP_REST_Request $request) {
        $in=$request->get_json_params()?:$request->get_params();if(!is_array($in)){return Security::error('Invalid request.');}
        foreach($in as $k=>$v){if($k!=='config'&&!is_scalar($v)&&$v!==null){return Security::error('Invalid field.');}}
        switch($action){
            case 'state':$l=Project::location();$w=Project::widget();return self::private_response(['google'=>GoogleBusiness::status(),'location'=>$l,'widget'=>$w,'widgets'=>Widgets::all(),'version'=>GRW_VERSION,'place_id'=>Project::PLACE_ID]);
            case 'google':
                $cursor=$in['cursor']??null;if($cursor!==null&&(!is_string($cursor)||strlen($cursor)>2048)){return Security::error('Invalid Google cursor.');}
                switch($in['action']??'status'){
                    case 'configure':$r=GoogleBusiness::configure($in);break;
                    case 'authorize':$r=GoogleBusiness::authorize();break;
                    case 'disconnect':$r=GoogleBusiness::disconnect();break;
                    case 'accounts':$r=GoogleBusiness::accounts($cursor);break;
                    case 'locations':$r=GoogleBusiness::locations((string)($in['account']??''),$cursor);break;
                    case 'bind':$r=GoogleBusiness::bind($in);break;
                    case 'display':$l=Project::location();$r=$l?Locations::save(['id'=>$l['id'],'google_public'=>filter_var($in['google_public']??false,FILTER_VALIDATE_BOOLEAN)]):Security::error('Connect Menjar Financial first.');break;
                    case 'reviews':$l=Project::location();$r=$l?GoogleBusiness::reviews((int)$l['id'],$cursor,!empty($in['force'])):Security::error('Connect Menjar Financial first.');break;
                    default:$r=GoogleBusiness::status();
                }return self::private_response($r);
            case 'widget':
                if(($in['action']??'')==='select'){$w=Widgets::get(absint($in['id']??0));if(!$w){return Security::error('Style not found.');}update_option('grw_menjar_widget_id',(int)$w['id'],false);return self::private_response(['selected'=>(int)$w['id']]);}
                if(!is_array($in['config']??null)){return Security::error('Invalid style settings.');}$w=Project::widget();$r=Widgets::save(['id'=>$w['id']??0,'name'=>$w['name']??'Menjar Financial reviews','config'=>$in['config']]);if(!is_wp_error($r)){update_option('grw_menjar_widget_id',$r,false);}return self::private_response($r);
            case 'preview':if(!is_array($in['config']??null)){return Security::error('Invalid style settings.');}$w=Project::widget();return self::private_response(['html'=>Renderer::render($w['id']??0,$in['config'],true),'css'=>plugins_url('assets/frontend.css',GRW_FILE).'?ver='.GRW_VERSION,'js'=>plugins_url('assets/frontend.js',GRW_FILE).'?ver='.GRW_VERSION]);
        }return Security::error('Feature unavailable.',404);
    }
    private static function private_response($data){return is_wp_error($data)?$data:new \WP_REST_Response($data,200,['Cache-Control'=>'no-store, private, max-age=0']);}
}
