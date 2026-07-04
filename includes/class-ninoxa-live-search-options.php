<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central access point for plugin options and defaults.
 */
class Ninoxa_Live_Search_Options {
	/**
	 * Stored option name.
	 */
	const OPTION_NAME = 'ninoxa_live_search_settings';

	/**
	 * Return the plugin defaults.
	 *
	 * @return array<string, string>
	 */
	public static function get_defaults() {
		return array(
			'keyboard_shortcut'        => 'ctrl+/',
			'type_to_search_enabled'   => '0',
			'search_results_limit'     => '10',
			'loading_spinner_enabled'  => '1',
			'loading_spinner_position' => 'right',
			'loading_spinner_color'    => '#3498db',
			'loading_spinner_size'     => 'medium',
			'loading_spinner_offset'   => '12',
			'loading_sweep_enabled'    => '1',
			'loading_sweep_speed'      => '1.4s',
			'search_matching_enabled'  => '1',
			'search_matching_default'  => 'keyword',
			'search_match_keyword'     => '1',
			'search_match_any'         => '0',
			'search_match_sentence'    => '1',
			'search_match_whole_word'  => '1',
			'search_match_fuzzy'       => '0',
		);
	}

	/**
	 * Return the available search matching modes.
	 *
	 * Each mode maps to the option key that toggles its availability and a
	 * human-readable label shown on the frontend control.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_match_modes() {
		return array(
			'keyword'    => array(
				'setting' => 'search_match_keyword',
				'label'   => __( 'All words', 'ninoxa-live-search' ),
			),
			'any'        => array(
				'setting' => 'search_match_any',
				'label'   => __( 'Any word', 'ninoxa-live-search' ),
			),
			'sentence'   => array(
				'setting' => 'search_match_sentence',
				'label'   => __( 'Exact phrase', 'ninoxa-live-search' ),
			),
			'whole_word' => array(
				'setting' => 'search_match_whole_word',
				'label'   => __( 'Whole word', 'ninoxa-live-search' ),
			),
			'fuzzy'      => array(
				'setting' => 'search_match_fuzzy',
				'label'   => __( 'Fuzzy', 'ninoxa-live-search' ),
			),
		);
	}

	/**
	 * Return the matching modes enabled in settings, as key => label pairs.
	 *
	 * @return array<string, string>
	 */
	public static function get_enabled_match_modes() {
		$enabled = array();

		foreach ( self::get_match_modes() as $mode_key => $mode ) {
			if ( '1' === self::get( $mode['setting'] ) ) {
				$enabled[ $mode_key ] = $mode['label'];
			}
		}

		return $enabled;
	}

	/**
	 * Return the default matching mode, falling back to the first enabled mode.
	 *
	 * @return string
	 */
	public static function get_default_match_mode() {
		$enabled = self::get_enabled_match_modes();

		if ( empty( $enabled ) ) {
			return 'keyword';
		}

		$default = self::get( 'search_matching_default' );

		if ( isset( $enabled[ $default ] ) ) {
			return $default;
		}

		$keys = array_keys( $enabled );

		return (string) reset( $keys );
	}

