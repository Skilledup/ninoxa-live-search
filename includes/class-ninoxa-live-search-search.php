<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handlers and search URL helpers.
 */
class Ninoxa_Live_Search_Search {
	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_live_search_refresh_nonce', array( $this, 'refresh_nonce_ajax' ) );
		add_action( 'wp_ajax_nopriv_live_search_refresh_nonce', array( $this, 'refresh_nonce_ajax' ) );
		add_action( 'wp_ajax_live_search', array( $this, 'live_search_ajax' ) );
		add_action( 'wp_ajax_nopriv_live_search', array( $this, 'live_search_ajax' ) );
	}

	/**
	 * Return a multilingual search URL.
	 *
	 * @param string $search_query Search query.
	 * @return string
	 */
	public static function get_multilingual_search_url( $search_query ) {
		$base_url = home_url( '/' );

		if ( function_exists( 'pll_current_language' ) && function_exists( 'pll_home_url' ) ) {
			$current_lang = pll_current_language();

			if ( $current_lang ) {
				$lang_home_url = pll_home_url( $current_lang );

				if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
					$front_page = get_post( get_option( 'page_on_front' ) );

					if ( $front_page ) {
						$parsed = wp_parse_url( $lang_home_url );

						if ( is_array( $parsed ) && ! empty( $parsed['path'] ) && ! empty( $parsed['host'] ) && ! empty( $parsed['scheme'] ) ) {
							$parts = explode( '/', trim( $parsed['path'], '/' ) );

							if ( ! empty( $parts ) && end( $parts ) === $front_page->post_name ) {
								array_pop( $parts );
								$parsed['path'] = '/' . implode( '/', $parts ) . '/';

								$host = $parsed['host'];

								if ( isset( $parsed['port'] ) ) {
									$host .= ':' . $parsed['port'];
								}

								$lang_home_url = untrailingslashit( $parsed['scheme'] . '://' . $host . $parsed['path'] );
							}
						}
					}
				}

				$base_url = $lang_home_url;
			}
		} elseif ( has_filter( 'wpml_current_language' ) ) {
			$current_lang = apply_filters( 'wpml_current_language', null );

			if ( $current_lang ) {
				if ( has_filter( 'wpml_home_url' ) ) {
					$base_url = apply_filters( 'wpml_home_url', home_url( '/' ), $current_lang );
				} else {
					$base_url = home_url( '/' . $current_lang . '/' );
				}
			}
		}

		return trailingslashit( $base_url ) . '?s=' . urlencode( $search_query );
	}

	/**
	 * Refresh a frontend nonce.
	 *
	 * @return void
	 */
	public function refresh_nonce_ajax() {
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-cache, no-store, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
		}

		wp_send_json_success(
			array(
				'nonce'     => wp_create_nonce( 'live_search_nonce' ),
				'timestamp' => time(),
			)
		);
	}

	/**
	 * Handle the live search AJAX response.
	 *
	 * @return void
	 */
	public function live_search_ajax() {
		$switched_locale = switch_to_locale( get_locale() );

		if ( ! isset( $_POST['nonce'] ) ) {
			$this->send_json_error(
				array(
					'message' => __( 'Missing nonce', 'ninoxa-live-search' ),
					'code'    => 'missing_nonce',
				),
				$switched_locale
			);
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'live_search_nonce' ) ) {
			$this->send_json_error(
				array(
					'message' => __( 'Invalid nonce', 'ninoxa-live-search' ),
					'code'    => 'invalid_nonce',
				),
				$switched_locale
			);
		}

		$search_query = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

		if ( '' === $search_query || strlen( $search_query ) < 3 ) {
			echo '<div class="live-search-no-results">';
			echo esc_html__( 'no results found...', 'ninoxa-live-search' );
			echo '</div>';
			$this->restore_locale( $switched_locale );
			wp_die();
		}

		$query = new WP_Query(
			array(
				's'                      => $search_query,
				'post_status'            => 'publish',
				'posts_per_page'         => 11,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$has_more_results = $query->post_count > 10;

		if ( $query->have_posts() ) {
			$result_index = 0;

			while ( $query->have_posts() && $result_index < 10 ) {
				$query->the_post();
				++$result_index;
				$aria_label = sprintf(
					/* translators: 1: result index number, 2: post title. */
					__( 'Search result %1$d: %2$s', 'ninoxa-live-search' ),
					$result_index,
					get_the_title()
				);
				?>
				<div class="live-search-result" role="option" tabindex="-1" aria-selected="false" data-result-index="<?php echo esc_attr( $result_index ); ?>">
					<a href="<?php the_permalink(); ?>" tabindex="-1" aria-label="<?php echo esc_attr( $aria_label ); ?>">
						<?php the_title(); ?>
					</a>
				</div>
				<?php
			}

			if ( $has_more_results ) {
				++$result_index;
				$more_aria_label = sprintf(
					/* translators: %s is the search query. */
					__( 'View more search results for: %s', 'ninoxa-live-search' ),
					$search_query
				);
				?>
				<div class="live-search-more-results" role="option" tabindex="-1" aria-selected="false" data-result-index="<?php echo esc_attr( $result_index ); ?>">
					<a href="<?php echo esc_url( self::get_multilingual_search_url( $search_query ) ); ?>" tabindex="-1" aria-label="<?php echo esc_attr( $more_aria_label ); ?>">
						<?php echo esc_html__( 'More results...', 'ninoxa-live-search' ); ?>
					</a>
				</div>
				<?php
			}
		} else {
			echo '<div class="live-search-no-results" role="status" aria-live="polite">';
			echo esc_html__( 'no results found...', 'ninoxa-live-search' );
			echo '</div>';
		}

		wp_reset_postdata();
		$this->restore_locale( $switched_locale );
		wp_die();
	}

	/**
	 * Restore the previous locale when one was switched.
	 *
	 * @param bool $switched_locale Whether the locale was switched.
	 * @return void
	 */
	private function restore_locale( $switched_locale ) {
		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	/**
	 * Send an error response and restore locale state first.
	 *
	 * @param array<string, string> $payload Error response payload.
	 * @param bool                  $switched_locale Whether the locale was switched.
	 * @return void
	 */
	private function send_json_error( $payload, $switched_locale ) {
		$this->restore_locale( $switched_locale );
		wp_send_json_error( $payload );
	}
}