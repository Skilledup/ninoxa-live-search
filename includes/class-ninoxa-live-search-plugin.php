<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ninoxa-live-search-assets.php';
require_once __DIR__ . '/class-ninoxa-live-search-search.php';
require_once __DIR__ . '/class-ninoxa-live-search-settings.php';

/**
 * Plugin bootstrap that wires feature modules together.
 */
class Ninoxa_Live_Search_Plugin {
	/**
	 * Registered plugin components.
	 *
	 * @var array<int, object>
	 */
	private $components = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->components[] = new Ninoxa_Live_Search_Assets();
		$this->components[] = new Ninoxa_Live_Search_Search();

		if ( is_admin() ) {
			$this->components[] = new Ninoxa_Live_Search_Settings();
		}
	}

	/**
	 * Register all plugin hooks.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->components as $component ) {
			if ( method_exists( $component, 'register' ) ) {
				$component->register();
			}
		}
	}
}