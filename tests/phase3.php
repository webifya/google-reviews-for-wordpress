<?php
/** Public listing and map regressions. Only synthetic fixture data is used. */
require rtrim(getenv('GRW_WP_ROOT'),'/').'/wp-load.php';
if (!class_exists('Webifya\\GRW\\Plugin')) { require_once ABSPATH.'wp-content/plugins/google-reviews-for-wordpress/google-reviews-for-wordpress.php'; Webifya\GRW\Plugin::boot(); }
use Webifya\GRW\{Locations,Security,Widgets,MapDisplay,Renderer,Admin,GoogleBusiness};
wp_set_current_user(1);$assertions=0;
function verify3($ok,$label){global $assertions;if(!$ok){throw new RuntimeException($label);}echo "PASS: $label\n";$assertions++;}
$src='https://www.google.com/maps/embed?pb=!1m2!1sfixture';
$html='<iframe src="'.$src.'" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
verify3(Security::embed($html)===$src,'official sharing iframe extracts source only');
foreach(['<script>alert(1)</script>'.$html,str_replace('www.google.com','evil.example',$html),str_replace(' width=', ' onload="alert(1)" width=',$html),str_replace(' width=', ' srcdoc="unsafe" width=',$html),$html.$html,'https://www.google.com/maps/embed/v1/place?key=secret&q=business','https://www.google.com/maps/embed?pb=','https://www.google.com/maps/embed?pb=!1m2!1sfixture&unexpected=x','<iframe src="javascript:alert(1)"></iframe>'] as $bad){verify3(Security::embed($bad)==='','unsafe or unsupported iframe rejected');}
verify3(Security::maps('https://www.google.com/mapsevil')==='','Google path boundary enforced');
$location=Locations::save(['business_name'=>'Non-owned fixture','address'=>'105 Banks Station #1068, Fayetteville, GA 30214','maps_url'=>'https://www.google.com/maps?cid=123','embed_url'=>$html,'reviews_url'=>'https://www.google.com/maps?cid=123','listing_confirmed'=>true]);
verify3(is_int($location)&&$location>0,'non-owned public listing saves without Google credentials');
$l=Locations::get($location);$d=json_decode($l['data'],true);
verify3($d['provider']==='public'&&$d['connection_mode']==='public','default mode is public');
verify3($d['embed_url']===$src&&!str_contains($l['data'],'iframe'),'stored embed is URL only');
verify3($d['listing_status']==='linked'&&$d['map_status']==='configured'&&$d['review_source_status']==='unavailable','structured independent statuses');
$p=Locations::presentation($l);verify3($p['listing']==='Public Listing Linked'&&$p['map']==='Map Embed Ready'&&$p['sync']==='Review Sync Unavailable','friendly distinct map and sync statuses');
verify3((int)$l['next_sync']===0,'public listing is not scheduled');
$map=do_shortcode('[google_reviews_map location="'.$location.'" height="350"]');
verify3(str_contains($map,'--map-height:350px')&&str_contains($map,'<iframe')&&str_contains($map,esc_url($src)),'map shortcode renders saved official source');
verify3(str_contains($map,'View on Google Maps')&&str_contains($map,'View Google Reviews'),'public listing and reviews links');
verify3(!str_contains($map,'style="border:0;"')&&!str_contains($map,'width="600"'),'supplied iframe attributes discarded');
verify3(str_contains(MapDisplay::render($location,999999),'--map-height:800px'),'map height bounded');
$widget=Widgets::save(['name'=>'Phase 3 fixture','config'=>['locations'=>[$location],'show_heading'=>false,'show_subtitle'=>false]]);
verify3(!str_contains(do_shortcode('[google_reviews_widget id="'.$widget.'"]'),'<iframe'),'old widget shortcode does not force a map');
verify3(str_contains(do_shortcode('[google_reviews_grid id="'.$widget.'"]'),'grw-grid'),'independent grid shortcode');
verify3(str_contains(do_shortcode('[google_reviews_combined id="'.$widget.'"]'),'<iframe'),'combined shortcode adds optional map');
verify3(Widgets::sanitize(['show_map'=>true,'map_height'=>1])['map_height']===200,'map widget settings sanitized');
$r=new WP_REST_Request('POST');$r->set_header('Content-Type','application/json');$r->set_body(wp_json_encode(['id'=>$location,'embed_url'=>$html,'business_name'=>'Non-owned fixture']));$preview=Admin::handle('listing_preview',$r);
verify3($preview['review_count']===0&&$preview['reviews_html']===''&&str_contains($preview['map_html'],'iframe'),'separate actual review preview remains empty');
Locations::save(['id'=>$location,'business_name'=>'Updated public fixture']);$after=json_decode(Locations::get($location)['data'],true);
verify3($after['embed_url']===$src&&$after['maps_url']===$d['maps_url']&&$after['reviews_url']===$d['reviews_url'],'partial update preserves embeds and links');
$feed=Locations::save(['name'=>'Feed fixture','provider'=>'local_json','active'=>true,'authorized'=>true,'attachment_id'=>1,'frequency'=>86400]);
$wpdb->update($wpdb->prefix.'grw_locations',['status'=>'synced','next_sync'=>123456789],['id'=>$feed]);update_option('grw_cursor_'.$feed,'fixture-cursor');
Locations::save(['id'=>$feed,'maps_url'=>'https://www.google.com/maps?cid=456','embed_url'=>$html,'logo'=>'https://example.com/logo.png']);$fl=Locations::get($feed);
verify3((int)$fl['next_sync']===123456789&&$fl['status']==='synced'&&get_option('grw_cursor_'.$feed)==='fixture-cursor','listing metadata update preserves sync schedule and cursor');
Locations::save(['id'=>$feed,'attachment_id'=>2]);verify3(get_option('grw_cursor_'.$feed)===false,'source attachment change clears cursor');
verify3(!is_wp_error(Locations::save(['name'=>'Search fixture','business_name'=>'Search fixture','address'=>'Fixture address','place_id'=>'ChIJ_fixture_id'])),'name and address listing can be saved');
verify3(str_contains(Locations::listing_url(['business_name'=>'Name','address'=>'Address','place_id'=>'ChIJ_fixture_id']),'query_place_id=ChIJ_fixture_id'),'key-free search links support supplied Place IDs');
verify3(is_wp_error(Locations::save(['id'=>$location,'embed_url'=>'<iframe src="https://evil.example"></iframe>'])),'invalid edit rejected');
verify3(json_decode(Locations::get($location)['data'],true)['embed_url']===$src,'invalid edit leaves stored data intact');
$before=Locations::get($location);GoogleBusiness::disconnect();verify3(Locations::get($location)===$before,'account disconnect preserves public listing');
Locations::save(['id'=>$location,'active'=>false]);verify3(MapDisplay::render($location)==='','inactive maps omitted');
verify3(MapDisplay::render(9999999)==='','unknown map shortcode is safe');
$legacy=['data'=>wp_json_encode(['provider'=>'embed','maps_url'=>$d['maps_url'],'embed_url'=>$src]),'status'=>'unsupported_source'];verify3(Locations::presentation($legacy)['map']==='Map Embed Ready','legacy unsupported status does not invalidate map');
verify3(!str_contains(wp_json_encode(GoogleBusiness::status()),'client_secret'),'connection status never returns secrets');
$write=new ReflectionMethod(GoogleBusiness::class,'write');$write->invoke(null,['client_id'=>'fixture.apps.googleusercontent.com','client_secret'=>'fixture-secret','refresh_token'=>'fixture-refresh','expires_at'=>0]);
$failure=function($pre,$args,$url){return str_contains($url,'oauth2.googleapis.com/token')?['headers'=>[],'body'=>'{}','response'=>['code'=>401]]:$pre;};add_filter('pre_http_request',$failure,10,3);
verify3(is_wp_error(GoogleBusiness::api('https://mybusinessaccountmanagement.googleapis.com/v1/accounts')),'expired OAuth token reports attention');
verify3(GoogleBusiness::read()['refresh_token']==='fixture-refresh'&&GoogleBusiness::read()['client_secret']==='fixture-secret'&&GoogleBusiness::status()['requires_attention'],'failed refresh preserves encrypted credentials');
remove_filter('pre_http_request',$failure,10);GoogleBusiness::disconnect();
$wpdb->delete($wpdb->prefix.'grw_widgets',['id'=>$widget]);foreach([$location,$feed] as $id){$r->set_body(wp_json_encode(['id'=>$id,'action'=>'delete']));verify3(Admin::handle('location',$r)['deleted']===true,'empty public/feed listing deletion');}
$wpdb->query("DELETE FROM {$wpdb->prefix}grw_locations WHERE name='Search fixture'");
echo "$assertions phase 3 assertions passed\n";
