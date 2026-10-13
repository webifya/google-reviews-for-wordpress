<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Project {
    public const PLACE_ID='ChIJy3bcVRzl9IgR5eOJcFC6ZCQ';
    public static function location(): ?array { foreach (Locations::all() as $l) { $d=json_decode($l['data'],true)?:[]; if (($d['place_id']??'')===self::PLACE_ID && ($d['provider']??'')==='google_business') { return $l; } } return null; }
    public static function config(array $in): array {
        $c=Widgets::sanitize($in); $l=self::location(); $c['locations']=$l?[(int)$l['id']]:[]; $c['source']='connected';$c['selected']=[];$c['analytics']=false;$c['show_summary']=false;$c['show_map']=false;$c['show_maps']=false;
        if ($c['template']==='embed') { $c['template']='classic'; } if (in_array($c['sort'],['manual','featured'],true)) { $c['sort']='latest'; } return $c;
    }
    public static function widget(): ?array {
        $saved=Widgets::get(absint(get_option('grw_menjar_widget_id',0))); if ($saved) { return $saved; }
        $l=self::location(); $matching=[];foreach (Locations::all() as $loc) { $d=json_decode($loc['data'],true)?:[]; if (($d['place_id']??'')===self::PLACE_ID) { $matching[]=(int)$loc['id']; } }
        $all=Widgets::all();foreach ($all as $w) { $c=json_decode($w['config'],true)?:[];if (array_intersect(Security::ids($c['locations']??[]),$matching)) { return $w; } }return $all[0]??null;
    }
    public static function defaults(): array { return array_merge(Widgets::defaults(),['limit'=>10000,'analytics'=>false]); }
}
