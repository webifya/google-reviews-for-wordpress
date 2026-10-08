<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
global $wpdb;
delete_option('grw_google_private');
update_option('grw_google_cache_generation',wp_generate_uuid4(),false);
$grw_google_names=$wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",$wpdb->esc_like('_transient_grw_gbp_').'%', $wpdb->esc_like('_transient_timeout_grw_gbp_').'%'));
foreach ($grw_google_names as $grw_google_name) { delete_option($grw_google_name); }
if (is_multisite()) { return; } // Multisite requires deliberate per-site cleanup.
$grw_settings=get_option('grw_settings',[]);
if (empty($grw_settings['delete_data'])) { return; }
global $wpdb;
foreach (['locations','reviews','widgets','analytics','logs'] as $grw_table) { $wpdb->query('DROP TABLE IF EXISTS '.$wpdb->prefix.'grw_'.$grw_table); }
foreach (['grw_settings','grw_db_version','grw_onboarding','grw_cache_generation','grw_google_cache_generation'] as $grw_option) { delete_option($grw_option); }
$grw_options=$wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'grw\\_lock\\_%' OR option_name LIKE 'grw\\_cursor\\_%' OR option_name LIKE '\\_transient\\_grw\\_%' OR option_name LIKE '\\_transient\\_timeout\\_grw\\_%'");
foreach ($grw_options as $grw_option) { delete_option($grw_option); }
wp_clear_scheduled_hook('grw_tick');
