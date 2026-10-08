<?php
$_SERVER['HTTP_HOST']='127.0.0.1:8097';require getenv('GRW_WP_ROOT').'/wp-load.php';
use Webifya\GRW\{Locations,Reviews,Widgets};
$l=Locations::save(['name'=>'Sample demonstration — not real reviews','active'=>true,'provider'=>'import']);
Reviews::import([
['external_id'=>'sample-1','reviewer'=>'Sample reviewer A','rating'=>5,'content'=>'Sample data only. The team explained every step clearly and made the whole experience feel straightforward. We appreciated the thoughtful support and attention to detail throughout the project.','review_date'=>'2023-10-08','source_name'=>'Development sample'],
['external_id'=>'sample-2','reviewer'=>'Sample reviewer B','rating'=>5,'content'=>'Sample data only. Professional and responsive, with helpful communication throughout our project.','review_date'=>'2023-10-08','source_name'=>'Development sample'],
['external_id'=>'sample-3','reviewer'=>'Sample reviewer C','rating'=>5,'content'=>'Sample data only. Having the right support made a meaningful difference. The guidance was practical, well explained, and tailored to our needs. We felt confident about the next steps and would gladly work together again.','review_date'=>'2023-10-08','source_name'=>'Development sample'],
['external_id'=>'sample-4','reviewer'=>'Sample reviewer D','rating'=>4,'content'=>'Sample data only. A positive experience with friendly service and clear advice.','review_date'=>'2024-01-01','source_name'=>'Development sample'],
['external_id'=>'sample-5','reviewer'=>'Sample reviewer E','rating'=>5,'content'=>'Sample data only. Thank you for keeping us informed and making time for our questions.','review_date'=>'2024-01-01','source_name'=>'Development sample']
],$l);
$config=['locations'=>[$l],'sort'=>'manual','max_chars'=>210,'show_dots'=>true,'autoplay'=>false];$w=Widgets::save(['name'=>'Classic sample','config'=>$config]);$w2=Widgets::save(['name'=>'Second independent sample','config'=>array_merge($config,['heading'=>'SECOND WIDGET','template'=>'minimal','card'=>'#ffffff','section'=>'#ffffff','border'=>'#dddddd','border_width'=>1])]);
$id=wp_insert_post(['post_title'=>'Sample review carousel preview','post_status'=>'publish','post_type'=>'page','post_content'=>'[google_reviews_widget id="'.$w.'"] [google_reviews_widget id="'.$w2.'"]']);
file_put_contents(ABSPATH.'grw-preview-ids.json',json_encode(['widget'=>$w,'widget2'=>$w2,'page'=>$id]));
echo "Preview page $id, widgets $w and $w2\n";
