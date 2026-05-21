<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ninoxa-live-search-options.php';

/**
 * Settings page and field schema.
 */
class Ninoxa_Live_Search_Settings {
	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'ninoxa-live-search-settings';

	/**
	 * Option group slug.
	 */
	const OPTION_GROUP = 'ninoxa_live_search_settings_group';

	/**
	 * Hook suffix assigned by WordPress.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Register settings hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add the settings page.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		$this->page_hook = add_menu_page(
			__( 'Ninoxa Live Search', 'ninoxa-live-search' ),
			__( 'Ninoxa', 'ninoxa-live-search' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-search',
			58
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			Ninoxa_Live_Search_Options::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);

		foreach ( $this->get_sections() as $section_id => $section ) {
			add_settings_section(
				$section_id,
				$section['title'],
				array( $this, 'render_section' ),
				self::PAGE_SLUG
			);
		}

		foreach ( $this->get_fields() as $field_id => $field ) {
			add_settings_field(
				$field_id,
				$field['label'],
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				$field['section'],
				array(
					'field_id' => $field_id,
					'field'    => $field,
				)
			);
		}
	}

	/**
	 * Enqueue admin assets for the settings screen.
	 *
	 * @param string $hook_suffix Current admin screen.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'ninoxa-live-search-settings',
			$this->get_plugin_asset_url( 'admin/css/settings.css' ),
			array( 'wp-color-picker' ),
			$this->get_plugin_version()
		);

		wp_enqueue_script(
			'ninoxa-live-search-settings',
			$this->get_plugin_asset_url( 'admin/js/settings.js' ),
			array( 'jquery', 'wp-color-picker' ),
			$this->get_plugin_version(),
			true
		);

		wp_localize_script(
			'ninoxa-live-search-settings',
			'ninoxaLiveSearchSettings',
			array(
				'disabledLabel'     => __( 'Disabled', 'ninoxa-live-search' ),
				'capturePrompt'      => __( 'Focus the field and press the shortcut you want to use.', 'ninoxa-live-search' ),
				'captureReady'      => __( 'Listening for your shortcut.', 'ninoxa-live-search' ),
				'captureSaved'      => __( 'Shortcut captured. Save settings to apply it.', 'ninoxa-live-search' ),
				'captureCleared'    => __( 'Shortcut cleared. Save settings to keep it disabled.', 'ninoxa-live-search' ),
				'captureNeedKey'     => __( 'Add one key besides Ctrl, Alt, Shift, or Cmd.', 'ninoxa-live-search' ),
				'captureInvalidCombo' => __( 'Keyboard shortcut must contain one key and optional modifiers like Ctrl, Alt, Shift, or Cmd.', 'ninoxa-live-search' ),
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		ob_start();
		settings_errors();
		$settings_messages = trim( ob_get_clean() );
		?>
		<div class="wrap ninoxa-settings">
			<div class="ninoxa-settings__header">
				<div>
					<h1>
						<?php echo esc_html__( 'Ninoxa Live Search', 'ninoxa-live-search' ); ?>
						<span class="ninoxa-settings__version"><?php echo esc_html( NINOXA_LIVE_SEARCH_VERSION ); ?></span>
					</h1>
					<p class="ninoxa-settings__intro"><?php echo esc_html__( 'AJAX-powered instant search results for your WordPress site.', 'ninoxa-live-search' ); ?></p>
				</div>
			</div>

			<div class="wp-header-end" style="margin-bottom: 20px;"></div>
		<?php echo wp_kses_post( $settings_messages ); ?>
			<form action="options.php" method="post" class="ninoxa-settings__form">
				<?php settings_fields( self::OPTION_GROUP ); ?>
				<div class="ninoxa-settings__panel">
					<?php do_settings_sections( self::PAGE_SLUG ); ?>
				</div>
				<?php submit_button( __( 'Save settings', 'ninoxa-live-search' ), 'primary ninoxa-settings__submit' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render section copy.
	 *
	 * @param array<string, string> $section Current section.
	 * @return void
	 */
	public function render_section( $section ) {
		$sections = $this->get_sections();

		if ( empty( $sections[ $section['id'] ]['description'] ) ) {
			return;
		}

		echo '<p class="ninoxa-settings__section-description">' . esc_html( $sections[ $section['id'] ]['description'] ) . '</p>';
	}

