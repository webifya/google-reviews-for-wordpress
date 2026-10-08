<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
if (is_multisite()) { return; } // Multisite requires deliberate per-site cleanup.
$grw_settings=get_option('grw_settings',[]);
if (empty($grw_settings['delete_data'])) { return; }
global $wpdb;
foreach (['locations','reviews','widgets','analytics','logs'] as $grw_table) { $wpdb->query('DROP TABLE IF EXISTS '.$wpdb->prefix.'grw_'.$grw_table); }
foreach (['grw_settings','grw_db_version','grw_onboarding'] as $grw_option) { delete_option($grw_option); }
$grw_options=$wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'grw\\_lock\\_%' OR option_name LIKE 'grw\\_cursor\\_%' OR option_name LIKE '\\_transient\\_grw\\_%' OR option_name LIKE '\\_transient\\_timeout\\_grw\\_%'");
foreach ($grw_options as $grw_option) { delete_option($grw_option); }
wp_clear_scheduled_hook('grw_tick');
