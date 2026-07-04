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

		// Output CSS custom properties for spinner color/size so JS does not need to
		// touch inline styles and themes cannot override the scoped variables.
		$size_map = array(
			'small'  => '14px',
			'medium' => '16px',
			'large'  => '20px',
		);
		$spinner_color    = sanitize_hex_color( Ninoxa_Live_Search_Options::get( 'loading_spinner_color' ) );
		$spinner_color    = $spinner_color ? $spinner_color : '#3498db';
		$spinner_size_key = Ninoxa_Live_Search_Options::get( 'loading_spinner_size' );
		$spinner_size     = isset( $size_map[ $spinner_size_key ] ) ? $size_map[ $spinner_size_key ] : '16px';
		$spinner_offset   = absint( Ninoxa_Live_Search_Options::get( 'loading_spinner_offset' ) );
		$spinner_offset   = $spinner_offset ? $spinner_offset : 12;
		$sweep_speed      = Ninoxa_Live_Search_Options::get( 'loading_sweep_speed' );
		$allowed_speeds   = array( '0.8s', '1.4s', '2.0s' );
		if ( ! in_array( $sweep_speed, $allowed_speeds, true ) ) {
			$sweep_speed = '1.4s';
		}

		wp_add_inline_style(
			'live-search-style',
			':root{--ninoxa-spinner-color:' . $spinner_color . ';--ninoxa-spinner-size:' . $spinner_size . ';--ninoxa-spinner-offset:' . $spinner_offset . 'px;--ninoxa-sweep-duration:' . $sweep_speed . ';}'
		);

		wp_localize_script(
			'live-search',
			'liveSearchData',
			array(
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'live_search_nonce' ),
				'refresh_nonce_action' => 'live_search_refresh_nonce',
				'settings'             => array(
					'keyboardShortcut'       => Ninoxa_Live_Search_Options::get_keyboard_shortcut(),
					'keyboardShortcutLabel'  => Ninoxa_Live_Search_Options::get_keyboard_shortcut_label(),
					'typeToSearchEnabled'    => '1' === Ninoxa_Live_Search_Options::get( 'type_to_search_enabled' ),
					'loadingSpinnerEnabled'  => '0' !== Ninoxa_Live_Search_Options::get( 'loading_spinner_enabled' ),
					'loadingSpinnerPosition' => Ninoxa_Live_Search_Options::get( 'loading_spinner_position' ) ?: 'right',
					'loadingSweepEnabled'    => '0' !== Ninoxa_Live_Search_Options::get( 'loading_sweep_enabled' ),
					'matching'               => array(
						'enabled'     => '1' === Ninoxa_Live_Search_Options::get( 'search_matching_enabled' ),
						'defaultMode' => Ninoxa_Live_Search_Options::get_default_match_mode(),
						'modes'       => Ninoxa_Live_Search_Options::get_enabled_match_modes(),
					),
				),
				'i18n'                 => array(
					'search_suggestions'    => __( 'Search suggestions', 'ninoxa-live-search' ),
					'one_suggestion'        => __( '1 suggestion available', 'ninoxa-live-search' ),
					/* translators: %d is the number of suggestions available. */
					'suggestions_available' => __( '%d suggestions available', 'ninoxa-live-search' ),
					'search_unavailable'    => __( 'Search temporarily unavailable. Please try again.', 'ninoxa-live-search' ),
					'nonce_refresh_failed'  => __( 'Search security token refresh failed', 'ninoxa-live-search' ),
					'search_failed'         => __( 'Search request failed', 'ninoxa-live-search' ),
					'matching_label'        => __( 'Matching', 'ninoxa-live-search' ),
				),
			)
		);
	}
}