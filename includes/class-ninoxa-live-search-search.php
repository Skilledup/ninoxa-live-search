<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handlers and search URL helpers.
 */
class Ninoxa_Live_Search_Search {
	/**
	 * The matching mode applied to the active query.
	 *
	 * @var string
	 */
	private $active_match_mode = 'keyword';

	/**
	 * The raw search query for the active request.
	 *
	 * @var string
	 */
	private $active_search_query = '';

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

		$results_limit = (int) Ninoxa_Live_Search_Options::get( 'search_results_limit' );
		if ( $results_limit <= 0 ) {
			$results_limit = 10;
		}

		$match_mode = $this->resolve_match_mode( isset( $_POST['match_mode'] ) ? sanitize_key( wp_unslash( $_POST['match_mode'] ) ) : '' );

		$query_args = array(
			's'                      => $search_query,
			'post_status'            => 'publish',
			'posts_per_page'         => $results_limit + 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( 'sentence' === $match_mode ) {
			$query_args['sentence'] = true;
		}

		$this->active_match_mode   = $match_mode;
		$this->active_search_query = $search_query;
		$uses_custom_clause        = in_array( $match_mode, array( 'any', 'whole_word', 'fuzzy' ), true );

		if ( $uses_custom_clause ) {
			add_filter( 'posts_search', array( $this, 'filter_posts_search' ), 10, 2 );
		}

		$query = new WP_Query( $query_args );

		if ( $uses_custom_clause ) {
			remove_filter( 'posts_search', array( $this, 'filter_posts_search' ), 10 );
		}

		$has_more_results = $query->post_count > $results_limit;

		if ( $query->have_posts() ) {
			$result_index = 0;

			while ( $query->have_posts() && $result_index < $results_limit ) {
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
	 * Resolve the requested matching mode against the enabled modes.
	 *
	 * Falls back to the configured default mode when the feature is disabled or
	 * the requested mode is not allowed. The server is authoritative here so a
	 * crafted request can never enable a mode the site owner turned off.
	 *
	 * @param string $requested_mode Mode requested by the client.
	 * @return string
	 */
	private function resolve_match_mode( $requested_mode ) {
		if ( '1' !== Ninoxa_Live_Search_Options::get( 'search_matching_enabled' ) ) {
			return Ninoxa_Live_Search_Options::get_default_match_mode();
		}

		$enabled_modes = Ninoxa_Live_Search_Options::get_enabled_match_modes();

		if ( '' !== $requested_mode && isset( $enabled_modes[ $requested_mode ] ) ) {
			return $requested_mode;
		}

		return Ninoxa_Live_Search_Options::get_default_match_mode();
	}

	/**
	 * Replace the core search SQL clause for the "any", "whole word" and
	 * "fuzzy" matching modes using only native MySQL features.
	 *
	 * @param string   $search   Existing search SQL.
	 * @param WP_Query $wp_query Query instance.
	 * @return string
	 */
	public function filter_posts_search( $search, $wp_query ) {
		global $wpdb;

		$query = isset( $wp_query->query_vars['s'] ) ? (string) $wp_query->query_vars['s'] : $this->active_search_query;
		$query = trim( $query );

		if ( '' === $query ) {
			return $search;
		}

		$terms = $this->split_search_terms( $query );

		if ( empty( $terms ) ) {
			return $search;
		}

		$mode    = $this->active_match_mode;
		$columns = array(
			$wpdb->posts . '.post_title',
			$wpdb->posts . '.post_excerpt',
			$wpdb->posts . '.post_content',
		);

		$term_clauses = array();

		foreach ( $terms as $term ) {
			$column_clauses = array();

			if ( 'whole_word' === $mode ) {
				$pattern = '(^|[^[:alnum:]])' . $this->escape_mysql_regex( $term ) . '([^[:alnum:]]|$)';

				foreach ( $columns as $column ) {
					$column_clauses[] = $wpdb->prepare( "{$column} REGEXP %s", $pattern ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}
			} else {
				$like = '%' . $wpdb->esc_like( $term ) . '%';

				foreach ( $columns as $column ) {
					$column_clauses[] = $wpdb->prepare( "{$column} LIKE %s", $like ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}

				if ( 'fuzzy' === $mode ) {
					foreach ( $this->get_fuzzy_like_patterns( $term ) as $pattern ) {
						foreach ( $columns as $column ) {
							$column_clauses[] = $wpdb->prepare( "{$column} LIKE %s", $pattern ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						}
					}
				}
			}

			$term_clauses[] = '(' . implode( ' OR ', $column_clauses ) . ')';
		}

		// Whole-word mode requires every term to be present; the broader "any"
		// and "fuzzy" modes match when any term is found.
		$glue = ( 'whole_word' === $mode ) ? ' AND ' : ' OR ';

		return ' AND (' . implode( $glue, $term_clauses ) . ') ';
	}

	/**
	 * Split a search query into individual terms.
	 *
	 * @param string $query Search query.
	 * @return array<int, string>
	 */
	private function split_search_terms( $query ) {
		$parts = preg_split( '/\s+/u', trim( $query ) );
		$terms = array();

		foreach ( (array) $parts as $part ) {
			$part = trim( $part );

			if ( '' !== $part && strlen( $part ) >= 2 ) {
				$terms[ $part ] = $part;
			}
		}

		if ( empty( $terms ) && '' !== trim( $query ) ) {
			$terms[ trim( $query ) ] = trim( $query );
		}

		return array_slice( array_values( $terms ), 0, 10 );
	}

	/**
	 * Escape MySQL REGEXP metacharacters in a search term.
	 *
	 * @param string $term Search term.
	 * @return string
	 */
	private function escape_mysql_regex( $term ) {
		return preg_replace( '/[.^$*+?()\[\]{}|\\\\]/', '\\\\$0', $term );
	}

	/**
	 * Build native LIKE patterns that tolerate a single-character typo.
	 *
	 * Because MySQL has no built-in fuzzy/edit-distance operator, we emulate an
	 * edit distance of one by generating LIKE patterns where one character is
	 * substituted, deleted, or inserted using the single-character wildcard "_".
	 * This lets "helo" match "hello", "wrold" match "world", etc. — all with the
	 * native search engine and no extra dependencies.
	 *
	 * @param string $term Search term.
	 * @return array<int, string> LIKE patterns (already wrapped in %…% and escaped).
	 */
	private function get_fuzzy_like_patterns( $term ) {
		$chars = preg_split( '//u', $term, -1, PREG_SPLIT_NO_EMPTY );
		$count = is_array( $chars ) ? count( $chars ) : 0;

		// Only worthwhile for short-to-medium terms; very long terms explode the
		// pattern count without improving relevance.
		if ( $count < 3 || $count > 12 ) {
			return array();
		}

		$variants = array();

		// Substitution: one character replaced by any single character.
		for ( $i = 0; $i < $count; $i++ ) {
			$copy        = $chars;
			$copy[ $i ]  = "\0wild\0";
			$variants[]  = $copy;
		}

		// Insertion: the stored word has one extra character the visitor missed.
		for ( $i = 0; $i <= $count; $i++ ) {
			$copy       = $chars;
			array_splice( $copy, $i, 0, array( "\0wild\0" ) );
			$variants[] = $copy;
		}

		// Deletion: the visitor typed one extra character.
		for ( $i = 0; $i < $count; $i++ ) {
			$copy = $chars;
			array_splice( $copy, $i, 1 );

			if ( ! empty( $copy ) ) {
				$variants[] = $copy;
			}
		}

		global $wpdb;

		$patterns = array();

		foreach ( $variants as $variant ) {
			$inner = '';

			foreach ( $variant as $piece ) {
				$inner .= ( "\0wild\0" === $piece ) ? '_' : $wpdb->esc_like( $piece );
			}

			$patterns[ $inner ] = '%' . $inner . '%';
		}

		return array_values( $patterns );
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