	/**
	 * Return all plugin settings merged with defaults.
	 *
	 * @return array<string, string>
	 */
	public static function get_all() {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, self::get_defaults() );
	}

	/**
	 * Return a single plugin setting.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	public static function get( $key ) {
		$settings = self::get_all();

		return isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
	}

	/**
	 * Return the normalized shortcut.
	 *
	 * @return string
	 */
	public static function get_keyboard_shortcut() {
		return self::normalize_keyboard_shortcut( self::get( 'keyboard_shortcut' ) );
	}

	/**
	 * Return the shortcut label shown to users.
	 *
	 * @param string|null $shortcut Optional shortcut value.
	 * @return string
	 */
	public static function get_keyboard_shortcut_label( $shortcut = null ) {
		$shortcut = null === $shortcut ? self::get_keyboard_shortcut() : self::normalize_keyboard_shortcut( $shortcut );

		if ( '' === $shortcut ) {
			return '';
		}

		$labels = array();
		$parts  = explode( '+', $shortcut );

		foreach ( $parts as $part ) {
			switch ( $part ) {
				case 'ctrl':
					$labels[] = 'Ctrl';
					break;
				case 'alt':
					$labels[] = 'Alt';
					break;
				case 'shift':
					$labels[] = 'Shift';
					break;
				case 'meta':
					$labels[] = 'Cmd';
					break;
				case 'escape':
					$labels[] = 'Escape';
					break;
				case 'enter':
					$labels[] = 'Enter';
					break;
				case 'space':
					$labels[] = 'Space';
					break;
				case 'backspace':
					$labels[] = 'Backspace';
					break;
				case 'tab':
					$labels[] = 'Tab';
					break;
				case 'arrowup':
					$labels[] = 'Arrow Up';
					break;
				case 'arrowdown':
					$labels[] = 'Arrow Down';
					break;
				case 'arrowleft':
					$labels[] = 'Arrow Left';
					break;
				case 'arrowright':
					$labels[] = 'Arrow Right';
					break;
				case 'delete':
					$labels[] = 'Delete';
					break;
				case 'insert':
					$labels[] = 'Insert';
					break;
				case 'home':
					$labels[] = 'Home';
					break;
				case 'end':
					$labels[] = 'End';
					break;
				case 'pageup':
					$labels[] = 'Page Up';
					break;
				case 'pagedown':
					$labels[] = 'Page Down';
					break;
				default:
					$labels[] = 1 === strlen( $part ) ? strtoupper( $part ) : ucwords( str_replace( array( '-', '_' ), ' ', $part ) );
			}
		}

		return implode( ' + ', $labels );
	}

	/**
	 * Normalize a shortcut into a predictable modifier+key string.
	 *
	 * @param string $shortcut Raw shortcut.
	 * @return string
	 */
	public static function normalize_keyboard_shortcut( $shortcut ) {
		$shortcut = strtolower( preg_replace( '/\s+/', '', (string) $shortcut ) );

		if ( '' === $shortcut ) {
			return '';
		}

		$modifier_aliases = array(
			'control' => 'ctrl',
			'ctrl'    => 'ctrl',
			'alt'     => 'alt',
			'option'  => 'alt',
			'shift'   => 'shift',
			'cmd'     => 'meta',
			'command' => 'meta',
			'meta'    => 'meta',
		);

		$key_aliases = array(
			'esc'      => 'escape',
			'return'   => 'enter',
			'spacebar' => 'space',
			'slash'    => '/',
		);

		$allowed_named_keys = array(
			'enter',
			'escape',
			'tab',
			'space',
			'backspace',
			'delete',
			'insert',
			'home',
			'end',
			'pageup',
			'pagedown',
			'arrowup',
			'arrowdown',
			'arrowleft',
			'arrowright',
			'f1',
			'f2',
			'f3',
			'f4',
			'f5',
			'f6',
			'f7',
			'f8',
			'f9',
			'f10',
			'f11',
			'f12',
		);

		$modifiers = array();
		$key       = '';
		$parts     = array_filter( explode( '+', $shortcut ), 'strlen' );

		if ( empty( $parts ) ) {
			return '';
		}

		foreach ( $parts as $part ) {
			if ( isset( $modifier_aliases[ $part ] ) ) {
				$modifiers[ $modifier_aliases[ $part ] ] = true;
				continue;
			}

			if ( isset( $key_aliases[ $part ] ) ) {
				$part = $key_aliases[ $part ];
			}

			if ( '' !== $key ) {
				return '';
			}

			if ( preg_match( '/^[a-z0-9]$/', $part ) ) {
				$key = $part;
				continue;
			}

			if ( in_array( $part, array( '/', '.', ',', ';', '-', '=', '[', ']', '\'', '`' ), true ) ) {
				$key = $part;
				continue;
			}

			if ( in_array( $part, $allowed_named_keys, true ) ) {
				$key = $part;
				continue;
			}

			return '';
		}

		if ( '' === $key ) {
			return '';
		}

		$normalized_parts = array();

		foreach ( array( 'ctrl', 'alt', 'shift', 'meta' ) as $modifier ) {
			if ( ! empty( $modifiers[ $modifier ] ) ) {
				$normalized_parts[] = $modifier;
			}
		}

		$normalized_parts[] = $key;

		return implode( '+', $normalized_parts );
	}
}