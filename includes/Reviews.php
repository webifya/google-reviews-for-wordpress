<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Reviews {
    public static function normalize(array $r, int $location, string $type='import',bool $validated=false,bool $verbatim=false,array $provenance=[]) {
        if (!$validated && !Locations::get($location)) { return Security::error(__('Unknown location.', 'google-reviews-for-wordpress')); }
        foreach ($r as $v) { if ($v!==null && !is_scalar($v)) { return Security::error('Review fields must be scalar values'); } }
        $name=sanitize_text_field($r['reviewer']??$r['reviewer_name']??''); $raw=(string)($r['content']??$r['review_text']??''); $text=$verbatim?$raw:sanitize_textarea_field($raw);
        if (!$name || !$text) { return Security::error(__('Reviewer and content are required.', 'google-reviews-for-wordpress')); }
        $rating=$r['rating']??null;
        if ($rating === '') { $rating=null; }
        if ($rating !== null && (!is_numeric($rating) || (float)$rating != (int)$rating || $rating<1 || $rating>5)) { return Security::error(__('Rating must be an integer from 1 to 5 or empty.', 'google-reviews-for-wordpress')); }
        $date=trim((string)($r['review_date']??'')); $parsed=null;
        if ($date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})?)?$/',$date) || !strtotime($date) || !checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))) { return Security::error(__('Use an ISO review date.', 'google-reviews-for-wordpress')); }
            $parsed=gmdate('Y-m-d H:i:s',strtotime($date));
        }
        if ($date && strlen($date)>10 && ((int)substr($date,11,2)>23 || (int)substr($date,14,2)>59 || (int)substr($date,17,2)>59)) { return Security::error('Invalid review time'); }
        $external=sanitize_text_field($r['external_id']??$r['review_id']??''); $source=$type==='manual'?'Authorized import':sanitize_text_field($r['source_name']??'Authorized import');
        $link=Security::url($r['permalink']??$r['review_permalink']??'');
        if ($type==='licensed' && (!$external || strlen($external)>190)) { return Security::error('A bounded stable provider review ID is required'); }
        $identityParts=[$location,$type,$source,$external ?: ($link ?: [$name,$parsed,$text])];
        if ($provenance) { $identityParts=[$location,$type,[$provenance['provider_id']??'',$provenance['source_binding']??''],$external]; }
        $identity=hash('sha256',wp_json_encode($identityParts));
        return ['provider_id'=>sanitize_key($provenance['provider_id']??''),'source_binding'=>$provenance['source_binding']??'','license_reference'=>Security::url($provenance['license_reference']??''),'location_id'=>$location,'identity'=>$identity,'external_id'=>$external,'reviewer'=>$name,'avatar'=>Security::url($r['avatar']??$r['reviewer_profile_image_url']??''),'rating'=>$rating===null?null:(int)$rating,'content'=>$text,'review_date'=>$parsed,'permalink'=>$link,'source_name'=>$type==='manual'?'Manual testimonial':$source,'source_url'=>Security::url($r['source_url']??''),'source_type'=>$type,'response'=>sanitize_textarea_field($r['response']??$r['business_response']??''),'content_hash'=>hash('sha256',wp_json_encode([$name,$text,$rating,$parsed,$r['response']??$r['business_response']??'',Security::url($r['avatar']??$r['reviewer_profile_image_url']??''),$link,Security::url($r['source_url']??''),$source,Security::url($provenance['license_reference']??'')]))];
    }
    public static function import(array $rows,int $location,string $type='import',array $provenance=[]): array {
        global $wpdb; $result=['added'=>0,'updated'=>0,'unchanged'=>0,'errors'=>[]]; $table=$wpdb->prefix.'grw_reviews'; $unchanged=[]; $synced=current_time('mysql',true);
        if (!Locations::get($location)) { $result['errors'][]=['row'=>0,'message'=>'Unknown location']; return $result; }
        foreach ($rows as $i=>$r) {
            if (!is_array($r)) { $result['errors'][]=['row'=>$i+1,'message'=>'Invalid row']; continue; }
            $data=self::normalize($r,$location,$type,true,$type==='licensed',$provenance);
            if (is_wp_error($data)) { $result['errors'][]=['row'=>$i+1,'message'=>$data->get_error_message()]; continue; }
            $old=$wpdb->get_row($wpdb->prepare("SELECT id,content_hash FROM $table WHERE identity=%s",$data['identity']),ARRAY_A);
            $data['updated_at']=$synced; $data['synced_at']=$synced;
            if ($old && hash_equals($old['content_hash'],$data['content_hash'])) { $result['unchanged']++; $unchanged[]=(int)$old['id']; continue; }
            if ($old) { $ok=$wpdb->update($table,$data,['id'=>$old['id']]); $key='updated'; }
            else { $data['imported_at']=$data['updated_at']; $ok=$wpdb->insert($table,$data); $key='added'; }
            if ($ok===false) { $result['errors'][]=['row'=>$i+1,'message'=>'Database write failed']; } else { $result[$key]++; }
        }
        foreach (array_chunk($unchanged,500) as $ids) { if ($wpdb->query($wpdb->prepare("UPDATE $table SET synced_at=%s WHERE id IN (".implode(',',array_map('absint',$ids)).')',$synced))===false) { $result['errors'][]=['row'=>0,'message'=>'Database sync timestamp failed']; } }
        if ($result['added'] || $result['updated']) { Cache::invalidate(); }
        return $result;
    }
    public static function query(array $c=[],int $page=1,int $size=100): array {
        global $wpdb; $scope=(!empty($c['connected'])||empty($c['admin']))?Sources::scope(Security::ids($c['locations']??[])):null; if ($scope) { $c['_scope']=$scope; } $key=Cache::key($c,$page,$size); $cache=empty($c['admin']) && ($c['sort']??'latest')!=='random'; if ($cache && ($rows=get_transient($key))!==false) { return $rows; } $where=['1=1']; $args=[]; if ($scope) { $where[]=!empty($c['connected'])?$scope['where']:('(r.source_type<>\'licensed\' OR '.$scope['where'].')'); $args=$scope['args']; }
        if (empty($c['admin'])) { $where[]='r.published=1'; $where[]='l.active=1'; }
        $ids=Security::ids($c['locations']??[]);
        if ($ids) { $where[]='r.location_id IN ('.implode(',',array_fill(0,count($ids),'%d')).')'; array_push($args,...$ids); }
        if (!empty($c['selected'])) { $selected=Security::ids($c['selected']); if ($selected) { $where[]='r.id IN ('.implode(',',array_fill(0,count($selected),'%d')).')'; array_push($args,...$selected); } }
        if (isset($c['visibility']) && in_array((string)$c['visibility'],['0','1'],true) && !empty($c['admin'])) { $where[]='r.published=%d'; $args[]=(int)$c['visibility']; }
        if (!empty($c['minimum'])) { $where[]='r.rating >= %d'; $args[]=min(5,absint($c['minimum'])); }
        if (!empty($c['search'])) { $where[]='(r.content LIKE %s OR r.reviewer LIKE %s)'; $args[]='%'.$wpdb->esc_like($c['search']).'%'; $args[]=end($args); }
        if (!empty($c['source'])) { $where[]='r.source_type=%s'; $args[]=sanitize_key($c['source']); }
        foreach (['from'=>'>=','to'=>'<='] as $key=>$op) { if (!empty($c[$key]) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$c[$key])) { $where[]="r.review_date $op %s"; $args[]=$c[$key].($key==='to'?' 23:59:59':' 00:00:00'); } }
        $sort=['latest'=>'r.review_date DESC,r.id DESC','oldest'=>'r.review_date ASC,r.id ASC','highest'=>'r.rating DESC,r.id DESC','lowest'=>'r.rating ASC,r.id ASC','random'=>'RAND()','featured'=>'r.featured DESC,r.review_date DESC','manual'=>'r.id ASC'][$c['sort']??'latest']??'r.id DESC';
        if (($c['sort']??'')==='manual' && !empty($selected)) { $sort='FIELD(r.id,'.implode(',',array_map('absint',$selected)).')'; }
        $sql="SELECT r.* FROM {$wpdb->prefix}grw_reviews r JOIN {$wpdb->prefix}grw_locations l ON l.id=r.location_id WHERE ".implode(' AND ',$where)." ORDER BY $sort LIMIT %d OFFSET %d";
        $args[]=max(1,$size); $args[]=max(0,($page-1)*$size);
        $rows=$wpdb->get_results($wpdb->prepare($sql,...$args),ARRAY_A); if ($cache) { set_transient($key,$rows,300); } return $rows;
    }
    public static function moderate(int $id,string $action) {
        global $wpdb; $table=$wpdb->prefix.'grw_reviews';
        if ($action==='delete') { $ok=$wpdb->delete($table,['id'=>$id]); }
        else { $field=in_array($action,['feature','unfeature'],true)?'featured':'published'; $ok=$wpdb->update($table,[$field=>in_array($action,['feature','show'],true)?1:0],['id'=>$id]); }
        Cache::invalidate(); Sync::log(0,'Review '.$id.' moderation: '.$action); return $ok!==false;
    }
}
