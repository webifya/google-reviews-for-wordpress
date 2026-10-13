<?php
/** Synthetic upstream failures; no credentials or real Google requests. */
if (!defined('ABSPATH')) { require getenv('GRW_WP_ROOT').'/wp-load.php'; }
use Webifya\GRW\GoogleBusiness;
$cases=[
 [403,'SERVICE_DISABLED','google_access','Enable this API'],
 [403,'ACCESS_TOKEN_SCOPE_INSUFFICIENT','google_access','Reconnect Google'],
 [429,'RATE_LIMIT_EXCEEDED','google_quota','0 requests/minute'],
 [403,'quotaExceeded','google_quota','0 requests/minute'],
 [403,'USER_PROJECT_DENIED','google_access','approved Google Cloud project'],
 [403,'','google_access','Workspace'],
 [404,'','google_access','could not find'],
 [503,'','google_access','temporarily unavailable'],
 [400,'UNTRUSTED_SYNTHETIC_SECRET','google_access','rejected the request'],
 [200,'','google_access','unexpected response'],
 [401,'','google_reconnect','invalid or expired'],
];
$count=0;
foreach ($cases as [$status,$reason,$code,$text]) {
 $filter=function()use($status,$reason){return ['headers'=>[],'cookies'=>[],'response'=>['code'=>$status],'body'=>$status===200?'not-json':wp_json_encode(['error'=>['message'=>'SYNTHETIC_PRIVATE_TOKEN accounts/123 secret@example.invalid','details'=>[['reason'=>$reason,'metadata'=>['consumer'=>'SYNTHETIC_PRIVATE_PROJECT']]],'errors'=>[['reason'=>$reason]]]])];};
 add_filter('pre_http_request',$filter,1,3);
 try {
  $r=GoogleBusiness::accounts();
  if (!is_wp_error($r)||$r->get_error_code()!==$code||!str_contains($r->get_error_message(),$text)||!str_contains($r->get_error_message(),'My Business Account Management API')||$r->get_error_data()['google_http_status']!==$status) { throw new RuntimeException('Diagnostic classification failed: '.$status.' '.$reason); }
  $safe=wp_json_encode([$r->get_error_message(),$r->get_error_data()]);
  if (str_contains($safe,'SYNTHETIC_PRIVATE')||str_contains($safe,'UNTRUSTED_SYNTHETIC')||str_contains($safe,'accounts/123')||str_contains($safe,'secret@example')) { throw new RuntimeException('Diagnostic leaked upstream data'); }
  echo 'PASS: safe Google diagnostic '.$status.' '.$reason."\n";$count++;
 } finally { remove_filter('pre_http_request',$filter,1); }
}
echo "Google diagnostic suite: $count synthetic failure cases; live Google not tested.\n";
