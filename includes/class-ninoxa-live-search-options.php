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
			'keyboard_shortcut' => 'ctrl+/',
		);
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