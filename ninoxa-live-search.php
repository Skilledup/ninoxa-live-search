<?php
/*
Plugin Name: Ninoxa Live Search
Plugin URI: https://wordpress.org/plugins/ninoxa-live-search/
Description: A plugin to add live search functionality to your WordPress site.
Version: 1.1.0
Author: Mohammad Anbarestany
Author URI: https://profiles.wordpress.org/macse2/
Text Domain: ninoxa-live-search
Domain Path: /languages
License: GPL-3.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NINOXA_LIVE_SEARCH_VERSION' ) ) {
	define( 'NINOXA_LIVE_SEARCH_VERSION', '1.1.0' );
}

if ( ! defined( 'NINOXA_LIVE_SEARCH_PATH' ) ) {
	define( 'NINOXA_LIVE_SEARCH_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'NINOXA_LIVE_SEARCH_URL' ) ) {
	define( 'NINOXA_LIVE_SEARCH_URL', plugin_dir_url( __FILE__ ) );
}

require_once __DIR__ . '/includes/class-ninoxa-live-search-options.php';
require_once __DIR__ . '/includes/class-ninoxa-live-search-assets.php';
require_once __DIR__ . '/includes/class-ninoxa-live-search-search.php';
require_once __DIR__ . '/includes/class-ninoxa-live-search-settings.php';
require_once __DIR__ . '/includes/class-ninoxa-live-search-plugin.php';

/**
 * Retrieve the shared plugin instance.
 *
 * @return Ninoxa_Live_Search_Plugin
 */
function ninoxa_live_search() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new Ninoxa_Live_Search_Plugin();
	}

	return $plugin;
}

ninoxa_live_search()->run();
