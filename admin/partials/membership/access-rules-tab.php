<?php
/**
 * Access Rules tab — multi-step rule setup.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_tab_url = admin_url(
	'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=wps-membership-manage&wps_mem_tab=access-rules'
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wps_saved = ! empty( $_GET['wps_saved'] );

$wps_public_post_types = array_filter(
	get_post_types( array( 'public' => true ), 'objects' ),
	function ( $pt ) {
		return ! in_array( $pt->name, array( 'attachment', WPS_MEMBERSHIP_PLAN_CPT ), true );
	}
);

$wps_public_taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

/**
 * Build a human-readable summary for a rule card header.
 *
 * @param array $rule       Single rule array.
 * @param array $plans_map  slug => name map of active plans.
 * @param array $post_types Public post-type objects keyed by name.
 * @param array $taxonomies Public taxonomy objects keyed by name.
 * @return array { target_lbl, plans_lbl, behavior }
 */
function wps_rule_summary( $rule, $plans_map, $post_types, $taxonomies ) {
	$type = isset( $rule['target_type'] ) ? $rule['target_type'] : 'post_type';

	if ( 'post_type' === $type ) {
		$pt_key   = isset( $rule['post_type'] ) ? $rule['post_type'] : '';
		$pt_label = isset( $post_types[ $pt_key ] ) ? $post_types[ $pt_key ]->label : $pt_key;
		$target   = sprintf(
			/* translators: post type label */
			esc_html__( 'Post Type: %s', 'subscriptions-for-woocommerce' ),
			esc_html( $pt_label )
		);
	} elseif ( in_array( $type, array( 'post', 'page', 'product' ), true ) ) {
		$ids   = isset( $rule['object_ids'] ) ? (array) $rule['object_ids'] : array();
		$count = count( $ids );
		if ( $count > 0 ) {
			$titles = array();
			foreach ( array_slice( $ids, 0, 2 ) as $oid ) {
				$obj = get_post( (int) $oid );
				if ( $obj ) {
					$titles[] = $obj->post_title;
				}
			}
			$target = implode( ', ', $titles );
			if ( $count > 2 ) {
				$target .= sprintf( ' +%d', $count - 2 );
			}
		} else {
			$labels = array(
				'post'    => __( 'Specific Post(s)', 'subscriptions-for-woocommerce' ),
				'page'    => __( 'Specific Page(s)', 'subscriptions-for-woocommerce' ),
				'product' => __( 'Specific Product(s)', 'subscriptions-for-woocommerce' ),
			);
			$target = isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
		}
	} elseif ( 'taxonomy' === $type ) {
		$tax_key  = isset( $rule['taxonomy'] ) ? $rule['taxonomy'] : '';
		$tax_lbl  = isset( $taxonomies[ $tax_key ] ) ? $taxonomies[ $tax_key ]->label : $tax_key;
		$term_ids = isset( $rule['term_ids'] ) ? (array) $rule['term_ids'] : array();
		$count    = count( $term_ids );
		if ( $count > 0 ) {
			$names = array();
			foreach ( array_slice( $term_ids, 0, 2 ) as $tid ) {
				$term = get_term( (int) $tid );
				if ( $term && ! is_wp_error( $term ) ) {
					$names[] = $term->name;
				}
			}
			$target = esc_html( $tax_lbl ) . ': ' . implode( ', ', $names );
			if ( $count > 2 ) {
				$target .= sprintf( ' +%d', $count - 2 );
			}
		} else {
			$target = esc_html( $tax_lbl );
		}
	} else {
		$target = esc_html( $type );
	}

	$plans = isset( $rule['plans'] ) ? (array) $rule['plans'] : array( 'any' );
	if ( in_array( 'any', $plans, true ) ) {
		$plans_lbl = __( 'Any Plan', 'subscriptions-for-woocommerce' );
	} elseif ( 1 === count( $plans ) ) {
		$slug     = reset( $plans );
		$plan_row = isset( $plans_map[ $slug ] ) ? $plans_map[ $slug ] : null;
		if ( $plan_row ) {
			$plans_lbl = $plan_row['name'];
			$method    = $plan_row['method'];
			if ( 'subscription' === $method ) {
				$plans_lbl .= ' (' . _x( 'Sub', 'Grant method short label', 'subscriptions-for-woocommerce' ) . ')';
			} elseif ( 'auto_enroll' === $method ) {
				$plans_lbl .= ' (' . _x( 'Auto', 'Grant method short label', 'subscriptions-for-woocommerce' ) . ')';
			}
		} else {
			$plans_lbl = $slug;
		}
	} else {
		$plans_lbl = sprintf(
			/* translators: %d number of plans */
			_n( '%d Plan', '%d Plans', count( $plans ), 'subscriptions-for-woocommerce' ),
			count( $plans )
		);
	}

	return array(
		'target_lbl' => $target,
		'plans_lbl'  => $plans_lbl,
		'behavior'   => isset( $rule['behavior'] ) ? $rule['behavior'] : 'message',
	);
}

/**
 * Short display label for a grant method.
 *
 * @param string $method Grant method slug.
 * @return string
 */
function wps_grant_method_short_label( $method ) {
	$map = array(
		'purchase'     => _x( 'Buy', 'Grant method short label', 'subscriptions-for-woocommerce' ),
		'subscription' => _x( 'Sub', 'Grant method short label', 'subscriptions-for-woocommerce' ),
		'auto_enroll'  => _x( 'Auto', 'Grant method short label', 'subscriptions-for-woocommerce' ),
	);
	return isset( $map[ $method ] ) ? $map[ $method ] : '';
}

// Map: slug => [ 'name', 'method', 'color' ] — used by summary + plan pills.
$wps_plans_map = array();
foreach ( $wps_all_plans as $wps_p ) {
	$wps_plans_map[ $wps_p['slug'] ] = array(
		'name'   => $wps_p['name'],
		'method' => isset( $wps_p['grant_method'] ) ? $wps_p['grant_method'] : 'purchase',
		'color'  => isset( $wps_p['color'] ) ? $wps_p['color'] : '',
	);
}
?>

