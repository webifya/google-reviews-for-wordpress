<?php
/** Tests only a disposable installed package. Network-free synthetic fixtures. */
require rtrim(getenv('GRW_WP_ROOT'),'/').'/wp-load.php';
use Webifya\GRW\{LocalCollector,Scraper,Locations,Installer,Sync,Reviews};
Installer::activate(); global $wpdb; $n=0;
function combined($ok,$label){global $n;if(!$ok)throw new RuntimeException($label);echo "PASS: $label\n";$n++;}
$old=get_option('grw_collector',null);$check=get_option('grw_collector_check',null);
$local=dirname(GRW_FILE).'/worker/local.cjs';$original=file_get_contents($local);$id=0;
try {
 combined(is_file(dirname(GRW_FILE).'/worker/node_modules/playwright/package.json')&&is_file(dirname(GRW_FILE).'/worker/node_modules/playwright-core/package.json'),'one installed ZIP includes browser libraries');
 $configured=LocalCollector::configure(['mode'=>'local','node'=>getenv('GRW_NODE')?:'/usr/local/bin/node','browser'=>getenv('GRW_BROWSER_PATH')?:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome','headless'=>true]);
 combined(!is_wp_error($configured)&&$configured['ready'],'installed executable paths accepted');
 combined(is_wp_error(LocalCollector::configure(['mode'=>'local','node'=>'/usr/local/bin/node;echo bad'])),'command fragments rejected');
 combined(is_wp_error(LocalCollector::configure(['mode'=>'local','node'=>'node'])),'relative executable paths rejected');
 combined(is_wp_error(LocalCollector::configure(['mode'=>'bad'])),'invalid execution mode rejected');
 do_action('rest_api_init');wp_set_current_user(0);$request=new WP_REST_Request('POST','/grw/v1/collector');$request->set_body_params(['mode'=>'local']);combined(in_array(rest_get_server()->dispatch($request)->get_status(),[401,403],true),'anonymous collector configuration denied');
 $ready=LocalCollector::configure(['action'=>'check']);combined(!is_wp_error($ready),'bundled browser launches without Google or HTTP credentials');
 $id=Locations::save(['name'=>'SYNTHETIC Combined Test','business_name'=>'SYNTHETIC Combined Test','provider'=>'google_scraper','place_id'=>'ChIJsyntheticCombined','scraper_enabled'=>true,'frequency'=>259200]);
 // Fixture transport replaces only this disposable copy's fixed runner, then is restored in finally.
 file_put_contents($local,<<<'JS'
'use strict';let input='';process.stdin.on('data',s=>input+=s);process.stdin.on('end',()=>{const j=JSON.parse(input);if(Object.keys(j).sort().join(',')!=='business_name,maximum,url'||!j.url.includes('ChIJsyntheticCombined'))process.exit(2);else process.stdout.write(JSON.stringify({reason:'all_visible',advertised_total:1,rows:[{external_id:'ChZsyntheticCombinedReview',reviewer:'SYNTHETIC Reviewer',rating:5,content:'SYNTHETIC combined fixture'}]}));});
JS);
 combined(!empty(Sync::run($id)['queued'])&&wp_next_scheduled('grw_browser_local'),'download queues existing job and local WP-Cron event');
 LocalCollector::run();$count=fn()=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews WHERE location_id=%d",$id));
 combined(LocalCollector::status()['mode']==='local','local claims preserve bundled execution mode');
 combined($count()===1&&!get_option('grw_browser_job_'.$id),'bundled process result enters existing validated storage without application password');
 combined(abs((int)Locations::get($id)['next_sync']-time()-259200)<5,'three-day schedule preserved');
 Sync::run($id);LocalCollector::run();combined($count()===1&&get_option('grw_sync_counts_'.$id)['unchanged']===1,'same process result does not duplicate review');
 Locations::save(['id'=>$id,'frequency'=>86400]);Sync::run($id);LocalCollector::run();combined(abs((int)Locations::get($id)['next_sync']-time()-86400)<5,'daily schedule preserved');
 Sync::run($id);add_option('grw_collector_lock',time(),'','no');LocalCollector::run();combined(get_option('grw_browser_job_'.$id)['state']==='queued','global lock prevents concurrent local browser execution');delete_option('grw_collector_lock');
 file_put_contents($local,"process.stdout.write('invalid json');");LocalCollector::run();combined($count()===1&&get_option('grw_sync_error_'.$id)['code']==='browser_runtime_error','malformed process output stops collection and preserves stored reviews');
 file_put_contents($local,$original);
 LocalCollector::configure(['mode'=>'remote']);combined(!wp_next_scheduled('grw_browser_local'),'external compatibility mode cancels local execution event');
} finally {
 file_put_contents($local,$original);wp_clear_scheduled_hook('grw_browser_local');delete_option('grw_collector_lock');
 $old===null?delete_option('grw_collector'):update_option('grw_collector',$old,false);$check===null?delete_option('grw_collector_check'):update_option('grw_collector_check',$check,false);
 if($id){foreach(['reviews','logs'] as $table)$wpdb->delete($wpdb->prefix.'grw_'.$table,['location_id'=>$id]);$wpdb->delete($wpdb->prefix.'grw_locations',['id'=>$id]);foreach(['grw_browser_job_','grw_sync_counts_','grw_sync_error_']as $prefix)delete_option($prefix.$id);}
}
echo "$n combined plugin assertions passed; Google requests: 0\n";
