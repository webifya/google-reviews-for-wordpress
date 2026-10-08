<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Plugin {
    public static function boot(): void {
        load_plugin_textdomain('google-reviews-for-wordpress',false,dirname(plugin_basename(GRW_FILE)).'/languages');
        if (get_option('grw_db_version')!==GRW_VERSION) { Installer::activate(); }
        add_action('grw_tick',[Sync::class,'tick']);
        add_action('admin_menu',[Admin::class,'menu']);
        add_action('admin_enqueue_scripts',[Admin::class,'assets']);
        add_action('admin_init',function(){ register_setting('grw','grw_settings',['sanitize_callback'=>[Admin::class,'settings']]); });
        add_filter('upload_mimes',function($m){ if (Security::can()) { $m['json']='application/json'; } return $m; });
        add_action('admin_post_grw_google_callback',[GoogleBusiness::class,'callback']);
        add_action('rest_api_init',[Admin::class,'routes']);
        add_shortcode('google_reviews_widget',function($atts){ $a=shortcode_atts(['id'=>0,'limit'=>0],$atts,'google_reviews_widget'); return Renderer::render(absint($a['id']),absint($a['limit'])?['limit'=>absint($a['limit'])]:[]); });
        add_shortcode('google_reviews_map',function($atts){ $a=shortcode_atts(['location'=>0,'height'=>380],$atts,'google_reviews_map'); return MapDisplay::render(absint($a['location']),absint($a['height'])); });
        add_shortcode('google_reviews_grid',function($atts){ $a=shortcode_atts(['id'=>0],$atts,'google_reviews_grid'); return Renderer::render(absint($a['id']),['template'=>'grid']); });
        add_shortcode('google_reviews_combined',function($atts){ $a=shortcode_atts(['id'=>0],$atts,'google_reviews_combined'); return Renderer::render(absint($a['id']),['show_map'=>true]); });
        add_action('init',function(){
            wp_register_script('grw-block',plugins_url('assets/block.js',GRW_FILE),['wp-blocks','wp-element','wp-components','wp-block-editor','wp-api-fetch','wp-i18n'],GRW_VERSION,true);
            wp_set_script_translations('grw-block','google-reviews-for-wordpress',dirname(GRW_FILE).'/languages');
            register_block_type('webifya/google-reviews',['api_version'=>3,'editor_script'=>'grw-block','attributes'=>['id'=>['type'=>'integer','default'=>0]],'render_callback'=>fn($a)=>Renderer::render(absint($a['id']??0))]);
        });
    }
}
