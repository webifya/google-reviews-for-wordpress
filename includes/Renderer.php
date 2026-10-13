<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Renderer {
    public static function assets(): void {
        wp_enqueue_style('grw-front',plugins_url('assets/frontend.css',GRW_FILE),[],GRW_VERSION);
        wp_enqueue_script('grw-front',plugins_url('assets/frontend.js',GRW_FILE),[],GRW_VERSION,true);
        wp_localize_script('grw-front','GRW_STRINGS',['close'=>__('Close','google-reviews-for-wordpress'),'review'=>__('Review','google-reviews-for-wordpress'),'of'=>__('of','google-reviews-for-wordpress')]);
    }
    public static function render(int $id,array $override=[],bool $preview=false,bool $live=false,?array $businessRows=null): string {
        $widget=$id?Widgets::get($id):Project::widget(); if (!$widget && $preview) { $widget=['name'=>'Menjar Financial reviews','config'=>wp_json_encode(Project::defaults())]; } if (!$widget) { return ''; }
        $c=Project::config(array_merge(json_decode($widget['config'],true)?:[], $override)); if (!$c['active']) { return ''; } self::assets();
        $location=Project::location(); $business=$location?[(int)$location['id']]:[];
        if (!$preview && !$live) {
            if (!$location || !GoogleBusiness::public_locations($c)) { return ''; }
            ob_start(); if (did_action('wp_head') && !wp_style_is('grw-front','done')) { wp_print_styles('grw-front'); } $styles=ob_get_clean();
            return $styles.'<div class="grw-live" data-grw-live="'.esc_attr(wp_json_encode(['id'=>$id?:$widget['id'],'token'=>GoogleBusiness::token($id?:$widget['id']),'sort'=>$c['sort'],'limit'=>$c['limit'],'empty'=>$c['empty_mode']==='message'?$c['empty_message']:'','endpoint'=>rest_url('grw/v1/business-widget')])).'" aria-live="polite"></div>';
        }
        $rows=$businessRows??[];
        if ($preview && $businessRows===null && $location) { $page=GoogleBusiness::reviews((int)$location['id']); if (!is_wp_error($page)) { $part=GoogleBusiness::rows((int)$location['id'],$page); if (!is_wp_error($part)) { $rows=array_slice(array_values(array_filter($part,fn($r)=>$r['rating']>=$c['minimum'])),0,$c['limit']); } } }
        if (!$rows && !$preview && $c['empty_mode']==='hide') { return ''; }
        $c['show_name']=true;$c['show_avatar']=true;$c['show_date']=true;
        $vars=[];
        foreach (['section','card','border','heading_color','subtitle_color','text','name_color','muted','stars','link','arrow','arrow_bg','dot','dot_active','font','heading_align','text_align','font_weight','line_height'] as $key) { $vars[]='--'.$key.':'.$c[$key]; }
        foreach (['heading_size','subtitle_size','name_size','text_size','date_size','summary_width','width','padding','card_padding','gap','radius','border_width','avatar','min_height','letter_spacing'] as $key) { $vars[]='--'.$key.':'.$c[$key].'px'; }
        foreach (['desktop','tablet','mobile','max_lines'] as $key) { $vars[]='--'.$key.':'.$c[$key]; }
        $vars[]='--duration:'.$c['duration'].'ms';
        $opts=array_intersect_key($c,array_flip(['swipe','mouse_drag','swipe_sensitivity','random_start','autoplay','interval','duration','loop','pause_hover','pause_interaction','peek','expansion']));
        $opts+=['widget'=>$id];
        ob_start(); if (did_action('wp_head') && !wp_style_is('grw-front','done')) { wp_print_styles('grw-front'); } ?>
        <section class="grw grw-<?php echo esc_attr($c['template']); ?> <?php echo ($c['shadow']?'grw-shadow ':'').(!$c['equal_height']?'grw-unequal ':'').($c['peek']?'grw-peek ':'').(!$c['show_avatar']?'grw-no-avatar ':'').($c['full_width']?'grw-full ':'').$c['custom_class']; ?>" style="<?php echo esc_attr(implode(';',$vars)); ?>" data-grw="<?php echo esc_attr(wp_json_encode($opts)); ?>" aria-label="<?php echo esc_attr($widget['name']); ?>">
        <div class="grw-inner">
        <?php if ($c['show_subtitle']) { ?><p class="grw-subtitle"><?php echo esc_html($c['subtitle']); ?></p><?php } ?>
        <?php if ($c['show_heading']) { ?><h2 class="grw-heading"><?php echo esc_html($c['heading']); ?></h2><?php } ?>
        <?php {
            if ($c['show_summary'] && $rows) { $rated=array_filter(array_column($rows,'rating'),fn($v)=>$v!==null); ?><aside class="grw-summary grw-summary-<?php echo esc_attr($c['summary_position']); ?>"><?php if ($rated) { echo esc_html(number_format_i18n(array_sum($rated)/count($rated),1).' / 5 · '); } echo esc_html(sprintf(__('Based on %d displayed reviews (selected subset)', 'google-reviews-for-wordpress'),count($rows))); ?></aside><?php }
            if (!$rows) { ?><p><?php echo esc_html($preview?'Connect Menjar Financial to preview its Google reviews.':$c['empty_message']); ?></p><?php }
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
        if ($businessRows!==null && $rows) { echo '<p class="grw-attribution">Google reviews · Business Profile API. Showing the configured selection; review limit '.absint($c['limit']).', minimum rating '.absint($c['minimum']).'.</p>'; }
        ?>
        </div></section>
        <?php return ob_get_clean();
    }
    private static function card(array $r,array $c): void {
        $google=true; if ($google) { $c['show_name']=true; $c['show_avatar']=true; $c['show_date']=true; }
        $text=$r['content']; $short=$c['expansion']!=='full' && $c['max_chars']>0 && mb_strlen($text)>(int)$c['max_chars'];
        $excerpt=$short?mb_substr($text,0,(int)$c['max_chars']).'…':$text;
        ?>
        <article class="grw-card" data-review="<?php echo esc_attr('google:'.$r['location_id'].':'.$r['external_id']); ?>" data-external="<?php echo esc_attr($r['external_id']??''); ?>" data-rating="<?php echo absint($r['rating']); ?>" data-location="<?php echo absint($r['location_id']); ?>">
        <?php if ($c['show_avatar']) { ?><div class="grw-avatar"><?php if ($r['avatar']) { ?><img loading="lazy" referrerpolicy="no-referrer" src="<?php echo esc_url($r['avatar']); ?>" alt="" width="<?php echo absint($c['avatar']); ?>" height="<?php echo absint($c['avatar']); ?>"><?php } ?><span><?php echo esc_html(mb_substr($r['reviewer'],0,1)); ?></span></div><?php } ?>
        <?php if ($c['show_name']) { ?><h3 class="grw-name"><?php if ($google && $r['author_url']) { ?><a href="<?php echo esc_url($r['author_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($r['reviewer']); ?></a><?php } else { echo esc_html($r['reviewer']); } ?></h3><?php } ?>
        <?php if ($c['show_date'] && $r['review_date']) { ?><time class="grw-date" datetime="<?php echo esc_attr(gmdate('c',strtotime($r['review_date'].' UTC'))); ?>"><?php echo esc_html(strtotime($r['review_date'].' UTC')<=time()?sprintf(__('%s ago','google-reviews-for-wordpress'),human_time_diff(strtotime($r['review_date'].' UTC'),time())):mysql2date(get_option('date_format'),$r['review_date'])); ?></time><?php } ?>
        <?php if ($c['show_date'] && !$r['review_date'] && !empty($r['review_date_label'])) { ?><span class="grw-date"><?php echo esc_html($r['review_date_label']); ?></span><?php } ?>
        <?php if ($c['show_stars'] && $r['rating']!==null) { ?><div class="grw-stars" role="img" aria-label="<?php echo esc_attr(sprintf(__('%d out of 5 stars', 'google-reviews-for-wordpress'),$r['rating'])); ?>"><?php echo esc_html(str_repeat('★',(int)$r['rating']).str_repeat('☆',5-(int)$r['rating'])); ?></div><?php } ?>
        <p <?php echo !$c['show_text']?'hidden':''; ?> class="grw-content<?php echo $c['expansion']==='full'?' grw-expanded':''; ?>" data-full="<?php echo esc_attr($text); ?>" data-short="<?php echo esc_attr($excerpt); ?>"><?php echo esc_html($excerpt); ?></p>
        <?php if ($c['show_text'] && $c['expansion']!=='full' && $c['show_readmore']) { ?><button class="grw-more" type="button" aria-expanded="false" data-more="<?php esc_attr_e('Read more', 'google-reviews-for-wordpress'); ?>" data-less="<?php esc_attr_e('Read less', 'google-reviews-for-wordpress'); ?>"><?php esc_html_e('Read more', 'google-reviews-for-wordpress'); ?></button><?php } ?>
        <footer class="grw-source grw-source-google">Google · <a href="<?php echo esc_url($r['source_url']); ?>" target="_blank" rel="noopener noreferrer">View on Google Maps</a></footer>
        <?php if ($r['response']) { ?><details><summary><?php esc_html_e('Business response', 'google-reviews-for-wordpress'); ?></summary><?php echo esc_html($r['response']); ?></details><?php } ?>
        </article>
        <?php
    }
}
