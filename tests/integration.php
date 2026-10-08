<?php
/** Run against an isolated WordPress site: GRW_WP_ROOT=/path/to/wordpress php tests/integration.php */
$root=getenv('GRW_WP_ROOT');
if (!$root || !is_file($root.'/wp-load.php')) { fwrite(STDERR,"Set GRW_WP_ROOT to a disposable installed WordPress directory.\n"); exit(2); }
$_SERVER['HTTP_HOST']='127.0.0.1:8097';$_SERVER['REQUEST_METHOD']='GET';
require $root.'/wp-load.php';require_once ABSPATH.'wp-admin/includes/plugin.php';
use Webifya\GRW\{Installer,Security,Locations,Reviews,Widgets,Renderer,Sources,SourceAdapter,Sync,Analytics,Admin};
$passed=0;
function check($condition,$label){global $passed;if(!$condition){fwrite(STDERR,"FAIL: $label\n");exit(1);} $passed++;echo "PASS: $label\n";}
$result=activate_plugin('google-reviews-for-wordpress/google-reviews-for-wordpress.php');
check(!is_wp_error($result),'activation without fatal errors');
if (!shortcode_exists('google_reviews_widget')) { Webifya\GRW\Plugin::boot(); }
Installer::activate();Installer::activate();
global $wpdb;
foreach (['locations','reviews','widgets','analytics','logs'] as $table) { check($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($wpdb->prefix.'grw_'.$table)))===$wpdb->prefix.'grw_'.$table,'table '.$table); }
check((bool)wp_next_scheduled('grw_tick'),'cron scheduled');
$count=0;foreach (_get_cron_array() as $time=>$hooks){if(isset($hooks['grw_tick']))$count+=count($hooks['grw_tick']);}check($count===1,'no duplicate cron schedule');
$admin=get_user_by('login','grwtest');if(!$admin){$id=wp_insert_user(['user_login'=>'grwtest','user_pass'=>wp_generate_password(),'role'=>'administrator']);$admin=get_user_by('id',$id);}wp_set_current_user($admin->ID);
check(Security::can(),'administrator permission');
$location=Locations::save(['name'=>'Integration sample location','active'=>true,'provider'=>'import','frequency'=>86400,'maps_url'=>'https://www.google.com/maps/place/example']);
check(is_int($location)&&$location>0,'location creation');
check(Locations::save(['id'=>$location,'name'=>'Integration sample location updated','active'=>true,'provider'=>'import','frequency'=>0])===$location,'location update');
check(is_wp_error(Locations::save(['name'=>'Bad URL','maps_url'=>'https://evil.example/maps','provider'=>'import'])),'invalid maps URL rejected');
check(Security::embed('https://www.google.com/maps/embed?pb=example')!=='','official share embed accepted');
check(Security::embed('https://evil.example/maps/embed?pb=x')==='','foreign embed rejected');
check(Security::url('https://127.0.0.1/x')===''&&Security::url('https://user:pass@evil.example/x')===''&&Security::url('javascript:alert(1)')==='','unsafe URLs rejected');
check(is_wp_error(Sources::request('https://metadata.google.internal/x',['api.example.com'])),'SSRF allowlist enforced');
$fixture=['external_id'=>'test-'.wp_generate_uuid4(),'reviewer'=>'Sample reviewer বাংলা العربية','rating'=>5,'content'=>'Sample testimonial <script>alert(1)</script> This is authorized test data only.','review_date'=>'2024-01-15','source_name'=>'Sample fixture'];
$r=Reviews::import([$fixture],$location);check($r['added']===1&&!$r['errors'],'authorized review import');
$r=Reviews::import([$fixture],$location);check($r['unchanged']===1&&$r['added']===0,'deduplication');
$fixture['content']='Updated sample review বাংলা العربية';$r=Reviews::import([$fixture],$location);check($r['updated']===1,'stable identity update');
$bad=$fixture;$bad['rating']=6;$r=Reviews::import([$bad],$location);check(count($r['errors'])===1,'invalid rating rejected');
$bad=$fixture;$bad['review_date']='2024-02-31';check(is_wp_error(Reviews::normalize($bad,$location)),'invalid calendar date rejected');
$rows=Reviews::query(['locations'=>[$location],'minimum'=>5]);check(count($rows)===1&&str_contains($rows[0]['content'],'বাংলা'),'Unicode preservation and rating filter');
check(Reviews::query(['locations'=>[$location],'minimum'=>5,'search'=>'absent'])===[],'search query');
$id=$rows[0]['id'];Reviews::moderate((int)$id,'hide');check(Reviews::query(['locations'=>[$location]])===[],'hide review');Reviews::moderate((int)$id,'show');
$second=$fixture;$second['external_id']='other-'.wp_generate_uuid4();$second['rating']=2;$second['review_date']='2023-01-01';Reviews::import([$second],$location);
$sorted=Reviews::query(['locations'=>[$location],'sort'=>'lowest']);check((int)$sorted[0]['rating']===2,'rating ordering');
$sorted=Reviews::query(['locations'=>[$location],'sort'=>'oldest']);check((int)$sorted[0]['rating']===2,'date ordering');
$wid=Widgets::save(['name'=>'Sample test widget','config'=>['locations'=>[$location],'show_summary'=>true]]);$wid2=Widgets::save(['name'=>'Second sample widget','config'=>['locations'=>[$location],'template'=>'dark']]);
check($wid!==$wid2,'independent widget IDs');
$unsafe=Widgets::sanitize(['text'=>'red;display:none','font'=>'url(javascript:alert(1))','desktop'=>99]);check($unsafe['text']==='#111111'&&$unsafe['font']==='inherit'&&$unsafe['desktop']===6,'CSS sanitization');
$html=do_shortcode('[google_reviews_widget id="'.$wid.'" limit="1"]');check(substr_count($html,'<article')===1&&str_contains($html,'selected subset'),'shortcode override and truthful subset summary');
check(!str_contains($html,'<script>alert')&&!str_contains($html,'verified'),'no XSS or fabricated verification');
check(str_contains(Renderer::render($wid2),'grw-dark'),'second widget renderer');
check(is_wp_error(Sync::run($location)),'unsupported sync reports unavailable');
class TestAdapter implements SourceAdapter {
 public function label():string{return 'Licensed test provider';}public function supports_sync():bool{return true;}
 public function fetch(array $location,?string $cursor){if(get_option('grw_test_fail'))return new WP_Error('temporary','Temporary provider error');return ['reviews'=>[['external_id'=>'adapter-test','reviewer'=>'Sample adapter reviewer','content'=>'Sample authorized adapter data','rating'=>4]],'cursor'=>null];}
}
add_filter('grw_source_adapters',function($a){$a['test']=new TestAdapter();return $a;});
$syncid=Locations::save(['name'=>'Sample sync location','active'=>true,'provider'=>'test','frequency'=>86400]);
$r=Sync::run($syncid);check(is_array($r)&&$r['added']===1,'authorized adapter synchronization');
check(Locations::get($syncid)['status']==='last_sync_successful','successful sync status');
add_option('grw_lock_'.$syncid,time(),'','no');check(is_wp_error(Sync::run($syncid)),'concurrent synchronization lock');delete_option('grw_lock_'.$syncid);
update_option('grw_test_fail',true);$r=Sync::run($syncid);check(is_wp_error($r)&&count(Reviews::query(['locations'=>[$syncid]]))===1,'temporary error preserves data');delete_option('grw_test_fail');
check(Locations::get($syncid)['next_sync']>time(),'bounded retry scheduled');
// Built-in permitted adapter: only local uploads, never arbitrary network URLs.
$uploads=wp_upload_dir();wp_mkdir_p($uploads['path']);$file=$uploads['path'].'/grw-test-feed.json';file_put_contents($file,wp_json_encode([['external_id'=>'local-1','reviewer'=>'Sample local reviewer','content'=>'Sample local feed','rating'=>5]]));
$attachment=wp_insert_attachment(['post_title'=>'Sample authorized feed','post_mime_type'=>'application/json','post_status'=>'inherit'],$file);update_attached_file($attachment,$file);
$local=Locations::save(['name'=>'Local feed test','active'=>true,'provider'=>'local_json','frequency'=>86400,'attachment_id'=>$attachment,'authorized'=>true]);$r=Sync::run($local);check(is_array($r)&&$r['added']===1,'local JSON feed automatic adapter');
file_put_contents($file,wp_json_encode([['external_id'=>'local-1','reviewer'=>'Sample local reviewer','content'=>'Updated authorized local feed','rating'=>5]]));$r=Sync::run($local);check($r['updated']===1,'local feed update on sync');
$evil=wp_insert_attachment(['post_title'=>'Unsafe feed','post_mime_type'=>'application/json']);update_attached_file($evil,'/etc/passwd');$localBad=Locations::save(['name'=>'Unsafe local','active'=>true,'provider'=>'local_json','attachment_id'=>$evil,'authorized'=>true]);check(is_wp_error(Sync::run($localBad)),'local feed path traversal prevention');
update_option('grw_settings',['analytics'=>true,'consent_required'=>true,'retention'=>90,'delete_data'=>false]);
wp_set_current_user(0);check(!Security::can(),'anonymous lacks privileged capability');
do_action('rest_api_init');$response=rest_do_request(new WP_REST_Request('GET','/grw/v1/state'));check($response->get_status()===401,'REST private data denied');
$request=new WP_REST_Request('POST','/grw/v1/events');$request->set_param('session',str_repeat('b',32));$request->set_param('events',[['widget'=>$wid,'event'=>'impression'],['widget'=>$wid,'event'=>'unique']]);$request->set_param('consent',false);check(Analytics::collect($request)['accepted']===0,'consent required');$request->set_param('consent',true);$request->set_param('session',md5(wp_generate_uuid4()));check(Analytics::collect($request)['accepted']===2,'analytics visible events collection');check(Analytics::collect($request)['accepted']===0,'server session deduplication');
$request->set_header('origin','https://evil.example');check(is_wp_error(Analytics::collect($request)),'foreign analytics origin blocked');$request->set_header('origin',home_url());
$request->set_param('session','bad');check(is_wp_error(Analytics::collect($request)),'analytics token validation');
wp_set_current_user($admin->ID);check(str_contains(Renderer::render($wid),'&quot;track&quot;:false'),'administrator previews excluded from analytics');
Installer::deactivate();check(!wp_next_scheduled('grw_tick'),'deactivation clears cron');check(Locations::get($location)!==null,'deactivation retains data');Installer::activate();
check(Security::csv('=HYPERLINK("evil")')[0]==="'",'CSV formula injection protection');
// Verify uninstall on this disposable test database, then restore schema for browser fixtures.
$before=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews");
define('WP_UNINSTALL_PLUGIN',true);require WP_PLUGIN_DIR.'/google-reviews-for-wordpress/uninstall.php';
check((int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews")===$before,'uninstall retention default');
update_option('grw_settings',['delete_data'=>true]);require WP_PLUGIN_DIR.'/google-reviews-for-wordpress/uninstall.php';
check($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($wpdb->prefix.'grw_reviews')))===null,'explicit uninstall deletes only plugin tables');
check($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($wpdb->posts)))===$wpdb->posts,'uninstall preserves WordPress tables');
Installer::activate();
echo "$passed assertions passed on WordPress ".get_bloginfo('version').', PHP '.PHP_VERSION."\n";
