<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Renderer {
    public static function assets(): void {
        wp_enqueue_style('grw-front',plugins_url('assets/frontend.css',GRW_FILE),[],GRW_VERSION);
        wp_enqueue_script('grw-front',plugins_url('assets/frontend.js',GRW_FILE),[],GRW_VERSION,true);
        wp_localize_script('grw-front','GRW_STRINGS',['close'=>__('Close','google-reviews-for-wordpress'),'review'=>__('Review','google-reviews-for-wordpress'),'of'=>__('of','google-reviews-for-wordpress')]);
    }
    public static function render(int $id,array $override=[],bool $preview=false,bool $live=false): string {
        $widget=Widgets::get($id); if (!$widget && $preview && $id===0) { $widget=['name'=>__('Draft preview','google-reviews-for-wordpress'),'config'=>wp_json_encode(Widgets::defaults())]; } if (!$widget) { return ''; }
        $stored=json_decode($widget['config'],true)?:[]; if (!array_key_exists('empty_mode',$stored)) { $stored['empty_mode']='message'; }
        $c=Widgets::sanitize(array_merge($stored,$override)); if (!$c['active']) { return ''; } self::assets();
        if (!$c['locations']) { $c['locations']=array_map(fn($loc)=>(int)$loc['id'],array_filter(Locations::all(),fn($loc)=>!empty($loc['active']))); }
        $places=array_filter($c['locations'],function($locid){ $l=Locations::get($locid); $d=$l?json_decode($l['data'],true):[]; return !empty($d['active']) && ($d['provider']??'')==='google_places'; });
        if ($places && !$preview && !$live && $c['template']!=='embed') { ob_start(); if (did_action('wp_head') && !wp_style_is('grw-front','done')) { wp_print_styles('grw-front'); } $styles=ob_get_clean(); return $styles.'<div class="grw-live" data-grw-live="'.esc_attr(wp_json_encode(['id'=>$id,'token'=>Places::token($id,Security::can()),'admin_preview'=>Security::can(),'limit'=>$c['limit'],'empty'=>$c['empty_mode']==='message'?$c['empty_message']:'','endpoint'=>rest_url('grw/v1/live-widget')])).'" aria-live="polite"></div>'; }
        $query=$c; if ($c['source']==='connected') { $query['source']='licensed'; $query['connected']=true; $query['locations']=array_values(array_filter($c['locations'],function($locid){$l=Locations::get($locid);$d=$l?json_decode($l['data'],true):[];return Sources::capabilities($d['provider']??'public')['storage']==='licensed_permanent';})); }
        $rows=$c['source']==='connected'&&!$query['locations']?[]:Reviews::query($query,1,(int)$c['limit']);
        if ($places && $c['template']!=='embed') {
            // Distinguish Google content from retained legacy records: never mix them in one collection.
            $rows=[]; foreach (array_slice(array_values($places),0,5) as $locid) { $response=Places::retrieve($locid); if (is_wp_error($response)) { break; } array_push($rows,...$response['reviews']); }
            $rows=array_values(array_filter($rows,fn($r)=>$r['rating'] >= $c['minimum']));
            $sort=$c['sort']; if (in_array($sort,['latest','oldest','highest','lowest'],true)) { usort($rows,fn($a,$b)=>($sort==='latest'?strcmp($b['review_date'],$a['review_date']):($sort==='oldest'?strcmp($a['review_date'],$b['review_date']):($sort==='highest'?$b['rating']<=>$a['rating']:$a['rating']<=>$b['rating'])))); }
            if ($sort==='random') { shuffle($rows); } $rows=array_slice($rows,0,$c['limit']);
            $c['show_map']=false; $c['show_maps']=false; $c['show_summary']=false; $c['show_name']=true; $c['show_avatar']=true; $c['show_date']=true; $c['selected']=[]; if (in_array($c['sort'],['manual','featured'],true)) { $c['sort']='Google relevance'; }
        }
        if (!$rows && !$preview && $c['template']!=='embed' && $c['empty_mode']==='hide') { return ''; }

        $vars=[];
        foreach (['section','card','border','heading_color','subtitle_color','text','name_color','muted','stars','link','arrow','arrow_bg','dot','dot_active','font','heading_align','text_align','font_weight','line_height'] as $key) { $vars[]='--'.$key.':'.$c[$key]; }
        foreach (['heading_size','subtitle_size','name_size','text_size','date_size','summary_width','width','padding','card_padding','gap','radius','border_width','avatar','min_height','letter_spacing'] as $key) { $vars[]='--'.$key.':'.$c[$key].'px'; }
        foreach (['desktop','tablet','mobile','max_lines'] as $key) { $vars[]='--'.$key.':'.$c[$key]; }
        $vars[]='--duration:'.$c['duration'].'ms';
        $settings=get_option('grw_settings',[]);
        $tracking=!$preview && !Security::can() && !empty($settings['analytics']) && $c['analytics'];
        $opts=array_intersect_key($c,array_flip(['swipe','mouse_drag','swipe_sensitivity','random_start','autoplay','interval','duration','loop','pause_hover','pause_interaction','peek','expansion']));
        $opts+=['widget'=>$id,'track'=>$tracking,'consent'=>!empty($settings['consent_required']),'endpoint'=>rest_url('grw/v1/events'),'locations'=>array_values(array_unique(array_column($rows,'location_id')))];
        ob_start(); if (did_action('wp_head') && !wp_style_is('grw-front','done')) { wp_print_styles('grw-front'); } ?>
        <section class="grw grw-<?php echo esc_attr($c['template']); ?> <?php echo ($c['shadow']?'grw-shadow ':'').(!$c['equal_height']?'grw-unequal ':'').($c['peek']?'grw-peek ':'').(!$c['show_avatar']?'grw-no-avatar ':'').($c['full_width']?'grw-full ':'').$c['custom_class']; ?>" style="<?php echo esc_attr(implode(';',$vars)); ?>" data-grw="<?php echo esc_attr(wp_json_encode($opts)); ?>" aria-label="<?php echo esc_attr($widget['name']); ?>">
        <div class="grw-inner">
        <?php if ($c['show_subtitle']) { ?><p class="grw-subtitle"><?php echo esc_html($c['subtitle']); ?></p><?php } ?>
        <?php if ($c['show_heading']) { ?><h2 class="grw-heading"><?php echo esc_html($c['heading']); ?></h2><?php } ?>
        <?php if ($c['template']==='embed') {
            foreach ($c['locations'] as $locid) { echo MapDisplay::render($locid,(int)$c['map_height']); }
        } else {
            if ($c['show_summary'] && $rows) { $rated=array_filter(array_column($rows,'rating'),fn($v)=>$v!==null); ?><aside class="grw-summary grw-summary-<?php echo esc_attr($c['summary_position']); ?>"><?php if ($rated) { echo esc_html(number_format_i18n(array_sum($rated)/count($rated),1).' / 5 · '); } echo esc_html(sprintf(__('Based on %d displayed reviews (selected subset)', 'google-reviews-for-wordpress'),count($rows))); ?></aside><?php }
            if (!$rows) { ?><p><?php echo esc_html($preview?'No reviews connected yet. Connect a supported review source.':$c['empty_message']); ?></p><?php }
            else { ?>
            <div class="grw-slider" role="region" aria-roledescription="<?php esc_attr_e('carousel', 'google-reviews-for-wordpress'); ?>" tabindex="0">
            <div class="grw-viewport"><div class="grw-track">
            <?php foreach ($rows as $row) { self::card($row,$c); } ?>
            </div></div>
            <?php if ($c['show_arrows'] && $c['template']!=='grid') { ?><button type="button" class="grw-arrow grw-prev" aria-label="<?php esc_attr_e('Previous', 'google-reviews-for-wordpress'); ?>">‹</button><button type="button" class="grw-arrow grw-next" aria-label="<?php esc_attr_e('Next', 'google-reviews-for-wordpress'); ?>">›</button><?php } ?>
            <p class="grw-sr grw-status" aria-live="polite"></p>
            <?php if ($c['show_dots'] && $c['template']!=='grid') { ?><div class="grw-dots"></div><?php } ?>
            </div>
            <?php }
        }
        if ($places && $rows) { echo '<p class="grw-attribution">Google-selected subset, up to 5 reviews per location. Display order: '.esc_html($c['sort']).'; minimum rating: '.absint($c['minimum']).'. <a href="https://support.google.com/contributionpolicy/answer/7400114" target="_blank" rel="noopener noreferrer">Google review policy</a></p>'; }
        if ($c['show_map'] && $c['template']!=='embed') { foreach ($c['locations'] as $locid) { echo MapDisplay::render($locid,(int)$c['map_height']); } }
        if ($c['show_maps']) { foreach ($c['locations'] as $locid) { $loc=Locations::get($locid); $d=$loc?json_decode($loc['data'],true):[]; if (!empty($d['maps_url'])) { ?><a class="grw-maps" data-event="maps" href="<?php echo esc_url($d['maps_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(sprintf(__('View %s on Google Maps', 'google-reviews-for-wordpress'),$loc['name'])); ?></a><?php } } }
        ?>
        </div></section>
        <?php return ob_get_clean();
    }
    private static function card(array $r,array $c): void {
        $google=$r['source_type']==='google_places'; if ($google) { $c['show_name']=true; $c['show_avatar']=true; $c['show_date']=true; }
        $licensed=$r['source_type']==='licensed';
        $text=$r['content']; $short=$c['expansion']!=='full' && $c['max_chars']>0 && mb_strlen($text)>(int)$c['max_chars'];
        $excerpt=$short?mb_substr($text,0,(int)$c['max_chars']).'…':$text;
        ?>
        <article class="grw-card" data-review="<?php echo absint($r['id']); ?>" data-location="<?php echo absint($r['location_id']); ?>">
        <?php if ($c['show_avatar']) { ?><div class="grw-avatar"><?php if ($r['avatar']) { ?><img loading="lazy" referrerpolicy="no-referrer" src="<?php echo esc_url($r['avatar']); ?>" alt="" width="<?php echo absint($c['avatar']); ?>" height="<?php echo absint($c['avatar']); ?>"><?php } ?><span><?php echo esc_html(mb_substr($r['reviewer'],0,1)); ?></span></div><?php } ?>
        <?php if ($c['show_name']) { ?><h3 class="grw-name"><?php if ($google && $r['author_url']) { ?><a href="<?php echo esc_url($r['author_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($r['reviewer']); ?></a><?php } else { echo esc_html($r['reviewer']); } ?></h3><?php } ?>
        <?php if ($c['show_date'] && $r['review_date']) { ?><time class="grw-date" datetime="<?php echo esc_attr(gmdate('c',strtotime($r['review_date'].' UTC'))); ?>"><?php echo esc_html(strtotime($r['review_date'].' UTC')<=time()?sprintf(__('%s ago','google-reviews-for-wordpress'),human_time_diff(strtotime($r['review_date'].' UTC'),time())):mysql2date(get_option('date_format'),$r['review_date'])); ?></time><?php } ?>
        <?php if ($c['show_stars'] && $r['rating']!==null) { ?><div class="grw-stars" role="img" aria-label="<?php echo esc_attr(sprintf(__('%d out of 5 stars', 'google-reviews-for-wordpress'),$r['rating'])); ?>"><?php echo esc_html(str_repeat('★',(int)$r['rating']).str_repeat('☆',5-(int)$r['rating'])); ?></div><?php } ?>
        <p <?php echo !$c['show_text']?'hidden':''; ?> class="grw-content<?php echo $c['expansion']==='full'?' grw-expanded':''; ?>" data-full="<?php echo esc_attr($text); ?>" data-short="<?php echo esc_attr($excerpt); ?>"><?php echo esc_html($excerpt); ?></p>
        <?php if ($c['show_text'] && $c['expansion']!=='full' && $c['show_readmore']) { ?><button class="grw-more" type="button" aria-expanded="false" data-more="<?php esc_attr_e('Read more', 'google-reviews-for-wordpress'); ?>" data-less="<?php esc_attr_e('Read less', 'google-reviews-for-wordpress'); ?>"><?php esc_html_e('Read more', 'google-reviews-for-wordpress'); ?></button><?php } ?>
        <footer class="grw-source<?php echo $google?' grw-source-google':''; ?>"><?php echo esc_html(($google?'':($r['source_type']==='manual'?__('Manual testimonial', 'google-reviews-for-wordpress'):($licensed?__('Licensed connected source', 'google-reviews-for-wordpress'):__('Legacy / authorized source', 'google-reviews-for-wordpress')))).($google?'':' · ').$r['source_name']); ?><?php if ($r['permalink'] || $r['source_url']) { ?> · <a data-event="outbound" href="<?php echo esc_url($r['permalink'] ?: $r['source_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View original', 'google-reviews-for-wordpress'); ?></a><?php } ?></footer>
        <?php if ($google) { if ($r['visit_date']) { echo '<p class="grw-date">Visited: '.esc_html($r['visit_date']).'</p>'; } foreach ($r['attributions'] as $attribution) { if (is_array($attribution) && is_string($attribution['provider']??null)) { echo '<p class="grw-source"><a href="'.esc_url(Security::url($attribution['providerUri']??'')).'" target="_blank" rel="noopener noreferrer">'.esc_html($attribution['provider']).'</a></p>'; } } } ?>
        <?php if ($r['response']) { ?><details><summary><?php esc_html_e('Business response', 'google-reviews-for-wordpress'); ?></summary><?php echo esc_html($r['response']); ?></details><?php } ?>
        </article>
        <?php
    }
}
