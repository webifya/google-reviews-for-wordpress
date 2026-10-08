<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Installer {
    public static function activate(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate(); $p = $wpdb->prefix . 'grw_';
        dbDelta("CREATE TABLE {$p}locations (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 name varchar(190) NOT NULL,
 active tinyint NOT NULL DEFAULT 1,
 data longtext NOT NULL,
 status varchar(40) NOT NULL DEFAULT 'manual_import_only',
 last_attempt datetime NULL,
 last_success datetime NULL,
 next_sync bigint unsigned NOT NULL DEFAULT 0,
 failures int unsigned NOT NULL DEFAULT 0,
 PRIMARY KEY  (id)
) $c;");
        dbDelta("CREATE TABLE {$p}reviews (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 location_id bigint unsigned NOT NULL,
 identity char(64) NOT NULL,
 external_id varchar(190) NOT NULL DEFAULT '',
 reviewer varchar(190) NOT NULL,
 avatar text NOT NULL,
 rating tinyint unsigned NULL,
 content longtext NOT NULL,
 review_date datetime NULL,
 permalink text NOT NULL,
 source_name varchar(190) NOT NULL,
 source_url text NOT NULL,
 source_type varchar(32) NOT NULL,
 provider_id varchar(64) NOT NULL DEFAULT '',
 source_binding char(64) NOT NULL DEFAULT '',
 license_reference varchar(2048) NOT NULL DEFAULT '',
 synced_at datetime NULL,
 response longtext NOT NULL,
 content_hash char(64) NOT NULL,
 published tinyint NOT NULL DEFAULT 1,
 featured tinyint NOT NULL DEFAULT 0,
 imported_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY identity (identity),
 KEY location_status (location_id,published),
 KEY rating_date (rating,review_date),
 KEY location_date (location_id,published,review_date,id),
 KEY published_date (published,review_date,id),
 KEY source_date (source_type,review_date,id),
 KEY provider_binding (location_id,provider_id,source_binding,published),
 KEY provider_external (provider_id,external_id),
 KEY synchronized_at (synced_at)
) $c;");
        dbDelta("CREATE TABLE {$p}widgets (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 name varchar(190) NOT NULL,
 config longtext NOT NULL,
 PRIMARY KEY  (id)
) $c;");
        dbDelta("CREATE TABLE {$p}analytics (
 day date NOT NULL,
 widget_id bigint unsigned NOT NULL,
 location_id bigint unsigned NOT NULL DEFAULT 0,
 event varchar(32) NOT NULL,
 total bigint unsigned NOT NULL DEFAULT 0,
 PRIMARY KEY  (day,widget_id,location_id,event)
) $c;");
        dbDelta("CREATE TABLE {$p}logs (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 location_id bigint unsigned NOT NULL DEFAULT 0,
 message text NOT NULL,
 created_at datetime NOT NULL,
 PRIMARY KEY  (id),
 KEY created_at (created_at)
) $c;");
        update_option('grw_db_version', GRW_VERSION, false);
        add_option('grw_settings', ['analytics'=>true,'retention'=>90,'delete_data'=>false,'consent_required'=>true]);
        add_option('grw_onboarding', true);
        if (!wp_next_scheduled('grw_tick')) { wp_schedule_event(time()+300, 'hourly', 'grw_tick'); }
    }
    public static function deactivate(): void { wp_clear_scheduled_hook('grw_tick'); }
}