	/**
	 * Render an individual field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_field( $args ) {
		$field_id = $args['field_id'];
		$field    = $args['field'];
		$options  = Ninoxa_Live_Search_Options::get_all();
		$value    = isset( $options[ $field_id ] ) ? (string) $options[ $field_id ] : '';
		$input_id = 'ninoxa-live-search-' . str_replace( '_', '-', $field_id );
		$status_id = $input_id . '-status';

		echo '<div class="ninoxa-settings__field">';

		if ( 'keyboard_shortcut' === $field_id ) {
			$display_value = Ninoxa_Live_Search_Options::get_keyboard_shortcut_label( $value );

			if ( '' === $display_value && '' !== $value ) {
				$display_value = $value;
			}

			echo '<div class="ninoxa-settings__shortcut-control">';
			printf(
				'<input id="%1$s" name="%2$s[%3$s]" type="text" value="%4$s" class="%5$s ninoxa-settings__input--shortcut" placeholder="%6$s" autocomplete="off" spellcheck="false" readonly="readonly" inputmode="none" aria-describedby="%7$s" data-ninoxa-shortcut-input />',
				esc_attr( $input_id ),
				esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ),
				esc_attr( $field_id ),
				esc_attr( $display_value ),
				esc_attr( $field['input_class'] ),
				esc_attr( $field['placeholder'] ),
				esc_attr( $status_id )
			);
			printf(
				'<button type="button" class="button button-secondary ninoxa-settings__shortcut-clear" data-ninoxa-shortcut-clear>%s</button>',
				esc_html__( 'Clear', 'ninoxa-live-search' )
			);
			echo '</div>';
			printf(
				'<p id="%1$s" class="ninoxa-settings__capture-hint" data-ninoxa-shortcut-status>%2$s</p>',
				esc_attr( $status_id ),
				esc_html__( 'Focus the field and press the shortcut you want to use. Backspace or Delete clears it.', 'ninoxa-live-search' )
			);
		} else {
			switch ( $field['type'] ) {
				case 'checkbox':
					printf(
						'<label class="ninoxa-settings__toggle"><input id="%1$s" name="%2$s[%3$s]" type="checkbox" value="1"%4$s class="ninoxa-settings__input--checkbox" /> <span>%5$s</span></label>',
						esc_attr( $input_id ),
						esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ),
						esc_attr( $field_id ),
						checked( $value, '1', false ),
						esc_html( isset( $field['checkbox_label'] ) ? $field['checkbox_label'] : __( 'Enable', 'ninoxa-live-search' ) )
					);
					break;

				case 'select':
					$options_html = '';
					if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
						foreach ( $field['options'] as $option_value => $option_label ) {
							$options_html .= sprintf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $option_value ),
								selected( $value, $option_value, false ),
								esc_html( $option_label )
							);
						}
					}
					printf(
						'<select id="%1$s" name="%2$s[%3$s]" class="ninoxa-settings__select">%4$s</select>',
						esc_attr( $input_id ),
						esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ),
						esc_attr( $field_id ),
						wp_kses(
							$options_html,
							array( 'option' => array( 'value' => array(), 'selected' => array() ) )
						)
					);
					break;

				case 'color':
					$default_color = isset( $field['default'] ) ? $field['default'] : '#000000';
					printf(
						'<input id="%1$s" name="%2$s[%3$s]" type="text" value="%4$s" class="ninoxa-settings__input ninoxa-settings__input--color" data-default-color="%5$s" />',
						esc_attr( $input_id ),
						esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ),
						esc_attr( $field_id ),
						esc_attr( $value ? $value : $default_color ),
						esc_attr( $default_color )
					);
					break;

				case 'text':
				default:
					printf(
						'<input id="%1$s" name="%2$s[%3$s]" type="text" value="%4$s" class="%5$s" placeholder="%6$s" autocomplete="off" spellcheck="false" />',
						esc_attr( $input_id ),
						esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ),
						esc_attr( $field_id ),
						esc_attr( $value ),
						esc_attr( isset( $field['input_class'] ) ? $field['input_class'] : 'regular-text ninoxa-settings__input' ),
						esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' )
					);
			}
		}

		if ( 'keyboard_shortcut' === $field_id ) {
			$label = Ninoxa_Live_Search_Options::get_keyboard_shortcut_label( $value );

			if ( '' === $label ) {
				$label = __( 'Disabled', 'ninoxa-live-search' );
			}

			echo '<div class="ninoxa-settings__preview">';
			echo '<span class="ninoxa-settings__preview-label">' . esc_html__( 'Shown on search field', 'ninoxa-live-search' ) . '</span>';
			echo '<span class="ninoxa-settings__chip" data-ninoxa-shortcut-preview data-state="' . esc_attr( __( 'Disabled', 'ninoxa-live-search' ) === $label ? 'disabled' : 'active' ) . '">' . esc_html( $label ) . '</span>';
			echo '</div>';
		}

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description ninoxa-settings__description">' . esc_html( $field['description'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Sanitize the full settings array.
	 *
	 * @param array<string, mixed> $input Raw settings.
	 * @return array<string, string>
	 */
	public function sanitize_settings( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$sanitized  = Ninoxa_Live_Search_Options::get_defaults();
		$current    = Ninoxa_Live_Search_Options::get_all();
		$field_defs = $this->get_fields();

		foreach ( $field_defs as $field_id => $field ) {
			$raw_value = isset( $input[ $field_id ] ) ? $input[ $field_id ] : ( isset( $current[ $field_id ] ) ? $current[ $field_id ] : '' );

			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$sanitized[ $field_id ] = (string) call_user_func( $field['sanitize_callback'], $raw_value );
				continue;
			}

			$field_type = isset( $field['type'] ) ? $field['type'] : 'text';

			if ( 'checkbox' === $field_type ) {
				$sanitized[ $field_id ] = '1' === (string) $raw_value ? '1' : '0';
				continue;
			}

			if ( 'select' === $field_type && isset( $field['options'] ) ) {
				$option_keys   = array_keys( $field['options'] );
				$first_key     = isset( $option_keys[0] ) ? (string) $option_keys[0] : '';
				$fallback      = isset( $field['default'] ) ? (string) $field['default'] : $first_key;
				$sanitized[ $field_id ] = array_key_exists( (string) $raw_value, $field['options'] )
					? sanitize_text_field( (string) $raw_value )
					: $fallback;
				continue;
			}

			if ( 'color' === $field_type ) {
				$color = sanitize_hex_color( (string) $raw_value );
				$sanitized[ $field_id ] = $color ? $color : ( isset( $field['default'] ) ? (string) $field['default'] : '' );
				continue;
			}

			$sanitized[ $field_id ] = sanitize_text_field( (string) $raw_value );
		}

