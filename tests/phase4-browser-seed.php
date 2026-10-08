<?php
require getenv('GRW_WP_ROOT').'/wp-load.php';
use Webifya\GRW\{Locations,Widgets,Places};
update_option('grw_browser_fixture4',true,false);delete_option('grw_places_budget');Places::configure(['action'=>'disconnect']);
$l=Locations::save(['name'=>'SYNTHETIC browser Places business','provider'=>'google_places','place_id'=>'ChIJbrowserFixture','business_name'=>'SYNTHETIC browser Places business','address'=>'123 Fixture Street','maps_url'=>'https://www.google.com/maps?cid=123','active'=>true]);
$w=Widgets::save(['name'=>'SYNTHETIC live Places widget','config'=>['locations'=>[$l],'show_avatar'=>false,'show_name'=>false,'show_date'=>false,'autoplay'=>false,'show_dots'=>true]]);$w2=Widgets::save(['name'=>'SYNTHETIC second live widget','config'=>['locations'=>[$l],'template'=>'dark','heading'=>'SECOND LIVE WIDGET','autoplay'=>false]]);
$legacy=Locations::save(['name'=>'SYNTHETIC retained map location','provider'=>'public','business_name'=>'SYNTHETIC retained business','maps_url'=>'https://www.google.com/maps?cid=123','embed_url'=>'https://www.google.com/maps/embed?pb=!1m2!1sfixture']);
file_put_contents(ABSPATH.'grw-phase4-ids.json',wp_json_encode(['location'=>$l,'widget'=>$w,'widget2'=>$w2,'legacy'=>$legacy]));
