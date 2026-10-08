<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class MapDisplay {
    public static function render(int $id,int $height=380): string {
        $location=Locations::get($id); if (!$location || empty($location['active'])) { return ''; }
        return self::preview(json_decode($location['data'],true)?:[], $location['name'], $height);
    }
    public static function preview(array $data,string $name,int $height=380): string {
        $src=Security::embed($data['embed_url']??''); $height=max(200,min(800,$height));
        wp_enqueue_style('grw-front',plugins_url('assets/frontend.css',GRW_FILE),[],GRW_VERSION);
        ob_start(); if (did_action('wp_head') && !wp_style_is('grw-front','done')) { wp_print_styles('grw-front'); }
        ?><section class="grw-map-display" style="--map-height:<?php echo $height; ?>px" aria-label="<?php echo esc_attr($name); ?>">
        <h3><?php echo esc_html(($data['business_name']??'')?:$name); ?></h3>
        <?php if (!empty($data['address'])) { ?><p><?php echo esc_html($data['address']); ?></p><?php }
        if ($src) { ?><iframe class="grw-map" src="<?php echo esc_url($src); ?>" title="<?php echo esc_attr(sprintf(__('%s — Google Maps','google-reviews-for-wordpress'),$name)); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe><?php }
        else { ?><p><?php esc_html_e('No official map embed has been added for this location.','google-reviews-for-wordpress'); ?></p><?php }
        $url=Locations::listing_url($data); if ($url) { ?><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View on Google Maps','google-reviews-for-wordpress'); ?></a><?php }
        $reviews=Security::maps($data['reviews_url']??''); if ($reviews) { ?> <a href="<?php echo esc_url($reviews); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View Google Reviews','google-reviews-for-wordpress'); ?></a><?php } ?>
        </section><?php return ob_get_clean();
    }
}