		return $sanitized;
	}

	/**
	 * Sanitize the keyboard shortcut field.
	 *
	 * @param mixed $value Raw field value.
	 * @return string
	 */
	public function sanitize_keyboard_shortcut( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$normalized = Ninoxa_Live_Search_Options::normalize_keyboard_shortcut( $value );

		if ( '' === $normalized ) {
			add_settings_error(
				Ninoxa_Live_Search_Options::OPTION_NAME,
				'invalid-keyboard-shortcut',
				__( 'Keyboard shortcut must contain one key and optional modifiers like Ctrl, Alt, Shift, or Cmd.', 'ninoxa-live-search' )
			);

			return Ninoxa_Live_Search_Options::get( 'keyboard_shortcut' );
		}

		return $normalized;
	}

	/**
	 * Return the settings sections schema.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_sections() {
		return array(
			'general' => array(
				'title'       => __( 'General', 'ninoxa-live-search' ),
				'description' => __( 'Choose how visitors trigger and use live search.', 'ninoxa-live-search' ),
			),
			'loading' => array(
				'title'       => __( 'Loading indicators', 'ninoxa-live-search' ),
				'description' => __( 'Configure the visual feedback shown on the search field while results are loading.', 'ninoxa-live-search' ),
			),
		);
	}

	/**
	 * Return the settings field schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_fields() {
		return array(
			'keyboard_shortcut'       => array(
				'section'           => 'general',
				'label'             => __( 'Keyboard shortcut', 'ninoxa-live-search' ),
				'type'              => 'text',
				'placeholder'       => __( 'Press keys', 'ninoxa-live-search' ),
				'input_class'       => 'regular-text code ninoxa-settings__input',
				'description'       => __( 'Allowed keys: letters (A–Z), digits (0–9), symbols (/ . , ; - = [ ] \' `), function keys (F1–F12), and named keys — Enter, Escape, Backspace, Delete, Tab, Space, Insert, Home, End, Page Up, Page Down, and arrows. Combine with Ctrl, Alt, Shift, or Cmd. Leave empty to disable the shortcut and its hint.', 'ninoxa-live-search' ),
				'sanitize_callback' => array( $this, 'sanitize_keyboard_shortcut' ),
			),
			'loading_spinner_enabled' => array(
				'section'        => 'loading',
				'label'          => __( 'Spinner', 'ninoxa-live-search' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Show a spinning indicator inside the search field while loading', 'ninoxa-live-search' ),
			),
			'loading_spinner_position' => array(
				'section'     => 'loading',
				'label'       => __( 'Spinner position', 'ninoxa-live-search' ),
				'type'        => 'select',
				'default'     => 'right',
				'options'     => array(
					'right' => __( 'Right', 'ninoxa-live-search' ),
					'left'  => __( 'Left', 'ninoxa-live-search' ),
				),
				'description' => __( 'Side of the search field where the spinner appears.', 'ninoxa-live-search' ),
			),
			'loading_spinner_color'   => array(
				'section'     => 'loading',
				'label'       => __( 'Spinner color', 'ninoxa-live-search' ),
				'type'        => 'color',
				'default'     => '#3498db',
				'description' => __( 'Color of the spinning indicator arc.', 'ninoxa-live-search' ),
			),
			'loading_spinner_size'    => array(
				'section'  => 'loading',
				'label'    => __( 'Spinner size', 'ninoxa-live-search' ),
				'type'     => 'select',
				'default'  => 'medium',
				'options'  => array(
					'small'  => __( 'Small (14 px)', 'ninoxa-live-search' ),
					'medium' => __( 'Medium (16 px)', 'ninoxa-live-search' ),
					'large'  => __( 'Large (20 px)', 'ninoxa-live-search' ),
				),
			),
			'loading_sweep_enabled'   => array(
				'section'        => 'loading',
				'label'          => __( 'Light sweep', 'ninoxa-live-search' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Animate a light sweep across the search field while loading', 'ninoxa-live-search' ),
			),
		);
	}

	/**
	 * Return a plugin asset URL with a safe fallback for editor analysis.
	 *
	 * @param string $path Relative asset path.
	 * @return string
	 */
	private function get_plugin_asset_url( $path ) {
		$base_url = defined( 'NINOXA_LIVE_SEARCH_URL' )
			? NINOXA_LIVE_SEARCH_URL
			: plugin_dir_url( dirname( __DIR__ ) . '/ninoxa-live-search.php' );

		return $base_url . ltrim( $path, '/' );
	}

	/**
	 * Return the plugin version with a safe fallback.
	 *
	 * @return string
	 */
	private function get_plugin_version() {
		return defined( 'NINOXA_LIVE_SEARCH_VERSION' ) ? NINOXA_LIVE_SEARCH_VERSION : '1.1.0';
	}
}