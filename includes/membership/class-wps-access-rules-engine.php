<?php
/**
 * Membership Layer — Access Rules Engine (Day 11)
 *
 * Builds and queries the `wps_access_rules_index` lookup map.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Access_Rules_Engine' ) ) {

	/**
	 * Builds and queries the access-rules index for O(1)-ish enforcement.
	 *
	 * The index (stored in `wps_access_rules_index`, autoloaded) has three buckets:
	 *   'object'    — specific post/product IDs → rule IDs
	 *   'term'      — 'taxonomy:term_id' keys → rule IDs
	 *   'post_type' — post type slugs → rule IDs
	 *
	 * @since 2.0.0
	 */
	class WPS_Access_Rules_Engine {

		/**
		 * Rebuild the `wps_access_rules_index` option from the current rule set.
		 *
		 * Called on every rule save so the enforcement layer never scans the
		 * full rules array at runtime. The option is autoloaded so it is always
		 * available in memory by the time the enforcer runs.
		 *
		 * @since 2.0.0
		 */
		public function rebuild_index() {
			$rules = get_option( WPS_ACCESS_RULES_OPTION, array() );
			if ( ! is_array( $rules ) ) {
				$rules = array();
			}

			$index = array(
				'object'    => array(),
				'term'      => array(),
				'post_type' => array(),
			);

			foreach ( $rules as $rule ) {
				if ( ! isset( $rule['id'] ) || '' === $rule['id'] ) {
					continue;
				}

				$id          = $rule['id'];
				$target_type = isset( $rule['target_type'] ) ? $rule['target_type'] : '';

				switch ( $target_type ) {
					case 'post':
					case 'page':
					case 'product':
						$object_ids = isset( $rule['object_ids'] ) && is_array( $rule['object_ids'] )
							? $rule['object_ids']
							: array();

						if ( ! empty( $object_ids ) ) {
							foreach ( $object_ids as $oid ) {
								$oid = absint( $oid );
								if ( $oid > 0 ) {
									$index['object'][ $oid ][] = $id;
								}
							}
						} else {
							// No specific IDs → restrict the whole post type.
							$pt = $this->target_type_to_post_type( $target_type );
							if ( $pt ) {
								$index['post_type'][ $pt ][] = $id;
							}
						}
						break;

					case 'post_type':
						$pt = isset( $rule['post_type'] ) ? sanitize_key( $rule['post_type'] ) : '';
						if ( $pt ) {
							$index['post_type'][ $pt ][] = $id;
						}
						break;

					case 'taxonomy':
						$taxonomy = isset( $rule['taxonomy'] ) ? sanitize_key( $rule['taxonomy'] ) : '';
						$term_ids = isset( $rule['term_ids'] ) && is_array( $rule['term_ids'] )
							? $rule['term_ids']
							: array();

						if ( $taxonomy && ! empty( $term_ids ) ) {
							foreach ( $term_ids as $tid ) {
								$tid = absint( $tid );
								if ( $tid > 0 ) {
									$index['term'][ $taxonomy . ':' . $tid ][] = $id;
								}
							}
						}
						break;
				}
			}

			// Autoloaded — the enforcement layer reads this on every restricted request.
			update_option( WPS_ACCESS_RULES_INDEX_OPTION, $index, true );
		}

		/**
		 * Return matching rule arrays for a given post object.
		 *
		 * Checks the index in three passes:
		 *   1. Exact post ID match ('object' bucket).
		 *   2. Taxonomy term match ('term' bucket) for every taxonomy attached to the post.
		 *   3. Whole post-type match ('post_type' bucket).
		 *
		 * Matches are de-duplicated by rule ID and sorted by priority (ascending;
		 * lower number = evaluated first).
		 *
		 * @since  2.0.0
		 * @param  WP_Post $post Post to check.
		 * @return array Ordered array of matching rule arrays (may be empty).
		 */
		public function get_rules_for_object( WP_Post $post ) {
			$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION, array() );
			if ( ! is_array( $index ) ) {
				return array();
			}

			$matched_ids = array();

			// Pass 1: exact object ID.
			if ( ! empty( $index['object'][ $post->ID ] ) ) {
				foreach ( (array) $index['object'][ $post->ID ] as $rule_id ) {
					$matched_ids[ $rule_id ] = true;
				}
			}

			// Pass 2: taxonomy terms attached to this post.
			$taxonomies = get_object_taxonomies( $post->post_type );
			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_the_terms( $post->ID, $taxonomy );
				if ( ! is_array( $terms ) ) {
					continue;
				}
				foreach ( $terms as $term ) {
					$key = $taxonomy . ':' . $term->term_id;
					if ( ! empty( $index['term'][ $key ] ) ) {
						foreach ( (array) $index['term'][ $key ] as $rule_id ) {
							$matched_ids[ $rule_id ] = true;
						}
					}
				}
			}

			// Pass 3: whole post type.
			if ( ! empty( $index['post_type'][ $post->post_type ] ) ) {
				foreach ( (array) $index['post_type'][ $post->post_type ] as $rule_id ) {
					$matched_ids[ $rule_id ] = true;
				}
			}

			if ( empty( $matched_ids ) ) {
				return array();
			}

			// Load matching rules by ID and sort by priority.
			$all_rules   = function_exists( 'wps_get_access_rules' )
				? wps_get_access_rules()
				: (array) get_option( WPS_ACCESS_RULES_OPTION, array() );
			$matched_ids = array_keys( $matched_ids );
			$matched     = array();

			foreach ( $all_rules as $rule ) {
				if ( isset( $rule['id'] ) && in_array( $rule['id'], $matched_ids, true ) ) {
					$matched[] = $rule;
				}
			}

			usort(
				$matched,
				function ( $a, $b ) {
					$pa = isset( $a['priority'] ) ? absint( $a['priority'] ) : 10;
					$pb = isset( $b['priority'] ) ? absint( $b['priority'] ) : 10;
					return $pa - $pb;
				}
			);

			return $matched;
		}

		/**
		 * Map a target_type shorthand to its WordPress post type slug.
		 *
		 * @since  2.0.0
		 * @param  string $target_type One of: post, page, product.
		 * @return string|null Post type slug, or null for unmapped types.
		 */
		private function target_type_to_post_type( $target_type ) {
			$map = array(
				'post'    => 'post',
				'page'    => 'page',
				'product' => 'product',
			);
			return isset( $map[ $target_type ] ) ? $map[ $target_type ] : null;
		}
	}
}
