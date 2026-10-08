<?php
/** ISOLATED browser fixture only; never packaged or installed on a customer site. */
add_action('plugins_loaded',function(){
 if(!get_option('grw_browser_fixture6'))return;
 add_filter('grw_source_adapters',function($a){$a['browser6']=new class implements Webifya\GRW\PermanentReviewProvider {
  public function label():string{return 'SYNTHETIC Phase6 licensed browser source';}
  public function supports_sync():bool{return true;}
  public function policy():array{return ['permanent_storage'=>true,'public_display'=>true,'license_reference'=>'https://example.org/phase6-browser-contract'];}
  public function fetch(array $location,?string $cursor){$rows=[];for($i=0;$i<6;$i++)$rows[]=['external_id'=>'browser6-'.$i,'reviewer'=>'SYNTHETIC local reviewer '.$i,'rating'=>5,'content'=>str_repeat('SYNTHETIC saved review, not an actual customer review. ',6),'review_date'=>'2024-01-'.sprintf('%02d',$i+1),'source_name'=>'SYNTHETIC licensed source','permalink'=>'https://example.org/review/'.$i];return ['reviews'=>$rows,'cursor'=>null];}
 };return $a;});
});
