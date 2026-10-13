<?php
/** Seed a disposable v1.6 installation; no real credentials or Google HTTP calls. */
require getenv('GRW_WP_ROOT').'/wp-load.php';
use Webifya\GRW\{GoogleBusiness,Locations,Widgets,Reviews};
wp_set_current_user(1);global $wpdb;if(!class_exists(GoogleBusiness::class)){require_once ABSPATH.'wp-admin/includes/plugin.php';$activation=activate_plugin('google-reviews-for-wordpress/google-reviews-for-wordpress.php');if(is_wp_error($activation))throw new RuntimeException($activation->get_error_message());}
GoogleBusiness::configure(['client_id'=>'menjar-fixture.apps.googleusercontent.com','client_secret'=>'SYNTHETIC_MENJAR_SECRET']);
(new ReflectionMethod(GoogleBusiness::class,'write'))->invoke(null,['client_id'=>'menjar-fixture.apps.googleusercontent.com','client_secret'=>'SYNTHETIC_MENJAR_SECRET','access_token'=>'SYNTHETIC_MENJAR_ACCESS','refresh_token'=>'SYNTHETIC_MENJAR_REFRESH','expires_at'=>time()+3600]);
$existingId=0;foreach(Locations::all() as $record){$data=json_decode($record['data'],true)?:[];if(($data['place_id']??'')==='ChIJy3bcVRzl9IgR5eOJcFC6ZCQ'){$existingId=(int)$record['id'];break;}}delete_option('grw_menjar_widget_id');
$id=Locations::save(['id'=>$existingId,'name'=>'Menjar Financial','business_name'=>'Menjar Financial','place_id'=>'ChIJy3bcVRzl9IgR5eOJcFC6ZCQ','provider'=>'google_business','google_parent'=>'accounts/77/locations/123','identity_confirmed'=>true,'active'=>true,'google_public'=>true]);
$wid=Widgets::save(['name'=>'SYNTHETIC Existing Menjar Style','config'=>['locations'=>[$id],'card'=>'#abc123','padding'=>37,'heading'=>'SYNTHETIC EXISTING MENJAR HEADING','limit'=>100]]);
Reviews::import([['external_id'=>'menjar-retained-fixture','reviewer'=>'SYNTHETIC Retained Reviewer','rating'=>5,'content'=>'SYNTHETIC old archive fixture']],$id);
if(!wp_next_scheduled('grw_browser_local'))wp_schedule_single_event(time()+3600,'grw_browser_local');
$rows=[];foreach(['locations','reviews','widgets','analytics','logs'] as $name)$rows[$name]=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_$name ORDER BY id",ARRAY_A);
file_put_contents(getenv('GRW_SNAPSHOT'),wp_json_encode(['rows'=>$rows,'google'=>get_option('grw_google_private'),'settings'=>get_option('grw_settings'),'id'=>$id,'widget'=>$wid,'version'=>GRW_VERSION]));echo "Baseline on ".GRW_VERSION." saved.\n";
