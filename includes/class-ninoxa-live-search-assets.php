<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register frontend plugin assets.
 */
class Ninoxa_Live_Search_Assets {
	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script(
			'live-search',
			NINOXA_LIVE_SEARCH_URL . 'assets/js/live-search.js',
			array( 'jquery' ),
			NINOXA_LIVE_SEARCH_VERSION,
			true
		);

		wp_enqueue_style(
			'live-search-style',
			NINOXA_LIVE_SEARCH_URL . 'assets/css/style.css',
			array(),
			NINOXA_LIVE_SEARCH_VERSION
		);

		wp_localize_script(
			'live-search',
			'liveSearchData',
			array(
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'live_search_nonce' ),
				'refresh_nonce_action' => 'live_search_refresh_nonce',
				'settings'             => array(
					'keyboardShortcut'      => Ninoxa_Live_Search_Options::get_keyboard_shortcut(),
					'keyboardShortcutLabel' => Ninoxa_Live_Search_Options::get_keyboard_shortcut_label(),
				),
				'i18n'                 => array(
					'search_suggestions'    => __( 'Search suggestions', 'ninoxa-live-search' ),
					'one_suggestion'        => __( '1 suggestion available', 'ninoxa-live-search' ),
					/* translators: %d is the number of suggestions available. */
					'suggestions_available' => __( '%d suggestions available', 'ninoxa-live-search' ),
					'search_unavailable'    => __( 'Search temporarily unavailable. Please try again.', 'ninoxa-live-search' ),
					'nonce_refresh_failed'  => __( 'Search security token refresh failed', 'ninoxa-live-search' ),
					'search_failed'         => __( 'Search request failed', 'ninoxa-live-search' ),
				),
			)
		);
	}
}