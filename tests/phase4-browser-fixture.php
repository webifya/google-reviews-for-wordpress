<?php
/** Copy to an ISOLATED test site's mu-plugins. Never packaged or used on a customer site. */
add_action('plugins_loaded',function(){
 if (!get_option('grw_browser_fixture4')) { return; }
 add_filter('pre_http_request',function($pre,$args,$url){
  if (!str_starts_with($url,'https://places.googleapis.com/v1/places/')) { return $pre; }
  $id=basename(wp_parse_url($url,PHP_URL_PATH));$mode=(int)get_option('grw_browser_fixture4_status',200);$reviews=[];
  for($i=0;$i<5;$i++){$reviews[]=['name'=>'places/'.$id.'/reviews/fixture'.$i,'authorAttribution'=>['displayName'=>'SYNTHETIC reviewer '.($i+1),'photoUri'=>'https://example.org/fixture-avatar.png','uri'=>'https://www.google.com/maps/contrib/123'],'rating'=>5,'originalText'=>['text'=>str_repeat('SYNTHETIC review fixture. Not an actual customer review. ',6)],'publishTime'=>'2023-10-08T10:00:00Z','googleMapsUri'=>'https://www.google.com/maps/reviews/data=fixture'.$i,'visitDate'=>['year'=>2023,'month'=>10]];}
  return ['response'=>['code'=>$mode],'headers'=>[],'body'=>wp_json_encode(['id'=>$id,'displayName'=>['text'=>'SYNTHETIC lookup business'],'formattedAddress'=>'123 Fixture Street','googleMapsUri'=>'https://www.google.com/maps?cid=123','rating'=>5,'userRatingCount'=>100,'reviews'=>$reviews,'attributions'=>[['provider'=>'Fixture provider credit','providerUri'=>'https://example.org/credits']]]),'cookies'=>[]];
 },10,3);
});