<?php if ( $wps_saved ) : ?>
	<div class="notice notice-success inline" style="margin:10px 0 16px;">
		<p><?php esc_html_e( 'Access Rules saved.', 'subscriptions-for-woocommerce' ); ?></p>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( $wps_tab_url ); ?>">
	<?php wp_nonce_field( 'wps_save_access_rules', 'wps_access_rules_nonce' ); ?>
	<input type="hidden" name="wps_save_access_rules" value="1">

	<!-- ===== Access Rules list ===== -->
	<div class="wps-access-section">
		<div class="wps-access-section__head">
			<h2 class="wps-access-section__title">
				<?php esc_html_e( 'Access Rules', 'subscriptions-for-woocommerce' ); ?>
			</h2>
			<span class="wps-access-section__desc">
				<?php
				esc_html_e(
					'Evaluated in priority order — lower number = higher priority.',
					'subscriptions-for-woocommerce'
				);
				?>
			</span>
		</div>
		<div class="wps-access-section__body">

			<div id="wps-rules-list">

			<?php if ( empty( $wps_rules ) ) : ?>
				<div class="wps-rules-empty" id="wps-rules-empty">
					<p class="wps-rules-empty__text">
						<?php
						esc_html_e(
							'No access rules yet. Click "+ Add Rule" to restrict content.',
							'subscriptions-for-woocommerce'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php
			$wps_attr_remove       = esc_attr__( 'Remove', 'subscriptions-for-woocommerce' );
			$wps_attr_prio_title   = esc_attr__(
				'Priority — lower number evaluated first',
				'subscriptions-for-woocommerce'
			);
			$wps_attr_search_title = esc_attr__( 'Search by title…', 'subscriptions-for-woocommerce' );
			$wps_attr_search_terms = esc_attr__( 'Search terms…', 'subscriptions-for-woocommerce' );
			?>

			<?php foreach ( $wps_rules as $wps_ri => $wps_rule ) : ?>
				<?php
				$wps_rule_plans    = isset( $wps_rule['plans'] ) ? (array) $wps_rule['plans'] : array( 'any' );
				$wps_rule_type     = isset( $wps_rule['target_type'] ) ? $wps_rule['target_type'] : 'post_type';
				$wps_rule_behavior = isset( $wps_rule['behavior'] ) ? $wps_rule['behavior'] : 'message';
				$wps_rule_obj_ids  = isset( $wps_rule['object_ids'] ) ? (array) $wps_rule['object_ids'] : array();
				$wps_rule_term_ids = isset( $wps_rule['term_ids'] ) ? (array) $wps_rule['term_ids'] : array();
				$wps_rule_pt       = isset( $wps_rule['post_type'] ) ? $wps_rule['post_type'] : '';
				$wps_rule_tax      = isset( $wps_rule['taxonomy'] ) ? $wps_rule['taxonomy'] : '';
				$wps_rule_priority = isset( $wps_rule['priority'] ) ? (int) $wps_rule['priority'] : 10;
				$wps_rule_enabled  = isset( $wps_rule['enabled'] ) ? $wps_rule['enabled'] : '1';
				$wps_diff_plans    = array_diff( $wps_rule_plans, array_keys( $wps_plans_map ) );
				$wps_no_plans      = (
					empty( $wps_rule_plans )
					|| ( array( 'any' ) !== $wps_rule_plans && ! empty( $wps_diff_plans ) )
				);
				$wps_smry          = wps_rule_summary(
					$wps_rule,
					$wps_plans_map,
					$wps_public_post_types,
					$wps_public_taxonomies
				);
				$wps_card_classes  = implode(
					' ',
					array_filter(
						array(
							'wps-rule-card',
							$wps_no_plans ? 'wps-rule-card--warn' : '',
							'1' !== (string) $wps_rule_enabled ? 'wps-rule-card--disabled' : '',
						)
					)
				);
				$wps_behavior_lbl  = 'redirect' === $wps_smry['behavior']
					? esc_html__( 'Redirect', 'subscriptions-for-woocommerce' )
					: esc_html__( 'Message', 'subscriptions-for-woocommerce' );
				$wps_rule_msg      = isset( $wps_rule['message'] ) ? $wps_rule['message'] : '';
				$wps_rule_redir    = isset( $wps_rule['redirect_url'] ) ? $wps_rule['redirect_url'] : '';
				$wps_opt_comments  = isset( $wps_rule['restrict_comments'] ) ? $wps_rule['restrict_comments'] : '0';
				$wps_opt_archive   = isset( $wps_rule['include_archive'] ) ? $wps_rule['include_archive'] : '0';
				$wps_opt_cta       = isset( $wps_rule['show_cta'] ) ? $wps_rule['show_cta'] : '0';
				$wps_opt_proddesc  = isset( $wps_rule['restrict_product_description'] )
					? $wps_rule['restrict_product_description']
					: '0';

				// Pre-computed field names and conditional styles (keeps HTML lines under 120 chars).
				$wps_fld_id       = esc_attr( "wps_rules[{$wps_ri}][id]" );
				$wps_fld_enabled  = esc_attr( "wps_rules[{$wps_ri}][enabled]" );
				$wps_fld_priority = esc_attr( "wps_rules[{$wps_ri}][priority]" );
				$wps_fld_ttype    = esc_attr( "wps_rules[{$wps_ri}][target_type]" );
				$wps_fld_ptype    = esc_attr( "wps_rules[{$wps_ri}][post_type]" );
				$wps_fld_obj_ids  = esc_attr( "wps_rules[{$wps_ri}][object_ids][]" );
				$wps_fld_taxonomy = esc_attr( "wps_rules[{$wps_ri}][taxonomy]" );
				$wps_fld_term_ids = esc_attr( "wps_rules[{$wps_ri}][term_ids][]" );
				$wps_fld_plans    = esc_attr( "wps_rules[{$wps_ri}][plans][]" );
				$wps_fld_behavior = esc_attr( "wps_rules[{$wps_ri}][behavior]" );
				$wps_fld_message  = esc_attr( "wps_rules[{$wps_ri}][message]" );
				$wps_fld_redir    = esc_attr( "wps_rules[{$wps_ri}][redirect_url]" );
				$wps_fld_comments = esc_attr( "wps_rules[{$wps_ri}][restrict_comments]" );
				$wps_fld_archive  = esc_attr( "wps_rules[{$wps_ri}][include_archive]" );
				$wps_fld_cta      = esc_attr( "wps_rules[{$wps_ri}][show_cta]" );
				$wps_fld_rpd      = esc_attr( "wps_rules[{$wps_ri}][restrict_product_description]" );
				$wps_div_pt       = 'post_type' !== $wps_rule_type ? ' style="display:none;"' : '';
				$wps_div_obj      = ! in_array( $wps_rule_type, array( 'post', 'page', 'product' ), true )
					? ' style="display:none;"'
					: '';
				$wps_div_tax      = 'taxonomy' !== $wps_rule_type ? ' style="display:none;"' : '';
				$wps_div_msg      = 'message' !== $wps_rule_behavior ? ' style="display:none;"' : '';
				$wps_div_redir    = 'redirect' !== $wps_rule_behavior ? ' style="display:none;"' : '';
				?>
			<div class="<?php echo esc_attr( $wps_card_classes ); ?>"
				data-index="<?php echo esc_attr( $wps_ri ); ?>">

				<input type="hidden"
					name="<?php echo $wps_fld_id; // phpcs:ignore ?>"
					value="<?php echo esc_attr( $wps_rule['id'] ); ?>">

				<div class="wps-rule-card__header">

					<label class="wps-rule-enable-toggle"
						title="<?php esc_attr_e( 'Enable / disable this rule', 'subscriptions-for-woocommerce' ); ?>">
						<input type="hidden"
							name="<?php echo $wps_fld_enabled; // phpcs:ignore ?>"
							value="0">
						<input type="checkbox"
							name="<?php echo $wps_fld_enabled; // phpcs:ignore ?>"
							value="1"
							class="wps-rule-enabled-check"
							<?php checked( '1', (string) $wps_rule_enabled ); ?>>
						<span class="wps-toggle__slider"></span>
					</label>

					<span class="wps-rule-card__drag"
						title="<?php esc_attr_e( 'Drag to reorder', 'subscriptions-for-woocommerce' ); ?>">
					</span>

					<div class="wps-rule-card__summary">
						<span class="wps-rule-card__target-lbl">
							<?php echo esc_html( $wps_smry['target_lbl'] ); ?>
						</span>
						<span class="wps-rule-card__sep">&#8594;</span>
						<span class="wps-rule-card__plans-lbl">
							<?php echo esc_html( $wps_smry['plans_lbl'] ); ?>
						</span>
						<span class="wps-badge wps-badge--<?php echo esc_attr( $wps_smry['behavior'] ); ?>">
							<?php echo esc_html( $wps_behavior_lbl ); ?>
						</span>
					</div>

					<label class="wps-rule-prio-label"
						title="<?php echo esc_attr( $wps_attr_prio_title ); ?>">
						<span class="screen-reader-text">
							<?php esc_html_e( 'Priority', 'subscriptions-for-woocommerce' ); ?>
						</span>
						<input type="number" min="1" max="999"
							name="<?php echo $wps_fld_priority; // phpcs:ignore ?>"
							value="<?php echo esc_attr( $wps_rule_priority ); ?>"
							class="wps-rule-priority">
					</label>

					<div class="wps-rule-card__actions">
						<button type="button" class="wps-rule-card__toggle" aria-expanded="false">
							<?php esc_html_e( 'Edit', 'subscriptions-for-woocommerce' ); ?>
							<i class="wps-chevron">&#9662;</i>
						</button>
						<button type="button"
							class="wps-rule-card__remove-btn wps-remove-rule"
							aria-label="<?php esc_attr_e( 'Remove rule', 'subscriptions-for-woocommerce' ); ?>">
							&times;
						</button>
					</div>
				</div>

				<div class="wps-rule-card__body" hidden>
					<div class="wps-wizard">

						<!-- Step indicators -->
						<div class="wps-wizard__steps">
							<button type="button" class="wps-wizard__step wps-wizard__step--active" data-step="1">
								<span class="wps-wizard__step-num">1</span>
								<span class="wps-wizard__step-label">
									<?php esc_html_e( 'What', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</button>
							<span class="wps-wizard__sep">›</span>
							<button type="button" class="wps-wizard__step" data-step="2">
								<span class="wps-wizard__step-num">2</span>
								<span class="wps-wizard__step-label">
									<?php esc_html_e( 'Plans', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</button>
							<span class="wps-wizard__sep">›</span>
							<button type="button" class="wps-wizard__step" data-step="3">
								<span class="wps-wizard__step-num">3</span>
								<span class="wps-wizard__step-label">
									<?php esc_html_e( 'Behavior', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</button>
							<span class="wps-wizard__sep">›</span>
							<button type="button" class="wps-wizard__step" data-step="4">
								<span class="wps-wizard__step-num">4</span>
								<span class="wps-wizard__step-label">
									<?php esc_html_e( 'Options', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</button>
						</div>

						<!-- Step 1: What to Restrict -->
						<div class="wps-wizard__panel" data-panel="1">
							<div class="wps-field-group">
								<span class="wps-field-label">
									<?php esc_html_e( 'What to Restrict', 'subscriptions-for-woocommerce' ); ?>
								</span>
								<select name="<?php echo $wps_fld_ttype; // phpcs:ignore; ?>"
									class="wps-rule-target-type">
									<option value="post_type" <?php selected( $wps_rule_type, 'post_type' ); ?>>
										<?php esc_html_e( 'Post Type', 'subscriptions-for-woocommerce' ); ?>
									</option>
									<option value="post" <?php selected( $wps_rule_type, 'post' ); ?>>
										<?php esc_html_e( 'Specific Post(s)', 'subscriptions-for-woocommerce' ); ?>
									</option>
									<option value="page" <?php selected( $wps_rule_type, 'page' ); ?>>
										<?php esc_html_e( 'Specific Page(s)', 'subscriptions-for-woocommerce' ); ?>
									</option>
									<option value="product" <?php selected( $wps_rule_type, 'product' ); ?>>
										<?php esc_html_e( 'Specific Product(s)', 'subscriptions-for-woocommerce' ); ?>
									</option>
									<option value="taxonomy" <?php selected( $wps_rule_type, 'taxonomy' ); ?>>
										<?php esc_html_e( 'Taxonomy Term(s)', 'subscriptions-for-woocommerce' ); ?>
									</option>
								</select>

								<div class="wps-target-sub wps-target-post_type"
									<?php echo $wps_div_pt; // phpcs:ignore ?>>
									<select name="<?php echo $wps_fld_ptype; // phpcs:ignore ?>"
										class="wps-rule-post-type-select">
										<?php foreach ( $wps_public_post_types as $wps_pt ) : ?>
											<option value="<?php echo esc_attr( $wps_pt->name ); ?>"
												<?php selected( $wps_rule_pt, $wps_pt->name ); ?>>
												<?php echo esc_html( $wps_pt->label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="wps-target-sub wps-target-object"
									<?php echo $wps_div_obj; // phpcs:ignore ?>>
									<div class="wps-tag-container">
										<?php foreach ( $wps_rule_obj_ids as $wps_oid ) : ?>
											<?php
											$wps_obj = get_post( (int) $wps_oid );
											if ( ! $wps_obj ) {
												continue;
											}
											?>
											<span class="wps-tag">
												<?php echo esc_html( $wps_obj->post_title ); ?>
												<input type="hidden"
													name="<?php echo $wps_fld_obj_ids; // phpcs:ignore ?>"
													value="<?php echo esc_attr( $wps_oid ); ?>">
												<button type="button" class="wps-remove-tag"
													aria-label="<?php echo esc_attr( $wps_attr_remove ); ?>">
													&times;
												</button>
											</span>
										<?php endforeach; ?>
									</div>
									<div class="wps-search-wrap" style="margin-top:4px;">
										<input type="text" class="wps-object-search wps-ajax-search"
											placeholder="<?php echo esc_attr( $wps_attr_search_title ); ?>"
											autocomplete="off">
										<span class="wps-search-spinner"></span>
										<ul class="wps-search-results"></ul>
									</div>
								</div>

								<div class="wps-target-sub wps-target-taxonomy"
									<?php echo $wps_div_tax; // phpcs:ignore ?>>
									<select name="<?php echo $wps_fld_taxonomy; // phpcs:ignore ?>"
										class="wps-rule-taxonomy-select"
										style="margin-bottom:6px;">
										<?php foreach ( $wps_public_taxonomies as $wps_tx ) : ?>
											<option value="<?php echo esc_attr( $wps_tx->name ); ?>"
												<?php selected( $wps_rule_tax, $wps_tx->name ); ?>>
												<?php echo esc_html( $wps_tx->label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="wps-tag-container">
										<?php foreach ( $wps_rule_term_ids as $wps_tid ) : ?>
											<?php
											$wps_term = get_term( (int) $wps_tid );
											if ( ! $wps_term || is_wp_error( $wps_term ) ) {
												continue;
											}
											?>
											<span class="wps-tag">
												<?php echo esc_html( $wps_term->name ); ?>
												<input type="hidden"
													name="<?php echo $wps_fld_term_ids; // phpcs:ignore ?>"
													value="<?php echo esc_attr( $wps_tid ); ?>">
												<button type="button" class="wps-remove-tag"
													aria-label="<?php echo esc_attr( $wps_attr_remove ); ?>">
													&times;
												</button>
											</span>
										<?php endforeach; ?>
									</div>
									<div class="wps-search-wrap" style="margin-top:4px;">
										<input type="text" class="wps-term-search wps-ajax-search"
											placeholder="<?php echo esc_attr( $wps_attr_search_terms ); ?>"
											autocomplete="off">
										<span class="wps-search-spinner"></span>
										<ul class="wps-search-results"></ul>
									</div>
								</div>
							</div>
						</div>

						<!-- Step 2: Required Plans -->
						<div class="wps-wizard__panel" data-panel="2" hidden>
							<div class="wps-field-group">
								<span class="wps-field-label">
									<?php esc_html_e( 'Required Plan', 'subscriptions-for-woocommerce' ); ?>
								</span>
								<div class="wps-plan-pills">
									<label class="wps-plan-pill">
										<input type="checkbox"
											name="<?php echo $wps_fld_plans; // phpcs:ignore; ?>"
											value="any"
											class="wps-plan-any-check"
											<?php echo in_array( 'any', $wps_rule_plans, true ) ? 'checked' : ''; ?>>
										<span>
											<?php esc_html_e( 'Any Plan', 'subscriptions-for-woocommerce' ); ?>
										</span>
									</label>
									<?php foreach ( $wps_all_plans as $wps_plan ) : ?>
										<?php
										$wps_pill_checked = in_array( $wps_plan['slug'], $wps_rule_plans, true )
											? 'checked'
											: '';
										$wps_pill_row     = isset( $wps_plans_map[ $wps_plan['slug'] ] )
											? $wps_plans_map[ $wps_plan['slug'] ]
											: array();
										$wps_pill_method  = isset( $wps_pill_row['method'] )
											? $wps_pill_row['method']
											: 'purchase';
										$wps_pill_color   = isset( $wps_pill_row['color'] )
											? $wps_pill_row['color']
											: '';
										$wps_method_badge = 'purchase' !== $wps_pill_method
											? wps_grant_method_short_label( $wps_pill_method )
											: '';
										?>
										<label class="wps-plan-pill">
											<input type="checkbox"
												name="<?php echo $wps_fld_plans; // phpcs:ignore; ?>"
												value="<?php echo esc_attr( $wps_plan['slug'] ); ?>"
												class="wps-plan-specific-check"
												data-grant-method="<?php echo esc_attr( $wps_pill_method ); ?>"
												<?php echo esc_attr( $wps_pill_checked ); ?>>
											<span>
												<?php if ( $wps_pill_color ) : ?>
														<?php $wps_bg = esc_attr( $wps_pill_color ); ?>
														<i class="wps-plan-pill__dot"
															style="background:<?php echo $wps_bg; // phpcs:ignore ?>;">
														</i>
												<?php endif; ?>
												<?php echo esc_html( $wps_plan['name'] ); ?>
												<?php if ( $wps_method_badge ) : ?>
													<?php
													$wps_pill_class = 'wps-plan-pill__method wps-plan-pill__method--'
														. esc_attr( $wps_pill_method );
													?>
													<em class="<?php echo esc_attr( $wps_pill_class ); ?>">
														<?php echo esc_html( $wps_method_badge ); ?>
													</em>
												<?php endif; ?>
											</span>
										</label>
									<?php endforeach; ?>
									<?php if ( empty( $wps_all_plans ) ) : ?>
										<p class="description" style="margin:0;">
											<?php
											esc_html_e(
												'No active plans. Create plans in the Membership Plans tab.',
												'subscriptions-for-woocommerce'
											);
											?>
										</p>
									<?php endif; ?>
								</div>
								<div class="wps-plan-pills__notice" style="display:none;"></div>
							</div>
						</div>

						<!-- Step 3: Behavior -->
						<div class="wps-wizard__panel" data-panel="3" hidden>
							<div class="wps-field-group">
								<span class="wps-field-label">
									<?php esc_html_e( 'When Restricted', 'subscriptions-for-woocommerce' ); ?>
								</span>
								<div class="wps-seg-control" style="margin-bottom:10px;">
									<label class="wps-seg-control__option">
										<input type="radio"
											name="<?php echo $wps_fld_behavior; // phpcs:ignore; ?>"
											value="message"
											class="wps-rule-behavior-radio"
											<?php checked( $wps_rule_behavior, 'message' ); ?>>
										<span>
											<?php esc_html_e( 'Show Message', 'subscriptions-for-woocommerce' ); ?>
										</span>
									</label>
									<label class="wps-seg-control__option">
										<input type="radio"
											name="<?php echo $wps_fld_behavior; // phpcs:ignore; ?>"
											value="redirect"
											class="wps-rule-behavior-radio"
											<?php checked( $wps_rule_behavior, 'redirect' ); ?>>
										<span>
											<?php esc_html_e( 'Redirect', 'subscriptions-for-woocommerce' ); ?>
										</span>
									</label>
								</div>

								<div class="wps-behavior-message"
									<?php echo $wps_div_msg; // phpcs:ignore ?>>
									<?php
									$wps_ph_msg = __(
										'Leave blank to use the global default message.',
										'subscriptions-for-woocommerce'
									);
									?>
									<textarea
										name="<?php echo $wps_fld_message; // phpcs:ignore; ?>"
										rows="3"
										placeholder="<?php echo esc_attr( $wps_ph_msg ); ?>">
										<?php echo esc_textarea( $wps_rule_msg ); ?>
									</textarea>
									<p class="description" style="margin-top:4px;">
										<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
											{purchase_options}
										</button>
										<?php esc_html_e( '— inserts a buy-link.', 'subscriptions-for-woocommerce' ); ?>
									</p>
								</div>

								<div class="wps-behavior-redirect"
									<?php echo $wps_div_redir; // phpcs:ignore ?>>
									<?php
									$wps_ph_url = __(
										'Leave blank to use the global default URL.',
										'subscriptions-for-woocommerce'
									);
									?>
									<input type="url"
										name="<?php echo $wps_fld_redir; // phpcs:ignore; ?>"
										value="<?php echo esc_attr( $wps_rule_redir ); ?>"
										placeholder="<?php echo esc_attr( $wps_ph_url ); ?>">
								</div>
							</div>
						</div>

						<!-- Step 4: Extra Options -->
						<div class="wps-wizard__panel" data-panel="4" hidden>
							<div class="wps-field-group">
								<span class="wps-field-label">
									<?php esc_html_e( 'Extra Options', 'subscriptions-for-woocommerce' ); ?>
								</span>
								<div class="wps-toggle-list">
									<label class="wps-toggle">
										<input type="hidden"
											name="<?php echo $wps_fld_comments; // phpcs:ignore; ?>"
											value="0">
										<input type="checkbox"
											name="<?php echo $wps_fld_comments; // phpcs:ignore; ?>"
											value="1"
											<?php checked( '1', $wps_opt_comments ); ?>>
										<span class="wps-toggle__slider"></span>
										<span class="wps-toggle__text">
											<?php
											esc_html_e(
												'Disable comments on restricted posts',
												'subscriptions-for-woocommerce'
											);
											?>
										</span>
									</label>
									<label class="wps-toggle">
										<input type="hidden"
											name="<?php echo $wps_fld_archive; // phpcs:ignore; ?>"
											value="0">
										<input type="checkbox"
											name="<?php echo $wps_fld_archive; // phpcs:ignore; ?>"
											value="1"
											<?php checked( '1', $wps_opt_archive ); ?>>
										<span class="wps-toggle__slider"></span>
										<span class="wps-toggle__text">
											<?php
											esc_html_e(
												'Show restricted posts in archives &amp; search',
												'subscriptions-for-woocommerce'
											);
											?>
										</span>
									</label>
									<label class="wps-toggle">
										<input type="hidden"
											name="<?php echo $wps_fld_cta; // phpcs:ignore; ?>"
											value="0">
										<input type="checkbox"
											name="<?php echo $wps_fld_cta; // phpcs:ignore; ?>"
											value="1"
											<?php checked( '1', $wps_opt_cta ); ?>>
										<span class="wps-toggle__slider"></span>
										<span class="wps-toggle__text">
											<?php
											esc_html_e(
												'Auto-append purchase CTA to restriction messages',
												'subscriptions-for-woocommerce'
											);
											?>
										</span>
									</label>
									<label class="wps-toggle">
										<input type="hidden"
											name="<?php echo $wps_fld_rpd; // phpcs:ignore ?>"
											value="0">
										<input type="checkbox"
											name="<?php echo $wps_fld_rpd; // phpcs:ignore ?>"
											value="1"
											<?php checked( '1', $wps_opt_proddesc ); ?>>
										<span class="wps-toggle__slider"></span>
										<span class="wps-toggle__text">
											<?php
											esc_html_e(
												'Restrict product description for non-members',
												'subscriptions-for-woocommerce'
											);
											?>
										</span>
									</label>
								</div>
							</div>
						</div>

						<div class="wps-wizard__nav">
							<button type="button" class="button wps-wizard__back" hidden>
								<?php esc_html_e( '← Back', 'subscriptions-for-woocommerce' ); ?>
							</button>
							<button type="button" class="button button-primary wps-wizard__next">
								<?php esc_html_e( 'Next →', 'subscriptions-for-woocommerce' ); ?>
							</button>
						</div>

					</div>
				</div>

			</div>
			<?php endforeach; ?>

			</div>

			<div class="wps-rules-footer">
				<button type="button" id="wps-add-rule" class="button">
					<?php esc_html_e( '+ Add Rule', 'subscriptions-for-woocommerce' ); ?>
				</button>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Access Rules', 'subscriptions-for-woocommerce' ); ?>
				</button>
			</div>

		</div>
	</div>

	<!-- New rule card template (cloned by JS) -->
	<template id="wps-rule-row-template">
		<div class="wps-rule-card wps-rule-card--open" data-index="__IDX__">
			<input type="hidden" name="wps_rules[__IDX__][id]" value="">

			<div class="wps-rule-card__header">

				<label class="wps-rule-enable-toggle"
					title="<?php esc_attr_e( 'Enable / disable this rule', 'subscriptions-for-woocommerce' ); ?>">
					<input type="hidden" name="wps_rules[__IDX__][enabled]" value="0">
					<input type="checkbox" name="wps_rules[__IDX__][enabled]" value="1"
						class="wps-rule-enabled-check" checked>
					<span class="wps-toggle__slider"></span>
				</label>

				<span class="wps-rule-card__drag"></span>

				<div class="wps-rule-card__summary">
					<span class="wps-rule-card__target-lbl">
						<?php esc_html_e( 'New Rule', 'subscriptions-for-woocommerce' ); ?>
					</span>
					<span class="wps-rule-card__sep">&#8594;</span>
					<span class="wps-rule-card__plans-lbl">
						<?php esc_html_e( 'Any Plan', 'subscriptions-for-woocommerce' ); ?>
					</span>
					<span class="wps-badge wps-badge--message wps-rule-card__behavior-badge">
						<?php esc_html_e( 'Message', 'subscriptions-for-woocommerce' ); ?>
					</span>
				</div>

				<label class="wps-rule-prio-label">
					<span class="screen-reader-text">
						<?php esc_html_e( 'Priority', 'subscriptions-for-woocommerce' ); ?>
					</span>
					<input type="number" min="1" max="999"
						name="wps_rules[__IDX__][priority]"
						value="10"
						class="wps-rule-priority">
				</label>

				<div class="wps-rule-card__actions">
					<button type="button" class="wps-rule-card__toggle" aria-expanded="true">
						<?php esc_html_e( 'Collapse', 'subscriptions-for-woocommerce' ); ?>
						<i class="wps-chevron" style="transform:rotate(180deg);">&#9662;</i>
					</button>
					<button type="button"
						class="wps-rule-card__remove-btn wps-remove-rule"
						aria-label="<?php esc_attr_e( 'Remove rule', 'subscriptions-for-woocommerce' ); ?>">
						&times;
					</button>
				</div>
			</div>

			<div class="wps-rule-card__body">
				<div class="wps-wizard">

					<div class="wps-wizard__steps">
						<button type="button" class="wps-wizard__step wps-wizard__step--active" data-step="1">
							<span class="wps-wizard__step-num">1</span>
							<span class="wps-wizard__step-label">
								<?php esc_html_e( 'What', 'subscriptions-for-woocommerce' ); ?>
							</span>
						</button>
						<span class="wps-wizard__sep">›</span>
						<button type="button" class="wps-wizard__step" data-step="2">
							<span class="wps-wizard__step-num">2</span>
							<span class="wps-wizard__step-label">
								<?php esc_html_e( 'Plans', 'subscriptions-for-woocommerce' ); ?>
							</span>
						</button>
						<span class="wps-wizard__sep">›</span>
						<button type="button" class="wps-wizard__step" data-step="3">
							<span class="wps-wizard__step-num">3</span>
							<span class="wps-wizard__step-label">
								<?php esc_html_e( 'Behavior', 'subscriptions-for-woocommerce' ); ?>
							</span>
						</button>
						<span class="wps-wizard__sep">›</span>
						<button type="button" class="wps-wizard__step" data-step="4">
							<span class="wps-wizard__step-num">4</span>
							<span class="wps-wizard__step-label">
								<?php esc_html_e( 'Options', 'subscriptions-for-woocommerce' ); ?>
							</span>
						</button>
					</div>

					<!-- Step 1: What to Restrict -->
					<div class="wps-wizard__panel" data-panel="1">
						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'What to Restrict', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<select name="wps_rules[__IDX__][target_type]" class="wps-rule-target-type">
								<option value="post_type">
									<?php esc_html_e( 'Post Type', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="post">
									<?php esc_html_e( 'Specific Post(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="page">
									<?php esc_html_e( 'Specific Page(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="product">
									<?php esc_html_e( 'Specific Product(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="taxonomy">
									<?php esc_html_e( 'Taxonomy Term(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
							</select>
							<div class="wps-target-sub wps-target-post_type">
								<select name="wps_rules[__IDX__][post_type]" class="wps-rule-post-type-select">
									<?php foreach ( $wps_public_post_types as $wps_pt ) : ?>
										<option value="<?php echo esc_attr( $wps_pt->name ); ?>">
											<?php echo esc_html( $wps_pt->label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="wps-target-sub wps-target-object" style="display:none;">
								<div class="wps-tag-container"></div>
								<div class="wps-search-wrap" style="margin-top:4px;">
									<input type="text" class="wps-object-search wps-ajax-search"
										placeholder="<?php echo esc_attr( $wps_attr_search_title ); ?>"
										autocomplete="off">
									<span class="wps-search-spinner"></span>
									<ul class="wps-search-results"></ul>
								</div>
							</div>
							<div class="wps-target-sub wps-target-taxonomy" style="display:none;">
								<select name="wps_rules[__IDX__][taxonomy]"
									class="wps-rule-taxonomy-select"
									style="margin-bottom:6px;">
									<?php foreach ( $wps_public_taxonomies as $wps_tx ) : ?>
										<option value="<?php echo esc_attr( $wps_tx->name ); ?>">
											<?php echo esc_html( $wps_tx->label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<div class="wps-tag-container"></div>
								<div class="wps-search-wrap" style="margin-top:4px;">
									<input type="text" class="wps-term-search wps-ajax-search"
										placeholder="<?php echo esc_attr( $wps_attr_search_terms ); ?>"
										autocomplete="off">
									<span class="wps-search-spinner"></span>
									<ul class="wps-search-results"></ul>
								</div>
							</div>
						</div>
					</div>

					<!-- Step 2: Required Plans -->
					<div class="wps-wizard__panel" data-panel="2" hidden>
						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'Required Plan', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<div class="wps-plan-pills">
								<label class="wps-plan-pill">
									<input type="checkbox"
										name="wps_rules[__IDX__][plans][]"
										value="any"
										class="wps-plan-any-check"
										checked>
									<span>
										<?php esc_html_e( 'Any Plan', 'subscriptions-for-woocommerce' ); ?>
									</span>
								</label>
								<?php foreach ( $wps_all_plans as $wps_plan ) : ?>
									<?php
									$wps_tpl_row    = isset( $wps_plans_map[ $wps_plan['slug'] ] )
										? $wps_plans_map[ $wps_plan['slug'] ]
										: array();
									$wps_tpl_method = isset( $wps_tpl_row['method'] )
										? $wps_tpl_row['method']
										: 'purchase';
									$wps_tpl_color  = isset( $wps_tpl_row['color'] )
										? $wps_tpl_row['color']
										: '';
									$wps_tpl_badge  = 'purchase' !== $wps_tpl_method
										? wps_grant_method_short_label( $wps_tpl_method )
										: '';
									?>
									<label class="wps-plan-pill">
										<input type="checkbox"
											name="wps_rules[__IDX__][plans][]"
											value="<?php echo esc_attr( $wps_plan['slug'] ); ?>"
											class="wps-plan-specific-check"
											data-grant-method="<?php echo esc_attr( $wps_tpl_method ); ?>">
										<span>
											<?php if ( $wps_tpl_color ) : ?>
												<i class="wps-plan-pill__dot"
													style="background:<?php echo esc_attr( $wps_tpl_color ); ?>;"></i>
											<?php endif; ?>
											<?php echo esc_html( $wps_plan['name'] ); ?>
											<?php if ( $wps_tpl_badge ) : ?>
												<em class="wps-plan-pill__method
													wps-plan-pill__method--<?php echo esc_attr( $wps_tpl_method ); ?>">
													<?php echo esc_html( $wps_tpl_badge ); ?>
												</em>
											<?php endif; ?>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
							<div class="wps-plan-pills__notice" style="display:none;"></div>
						</div>
					</div>

					<!-- Step 3: Behavior -->
					<div class="wps-wizard__panel" data-panel="3" hidden>
						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'When Restricted', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<div class="wps-seg-control" style="margin-bottom:10px;">
								<label class="wps-seg-control__option">
									<input type="radio"
										name="wps_rules[__IDX__][behavior]"
										value="message"
										class="wps-rule-behavior-radio"
										checked>
									<span>
										<?php esc_html_e( 'Show Message', 'subscriptions-for-woocommerce' ); ?>
									</span>
								</label>
								<label class="wps-seg-control__option">
									<input type="radio"
										name="wps_rules[__IDX__][behavior]"
										value="redirect"
										class="wps-rule-behavior-radio">
									<span>
										<?php esc_html_e( 'Redirect', 'subscriptions-for-woocommerce' ); ?>
									</span>
								</label>
							</div>
							<div class="wps-behavior-message">
								<?php
								$wps_ph_msg = __(
									'Leave blank to use the global default message.',
									'subscriptions-for-woocommerce'
								);
								?>
								<textarea name="wps_rules[__IDX__][message]"
									rows="3"
									placeholder="<?php echo esc_attr( $wps_ph_msg ); ?>">
								</textarea>
								<p class="description" style="margin-top:4px;">
									<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
										{purchase_options}
									</button>
									<?php esc_html_e( '— inserts a buy-link.', 'subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
							<div class="wps-behavior-redirect" style="display:none;">
								<?php
								$wps_ph_url = __(
									'Leave blank to use the global default URL.',
									'subscriptions-for-woocommerce'
								);
								?>
								<input type="url"
									name="wps_rules[__IDX__][redirect_url]"
									placeholder="<?php echo esc_attr( $wps_ph_url ); ?>">
							</div>
						</div>
					</div>

					<!-- Step 4: Extra Options -->
					<div class="wps-wizard__panel" data-panel="4" hidden>
						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'Extra Options', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<div class="wps-toggle-list">
								<label class="wps-toggle">
									<input type="hidden" name="wps_rules[__IDX__][restrict_comments]" value="0">
									<input type="checkbox" name="wps_rules[__IDX__][restrict_comments]" value="1">
									<span class="wps-toggle__slider"></span>
									<span class="wps-toggle__text">
										<?php
										esc_html_e(
											'Disable comments on restricted posts',
											'subscriptions-for-woocommerce'
										);
										?>
									</span>
								</label>
								<label class="wps-toggle">
									<input type="hidden" name="wps_rules[__IDX__][include_archive]" value="0">
									<input type="checkbox" name="wps_rules[__IDX__][include_archive]" value="1">
									<span class="wps-toggle__slider"></span>
									<span class="wps-toggle__text">
										<?php
										esc_html_e(
											'Show restricted posts in archives &amp; search',
											'subscriptions-for-woocommerce'
										);
										?>
									</span>
								</label>
								<label class="wps-toggle">
									<input type="hidden" name="wps_rules[__IDX__][show_cta]" value="0">
									<input type="checkbox" name="wps_rules[__IDX__][show_cta]" value="1">
									<span class="wps-toggle__slider"></span>
									<span class="wps-toggle__text">
										<?php
										esc_html_e(
											'Auto-append purchase CTA to restriction messages',
											'subscriptions-for-woocommerce'
										);
										?>
									</span>
								</label>
								<label class="wps-toggle">
									<input type="hidden"
										name="wps_rules[__IDX__][restrict_product_description]"
										value="0">
									<input type="checkbox"
										name="wps_rules[__IDX__][restrict_product_description]"
										value="1">
									<span class="wps-toggle__slider"></span>
									<span class="wps-toggle__text">
										<?php
										esc_html_e(
											'Restrict product description for non-members',
											'subscriptions-for-woocommerce'
										);
										?>
									</span>
								</label>
							</div>
						</div>
					</div>

					<div class="wps-wizard__nav">
						<button type="button" class="button wps-wizard__back" hidden>
							<?php esc_html_e( '← Back', 'subscriptions-for-woocommerce' ); ?>
						</button>
						<button type="button" class="button button-primary wps-wizard__next">
							<?php esc_html_e( 'Next →', 'subscriptions-for-woocommerce' ); ?>
						</button>
					</div>

				</div>
			</div>
		</div>
	</template>

</form>

<script>
( function () {
	function wpsWizardGoTo( wizard, step ) {
		var panels = wizard.querySelectorAll( '.wps-wizard__panel' );
		var total  = panels.length;

		panels.forEach( function ( p ) {
			p.hidden = parseInt( p.dataset.panel, 10 ) !== step;
		} );

		wizard.querySelectorAll( '.wps-wizard__step' ).forEach( function ( s ) {
			var n = parseInt( s.dataset.step, 10 );
			s.classList.toggle( 'wps-wizard__step--active', n === step );
			s.classList.toggle( 'wps-wizard__step--done', n < step );
		} );

		var back = wizard.querySelector( '.wps-wizard__back' );
		var next = wizard.querySelector( '.wps-wizard__next' );
		if ( back ) { back.hidden = ( 1 === step ); }
		if ( next ) {
			next.textContent = ( step === total )
				? '<?php echo esc_js( __( '✓ Done', 'subscriptions-for-woocommerce' ) ); ?>'
				: '<?php echo esc_js( __( 'Next →', 'subscriptions-for-woocommerce' ) ); ?>';
			next.dataset.done = ( step === total ) ? '1' : '';
		}
	}

	function wpsWizardCurrentStep( wizard ) {
		var active = wizard.querySelector( '.wps-wizard__panel:not([hidden])' );
		return active ? parseInt( active.dataset.panel, 10 ) : 1;
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.wps-wizard__next' );
		if ( btn ) {
			var wizard = btn.closest( '.wps-wizard' );
			if ( ! wizard ) { return; }
			var panels = wizard.querySelectorAll( '.wps-wizard__panel' );
			var cur    = wpsWizardCurrentStep( wizard );
			if ( cur >= panels.length ) {
				// Done — collapse the card.
				var card   = wizard.closest( '.wps-rule-card' );
				var body   = card && card.querySelector( '.wps-rule-card__body' );
				var toggle = card && card.querySelector( '.wps-rule-card__toggle' );
				if ( body )   { body.hidden = true; }
				if ( toggle ) {
					toggle.setAttribute( 'aria-expanded', 'false' );
					toggle.innerHTML = '<?php echo esc_js( __( 'Edit', 'subscriptions-for-woocommerce' ) ); ?>'
						+ ' <i class="wps-chevron">&#9662;</i>';
				}
			} else {
				wpsWizardGoTo( wizard, cur + 1 );
			}
			return;
		}

		var backBtn = e.target.closest( '.wps-wizard__back' );
		if ( backBtn ) {
			var wizard = backBtn.closest( '.wps-wizard' );
			if ( ! wizard ) { return; }
			var cur = wpsWizardCurrentStep( wizard );
			if ( cur > 1 ) { wpsWizardGoTo( wizard, cur - 1 ); }
			return;
		}

		var stepBtn = e.target.closest( '.wps-wizard__step' );
		if ( stepBtn ) {
			var wizard = stepBtn.closest( '.wps-wizard' );
			if ( wizard ) { wpsWizardGoTo( wizard, parseInt( stepBtn.dataset.step, 10 ) ); }
			return;
		}

		// Reset to step 1 when a card is opened via the Edit toggle.
		var toggleBtn = e.target.closest( '.wps-rule-card__toggle' );
		if ( toggleBtn ) {
			requestAnimationFrame( function () {
				var card   = toggleBtn.closest( '.wps-rule-card' );
				var body   = card && card.querySelector( '.wps-rule-card__body' );
				if ( ! body || body.hidden ) { return; }
				var wizard = body.querySelector( '.wps-wizard' );
				if ( wizard ) { wpsWizardGoTo( wizard, 1 ); }
			} );
		}
	} );
} )();
</script>
