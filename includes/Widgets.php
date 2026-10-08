<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Widgets {
    public static function defaults(): array { return [
        'template'=>'classic','locations'=>[],'selected'=>[],'sort'=>'latest','minimum'=>0,'limit'=>100,
        'heading'=>'CLIENT REVIEWS','subtitle'=>'What Our Clients Say...','desktop'=>3,'tablet'=>2,'mobile'=>1,
        'autoplay'=>true,'interval'=>5000,'duration'=>500,'loop'=>true,'pause_hover'=>true,'pause_interaction'=>true,'peek'=>false,
        'show_heading'=>true,'show_subtitle'=>true,'show_avatar'=>true,'show_name'=>true,'show_date'=>true,'show_stars'=>true,'show_readmore'=>true,'show_arrows'=>true,'show_dots'=>false,'show_summary'=>false,'show_maps'=>false,'analytics'=>true,
        'max_chars'=>240,'max_lines'=>4,'expansion'=>'inline','summary_position'=>'above','equal_height'=>true,
        'section'=>'#eeeeee','card'=>'#f8f8f8','border'=>'#eeeeee','heading_color'=>'#000000','subtitle_color'=>'#222222','text'=>'#111111','name_color'=>'#111111','muted'=>'#777777','stars'=>'#fbbc04','link'=>'#666666','arrow'=>'#555555','arrow_bg'=>'#ffffff','dot'=>'#aaaaaa','dot_active'=>'#111111',
        'heading_size'=>56,'subtitle_size'=>24,'name_size'=>20,'text_size'=>20,'date_size'=>16,'font_weight'=>400,'line_height'=>1.5,'letter_spacing'=>0,'font'=>'inherit',
        'width'=>1440,'padding'=>80,'card_padding'=>28,'gap'=>24,'radius'=>18,'border_width'=>0,'avatar'=>84,'min_height'=>340,'shadow'=>false,'heading_align'=>'center','text_align'=>'center'
    ]; }
    public static function sanitize(array $in): array {
        $out=self::defaults();
        foreach ($out as $k=>$v) {
            if (!array_key_exists($k,$in)) { continue; }
            if (is_bool($v)) { $out[$k]=filter_var($in[$k],FILTER_VALIDATE_BOOLEAN); }
            elseif (in_array($k,['locations','selected'],true)) { $out[$k]=Security::ids($in[$k]); }
            elseif (is_int($v) || is_float($v)) { $out[$k]=is_numeric($in[$k])?(float)$in[$k]:$v; }
            elseif (str_starts_with($v,'#')) { $out[$k]=sanitize_hex_color($in[$k]) ?: $v; }
            else { $out[$k]=sanitize_text_field($in[$k]); }
        }
        $enums=['template'=>['classic','minimal','dark','grid','compact','embed'],'sort'=>['latest','oldest','highest','lowest','random','manual','featured'],'expansion'=>['inline','modal','full'],'summary_position'=>['above','left','card'],'heading_align'=>['left','center','right'],'text_align'=>['left','center','right']];
        foreach ($enums as $k=>$values) { if (!in_array($out[$k],$values,true)) { $out[$k]=self::defaults()[$k]; } }
        $ranges=['minimum'=>[0,5],'limit'=>[1,10000],'desktop'=>[1,6],'tablet'=>[1,4],'mobile'=>[1,2],'interval'=>[2000,60000],'duration'=>[0,2000],'max_chars'=>[0,10000],'max_lines'=>[1,50],'heading_size'=>[12,120],'subtitle_size'=>[10,60],'name_size'=>[10,50],'text_size'=>[10,50],'date_size'=>[10,40],'font_weight'=>[100,900],'line_height'=>[1,3],'letter_spacing'=>[-2,10],'width'=>[280,2400],'padding'=>[0,200],'card_padding'=>[8,100],'gap'=>[4,100],'radius'=>[0,100],'border_width'=>[0,10],'avatar'=>[24,150],'min_height'=>[150,1000]];
        foreach ($ranges as $k=>[$min,$max]) { $out[$k]=max($min,min($max,$out[$k])); }
        if (!preg_match('/^[a-zA-Z0-9 ,"\'-]{1,150}$/',$out['font'])) { $out['font']='inherit'; }
        return $out;
    }
    public static function all(): array { global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}grw_widgets ORDER BY id DESC",ARRAY_A); }
    public static function get(int $id): ?array { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}grw_widgets WHERE id=%d",$id),ARRAY_A); }
    public static function save(array $in) {
        global $wpdb; $id=absint($in['id']??0); if ($id && !self::get($id)) { return Security::error('Widget not found',404); }
        if (!is_array($in['config']??[])) { return Security::error('Widget config must be an object'); }
        $name=sanitize_text_field($in['name']??''); if (!$name) { return Security::error('Widget name is required'); }
        $config=self::sanitize($in['config']??[]); $row=['name'=>$name,'config'=>wp_json_encode($config)];
        $ok=$id?$wpdb->update($wpdb->prefix.'grw_widgets',$row,['id'=>$id]):$wpdb->insert($wpdb->prefix.'grw_widgets',$row);
        return $ok===false?Security::error('Could not save widget',500):($id ?: (int)$wpdb->insert_id);
    }
}
