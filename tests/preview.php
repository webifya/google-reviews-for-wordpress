<?php
require __DIR__.'/wp-load.php';
$ids=json_decode(file_get_contents(ABSPATH.'grw-preview-ids.json'),true);
$mode=sanitize_key($_GET['test']??'');
$override=$mode==='autoplay'?['autoplay'=>true,'interval'=>2000,'duration'=>150,'pause_hover'=>false,'pause_interaction'=>false]:($mode==='single'?['limit'=>1]:($mode==='avatar'?['avatar'=>150]:[]));
if (in_array($mode,['classic','minimal','dark','grid','compact'],true)) { $override=['template'=>$mode]; }
if ($mode==='disabled') { $override=['loop'=>false,'swipe'=>false,'mouse_drag'=>false,'show_dots'=>false,'show_arrows'=>false]; }
if ($mode==='modal') { $override=['expansion'=>'modal']; }
if ($mode==='empty') { $override=['selected'=>[999999999]]; }
$first=Webifya\GRW\Renderer::render((int)$ids['widget'],$override);$second=Webifya\GRW\Renderer::render((int)$ids['widget2']);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sample carousel visual test</title><?php wp_head(); ?><style>body{margin:0;font-family:Arial,sans-serif}body>.sample-label{padding:8px;text-align:center;font-size:12px;background:#fff8df}</style></head><body><div class="sample-label">Development sample data — these are not real reviews</div><?php echo $first.$second;wp_footer(); ?></body></html>
