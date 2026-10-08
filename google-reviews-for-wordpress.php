<?php
/**
 * Plugin Name: Google Reviews for WordPress
 * Description: Official maps embeds and authorized review carousels, imports, source adapters and privacy-conscious analytics.
 * Version: 1.1.0-rc.1
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Webifya
 * License: GPL-2.0-or-later
 * Text Domain: google-reviews-for-wordpress
 */
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
define('GRW_FILE', __FILE__);
define('GRW_VERSION', '1.1.0-rc.1');
foreach (['Security','Cache','Installer','Locations','Reviews','Sources','GoogleBusiness','Sync','Widgets','Renderer','Analytics','Admin','Plugin'] as $class) { require_once __DIR__ . '/includes/' . $class . '.php'; }
register_activation_hook(__FILE__, [Installer::class, 'activate']);
register_deactivation_hook(__FILE__, [Installer::class, 'deactivate']);
add_action('plugins_loaded', [Plugin::class, 'boot']);
