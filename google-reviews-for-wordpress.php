<?php
/**
 * Plugin Name: Google Reviews for WordPress
 * Description: Local review carousels, analytics and an experimental Google Maps browser collector with a bundled browser collector.
 * Version: 1.5.0-rc.2
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Webifya
 * License: GPL-2.0-or-later
 * Text Domain: google-reviews-for-wordpress
 */
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
define('GRW_FILE', __FILE__);
define('GRW_VERSION', '1.5.0-rc.2');
foreach (['Security','Cache','Installer','Locations','Reviews','Sources','LocalCollector','Scraper','GoogleBusiness','Places','Sync','Widgets','MapDisplay','Renderer','Analytics','Admin','Plugin'] as $class) { require_once __DIR__ . '/includes/' . $class . '.php'; }
register_activation_hook(__FILE__, [Installer::class, 'activate']);
register_deactivation_hook(__FILE__, [Installer::class, 'deactivate']);
add_action('plugins_loaded', [Plugin::class, 'boot']);
