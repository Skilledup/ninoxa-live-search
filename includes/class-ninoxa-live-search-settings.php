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
	 * Register plugin settings with WordPress (nonce + sanitize callback only).
	 * Fields are rendered manually — no add_settings_section/field needed.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			Ninoxa_Live_Search_Options::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);
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

			<div class="ninoxa-settings-header">
				<h1>
					<?php esc_html_e( 'Ninoxa Live Search', 'ninoxa-live-search' ); ?>
					<span class="ninoxa-settings-version"><?php echo esc_html( $this->get_plugin_version() ); ?></span>
				</h1>
				<p class="ninoxa-settings-intro"><?php esc_html_e( 'AJAX-powered instant search results for your WordPress site.', 'ninoxa-live-search' ); ?></p>
			</div>

			<div class="wp-header-end" style="margin-bottom: 20px;"></div>

			<?php if ( $settings_messages ) : ?>
			<div class="ninoxa-settings-notices"><?php echo wp_kses_post( $settings_messages ); ?></div>
			<?php endif; ?>

			<nav class="ninoxa-settings-tabs" aria-label="<?php esc_attr_e( 'Settings tabs', 'ninoxa-live-search' ); ?>">
				<a href="#general" class="nav-tab nav-tab-active" data-ninoxa-tab="general">
					<span class="ninoxa-tab-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.001M10 8h.001M14 8h.001M18 8h.001M8 12h.001M12 12h.001M16 12h.001M7 16h10"/></svg>
					</span>
					<?php esc_html_e( 'General', 'ninoxa-live-search' ); ?>
				</a>
				<a href="#loading" class="nav-tab" data-ninoxa-tab="loading">
					<span class="ninoxa-tab-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
					</span>
					<?php esc_html_e( 'Loading', 'ninoxa-live-search' ); ?>
				</a>
				<a href="#about" class="nav-tab" data-ninoxa-tab="about">
					<span class="ninoxa-tab-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
					</span>
					<?php esc_html_e( 'About', 'ninoxa-live-search' ); ?>
				</a>
			</nav>

			<div class="ninoxa-settings-layout">
				<div class="ninoxa-settings-main">
					<form action="options.php" method="post" class="ninoxa-settings-form">
						<?php settings_fields( self::OPTION_GROUP ); ?>

						<div class="ninoxa-tab-pane is-active" id="ninoxa-tab-general" data-tab="general">
							<?php $this->render_tab_general(); ?>
						</div>

						<div class="ninoxa-tab-pane" id="ninoxa-tab-loading" data-tab="loading">
							<?php $this->render_tab_loading(); ?>
						</div>

						<div class="ninoxa-tab-pane" id="ninoxa-tab-about" data-tab="about">
							<?php $this->render_tab_about(); ?>
						</div>

						<div class="ninoxa-submit-row">
							<?php submit_button( __( 'Save settings', 'ninoxa-live-search' ), 'primary ninoxa-submit-btn', 'submit', false ); ?>
						</div>
					</form>
				</div>

				<?php $this->render_sidebar(); ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the General tab content.
	 *
	 * @return void
	 */
	private function render_tab_general() {
		$options = Ninoxa_Live_Search_Options::get_all();
		?>
		<div class="ninoxa-settings-card">
			<div class="settings-card-header">
				<span class="settings-card-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.001M10 8h.001M14 8h.001M18 8h.001M8 12h.001M12 12h.001M16 12h.001M7 16h10"/></svg>
				</span>
				<h3><?php esc_html_e( 'Keyboard Shortcut', 'ninoxa-live-search' ); ?></h3>
			</div>
			<div class="settings-card-body">
				<p class="settings-card-intro"><?php esc_html_e( 'Choose how visitors trigger live search from the keyboard.', 'ninoxa-live-search' ); ?></p>
				<?php $this->render_shortcut_field( $options ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Loading tab content.
	 *
	 * @return void
	 */
	private function render_tab_loading() {
		$options         = Ninoxa_Live_Search_Options::get_all();
		$spinner_enabled = isset( $options['loading_spinner_enabled'] ) && '1' === (string) $options['loading_spinner_enabled'];
		$sweep_enabled   = isset( $options['loading_sweep_enabled'] ) && '1' === (string) $options['loading_sweep_enabled'];
		?>
		<div class="ninoxa-settings-card ninoxa-settings-card-highlight">
			<div class="settings-card-header">
				<span class="settings-card-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
				</span>
				<h3><?php esc_html_e( 'Loading Spinner', 'ninoxa-live-search' ); ?></h3>
			</div>
			<div class="settings-card-body">
				<p class="settings-card-intro"><?php esc_html_e( 'Show a spinning indicator inside the search field while results load.', 'ninoxa-live-search' ); ?></p>

				<div class="settings-field">
					<div class="settings-field-header">
						<label for="ninoxa-live-search-loading-spinner-enabled"><?php esc_html_e( 'Enable spinner', 'ninoxa-live-search' ); ?></label>
						<?php $this->render_toggle( 'loading_spinner_enabled', $options, 'ninoxa-spinner-options' ); ?>
					</div>
				</div>

				<div class="ninoxa-settings-collapsible<?php echo $spinner_enabled ? ' is-expanded' : ''; ?>" id="ninoxa-spinner-options">
					<div class="ninoxa-settings-collapsible__inner">
						<?php
						$this->render_field_card( 'loading_spinner_position', $options );
						$this->render_field_card( 'loading_spinner_color', $options );
						$this->render_field_card( 'loading_spinner_size', $options );
						$this->render_field_card( 'loading_spinner_offset', $options );
						?>
					</div>
				</div>
			</div>
		</div>

		<div class="ninoxa-settings-card">
			<div class="settings-card-header">
				<span class="settings-card-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.912 5.813a2 2 0 0 0 1.275 1.275L21 12l-5.813 1.912a2 2 0 0 0-1.275 1.275L12 21l-1.912-5.813a2 2 0 0 0-1.275-1.275L3 12l5.813-1.912a2 2 0 0 0 1.275-1.275L12 3z"/></svg>
				</span>
				<h3><?php esc_html_e( 'Light Sweep', 'ninoxa-live-search' ); ?></h3>
			</div>
			<div class="settings-card-body">
				<p class="settings-card-intro"><?php esc_html_e( 'Animate a light sweep across the search field while results load.', 'ninoxa-live-search' ); ?></p>

				<div class="settings-field">
					<div class="settings-field-header">
						<label for="ninoxa-live-search-loading-sweep-enabled"><?php esc_html_e( 'Enable light sweep', 'ninoxa-live-search' ); ?></label>
						<?php $this->render_toggle( 'loading_sweep_enabled', $options, 'ninoxa-sweep-options' ); ?>
					</div>
				</div>

				<div class="ninoxa-settings-collapsible<?php echo $sweep_enabled ? ' is-expanded' : ''; ?>" id="ninoxa-sweep-options">
					<div class="ninoxa-settings-collapsible__inner">
						<?php $this->render_field_card( 'loading_sweep_speed', $options ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the About tab content.
	 *
	 * @return void
	 */
	private function render_tab_about() {
		?>
		<div class="ninoxa-settings-card">
			<div class="settings-card-body">
				<p><?php esc_html_e( 'Ninoxa Live Search adds real-time, AJAX-powered search results to any standard WordPress search form. Results appear instantly as the visitor types — no page reload needed.', 'ninoxa-live-search' ); ?></p>
				<p><?php esc_html_e( 'Compatible with Polylang and WPML for multilingual sites. Fully accessible with keyboard navigation and ARIA attributes. Lightweight with no external dependencies.', 'ninoxa-live-search' ); ?></p>
				<p>
					<a href="https://wordpress.org/plugins/ninoxa-live-search/" target="_blank" rel="noopener noreferrer" class="ninoxa-about-link ninoxa-about-link--primary">
						<?php esc_html_e( 'View on WordPress.org', 'ninoxa-live-search' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the sticky sidebar with contextual tips.
	 *
	 * @return void
	 */
	private function render_sidebar() {
		?>
		<aside class="ninoxa-settings-sidebar">

			<div class="sidebar-tip" data-sidebar-tab="general">
				<div class="tip-header">
					<span class="tip-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
					</span>
					<strong><?php esc_html_e( 'Keyboard Shortcut', 'ninoxa-live-search' ); ?></strong>
				</div>
				<p><?php esc_html_e( 'A shortcut hint badge appears inside the search field to guide visitors. Leave the shortcut empty to hide it entirely.', 'ninoxa-live-search' ); ?></p>
			</div>

			<div class="sidebar-tip" data-sidebar-tab="general">
				<div class="tip-header">
					<span class="tip-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
					</span>
					<strong><?php esc_html_e( 'Examples', 'ninoxa-live-search' ); ?></strong>
				</div>
				<p><?php esc_html_e( 'Popular shortcuts: Ctrl+K for command-palette style, Ctrl+/ for search, or just / for quick access.', 'ninoxa-live-search' ); ?></p>
			</div>

			<div class="sidebar-tip" data-sidebar-tab="loading">
				<div class="tip-header">
					<span class="tip-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
					</span>
					<strong><?php esc_html_e( 'Loading Indicators', 'ninoxa-live-search' ); ?></strong>
				</div>
				<p><?php esc_html_e( 'Spinner and light sweep can both be active at the same time for a richer loading experience.', 'ninoxa-live-search' ); ?></p>
			</div>

			<div class="sidebar-tip" data-sidebar-tab="loading">
				<div class="tip-header">
					<span class="tip-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
					</span>
					<strong><?php esc_html_e( 'Performance', 'ninoxa-live-search' ); ?></strong>
				</div>
				<p><?php esc_html_e( 'Both indicators are pure CSS animations — zero JavaScript overhead, no impact on search speed.', 'ninoxa-live-search' ); ?></p>
			</div>

			<div class="sidebar-tip" data-sidebar-tab="about">
				<div class="tip-header">
					<span class="tip-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
					</span>
					<strong><?php esc_html_e( 'Leave a Review', 'ninoxa-live-search' ); ?></strong>
				</div>
				<p><?php esc_html_e( 'Enjoying the plugin? A review on WordPress.org helps others discover it and motivates continued development.', 'ninoxa-live-search' ); ?></p>
			</div>

		</aside>
		<?php
	}

	/**
	 * Render a Shuriken-style CSS toggle switch for a checkbox field.
	 *
	 * @param string               $field_id    Field ID.
	 * @param array<string, mixed> $options     Current saved options.
	 * @param string               $controls_id ID of the collapsible section this toggle controls.
	 * @return void
	 */
	private function render_toggle( $field_id, $options, $controls_id = '' ) {
		$value         = isset( $options[ $field_id ] ) ? (string) $options[ $field_id ] : '0';
		$input_id      = 'ninoxa-live-search-' . str_replace( '_', '-', $field_id );
		$data_controls = $controls_id ? ' data-controls="' . esc_attr( $controls_id ) . '"' : '';

		printf(
			'<label class="ninoxa-toggle"><input id="%1$s" name="%2$s[%3$s]" type="checkbox" value="1"%4$s class="ninoxa-settings__input--checkbox"%5$s /><span class="toggle-slider"></span></label>',
			esc_attr( $input_id ),
			esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ),
			esc_attr( $field_id ),
			checked( $value, '1', false ),
			$data_controls
		);
	}

	/**
	 * Render a settings field inside a .settings-field card block.
	 *
	 * @param string               $field_id Field ID.
	 * @param array<string, mixed> $options  Current saved options.
	 * @return void
	 */
	private function render_field_card( $field_id, $options ) {
		$fields = $this->get_fields();

		if ( ! isset( $fields[ $field_id ] ) ) {
			return;
		}

		$field    = $fields[ $field_id ];
		$value    = isset( $options[ $field_id ] ) ? (string) $options[ $field_id ] : '';
		$input_id = 'ninoxa-live-search-' . str_replace( '_', '-', $field_id );
		?>
		<div class="settings-field">
			<div class="settings-field-header">
				<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
			</div>
			<div class="settings-input-group">
				<?php
				switch ( $field['type'] ) {
					case 'select':
						$options_html = '';
						if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
							foreach ( $field['options'] as $opt_val => $opt_label ) {
								$options_html .= sprintf(
									'<option value="%s"%s>%s</option>',
									esc_attr( $opt_val ),
									selected( $value, $opt_val, false ),
									esc_html( $opt_label )
								);
							}
						}
						printf(
							'<select id="%1$s" name="%2$s[%3$s]" class="ninoxa-settings__select">%4$s</select>',
							esc_attr( $input_id ),
							esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ),
							esc_attr( $field_id ),
							wp_kses( $options_html, array( 'option' => array( 'value' => true, 'selected' => true ) ) )
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
						break;
				}
				?>
			</div>
			<?php if ( ! empty( $field['description'] ) ) : ?>
			<p class="settings-field-description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the keyboard shortcut capture field.
	 *
	 * @param array<string, mixed> $options Current saved options.
	 * @return void
	 */
	private function render_shortcut_field( $options ) {
		$fields    = $this->get_fields();
		$field_id  = 'keyboard_shortcut';
		$field     = $fields[ $field_id ];
		$value     = isset( $options[ $field_id ] ) ? (string) $options[ $field_id ] : '';
		$input_id  = 'ninoxa-live-search-keyboard-shortcut';
		$status_id = $input_id . '-status';

		$display_value = Ninoxa_Live_Search_Options::get_keyboard_shortcut_label( $value );
		if ( '' === $display_value && '' !== $value ) {
			$display_value = $value;
		}

		$label = Ninoxa_Live_Search_Options::get_keyboard_shortcut_label( $value );
		if ( '' === $label ) {
			$label = __( 'Disabled', 'ninoxa-live-search' );
		}
		?>
		<div class="settings-field">
			<div class="settings-field-header">
				<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
			</div>
			<div class="ninoxa-settings__shortcut-control">
				<input
					id="<?php echo esc_attr( $input_id ); ?>"
					name="<?php echo esc_attr( Ninoxa_Live_Search_Options::OPTION_NAME ); ?>[<?php echo esc_attr( $field_id ); ?>]"
					type="text"
					value="<?php echo esc_attr( $display_value ); ?>"
					class="regular-text code ninoxa-settings__input ninoxa-settings__input--shortcut"
					placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
					autocomplete="off"
					spellcheck="false"
					readonly="readonly"
					inputmode="none"
					aria-describedby="<?php echo esc_attr( $status_id ); ?>"
					data-ninoxa-shortcut-input
				/>
				<button type="button" class="button button-secondary ninoxa-settings__shortcut-clear" data-ninoxa-shortcut-clear>
					<?php esc_html_e( 'Clear', 'ninoxa-live-search' ); ?>
				</button>
			</div>
			<p id="<?php echo esc_attr( $status_id ); ?>" class="settings-field-description ninoxa-settings__capture-hint" data-ninoxa-shortcut-status>
				<?php esc_html_e( 'Focus the field and press the shortcut you want to use. Backspace or Delete clears it.', 'ninoxa-live-search' ); ?>
			</p>
			<div class="ninoxa-settings__preview">
				<span class="ninoxa-settings__preview-label"><?php esc_html_e( 'Shown on search field', 'ninoxa-live-search' ); ?></span>
				<span class="ninoxa-settings__chip" data-ninoxa-shortcut-preview data-state="<?php echo esc_attr( __( 'Disabled', 'ninoxa-live-search' ) === $label ? 'disabled' : 'active' ); ?>"><?php echo esc_html( $label ); ?></span>
			</div>
			<?php if ( ! empty( $field['description'] ) ) : ?>
			<p class="settings-field-description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
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
	 * Sanitize the spinner offset.
	 *
	 * @param mixed $value Raw field value.
	 * @return string
	 */
	public function sanitize_spinner_offset( $value ) {
		$offset = absint( $value );

		if ( 0 === $offset ) {
			return '12';
		}

		return (string) $offset;
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
			'loading_spinner_offset'  => array(
				'section'     => 'loading',
				'label'       => __( 'Spinner offset', 'ninoxa-live-search' ),
				'type'        => 'text',
				'placeholder' => __( '12', 'ninoxa-live-search' ),
				'input_class' => 'regular-text ninoxa-settings__input',
				'description' => __( 'Distance from the left or right edge of the input in pixels.', 'ninoxa-live-search' ),
				'sanitize_callback' => array( $this, 'sanitize_spinner_offset' ),
			),
			'loading_sweep_enabled'   => array(
				'section'        => 'loading',
				'label'          => __( 'Light sweep', 'ninoxa-live-search' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Animate a light sweep across the search field while loading', 'ninoxa-live-search' ),
			),
			'loading_sweep_speed'     => array(
				'section'  => 'loading',
				'label'    => __( 'Light sweep speed', 'ninoxa-live-search' ),
				'type'     => 'select',
				'default'  => '1.4s',
				'options'  => array(
					'0.8s' => __( 'Fast', 'ninoxa-live-search' ),
					'1.4s' => __( 'Normal', 'ninoxa-live-search' ),
					'2.0s' => __( 'Slow', 'ninoxa-live-search' ),
				),
				'description' => __( 'Set how quickly the light sweep animation travels across the input.', 'ninoxa-live-search' ),
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
		return defined( 'NINOXA_LIVE_SEARCH_VERSION' ) ? NINOXA_LIVE_SEARCH_VERSION : '1.1.1';
	}
}