<?php
/**
 * Access Rules tab content — card layout (Day 12).
 *
 * Variables injected by the wrapper partial (wps-membership-access-rules.php):
 *   $wps_all_plans  array  Active plans from wps_get_all_plans( 'active' ).
 *   $wps_rules      array  Current rules from wps_get_access_rules().
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_tab_url = admin_url(
	'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=wps-membership-access-rules'
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wps_saved = ! empty( $_GET['wps_saved'] );

$wps_def_behavior          = get_option( 'wps_access_default_behavior', 'message' );
$wps_def_logged_out_msg    = get_option( 'wps_access_logged_out_message', '' );
$wps_def_wrong_plan_msg    = get_option( 'wps_access_wrong_plan_message', '' );
$wps_def_redirect_url      = get_option( 'wps_access_redirect_url', '' );
$wps_def_restrict_comments = get_option( 'wps_access_restrict_comments', '0' );
$wps_def_include_archive   = get_option( 'wps_access_include_in_archive', '0' );
$wps_def_show_cta          = get_option( 'wps_access_show_purchase_cta', '1' );

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
 * @param array $post_types Public post-type objects (keyed by name).
 * @param array $taxonomies Public taxonomy objects (keyed by name).
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
		$slug      = reset( $plans );
		$plans_lbl = isset( $plans_map[ $slug ] ) ? $plans_map[ $slug ] : $slug;
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

$wps_plans_map = array();
foreach ( $wps_all_plans as $wps_p ) {
	$wps_plans_map[ $wps_p['slug'] ] = $wps_p['name'];
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

	<!-- Global Defaults -->
	<div class="wps-access-section">
		<div class="wps-access-section__head">
			<h2 class="wps-access-section__title">
				<?php esc_html_e( 'Global Defaults', 'subscriptions-for-woocommerce' ); ?>
			</h2>
			<p class="wps-access-section__desc">
				<?php esc_html_e( 'Fallback values when a rule has no message or redirect URL of its own.', 'subscriptions-for-woocommerce' ); ?>
			</p>
		</div>
		<div class="wps-access-section__body">
			<div class="wps-defaults-grid">

				<div class="wps-defaults-grid__label">
					<label for="wps_access_default_behavior">
						<?php esc_html_e( 'Default Behavior', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</div>
				<div class="wps-defaults-grid__field">
					<select id="wps_access_default_behavior"
						name="wps_access_default_behavior">
						<option value="message"
							<?php selected( $wps_def_behavior, 'message' ); ?>>
							<?php esc_html_e( 'Show Message', 'subscriptions-for-woocommerce' ); ?>
						</option>
						<option value="redirect"
							<?php selected( $wps_def_behavior, 'redirect' ); ?>>
							<?php esc_html_e( 'Redirect', 'subscriptions-for-woocommerce' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'What happens when a rule has no specific message or URL.', 'subscriptions-for-woocommerce' ); ?>
					</p>
				</div>

				<div class="wps-defaults-grid__label">
					<label for="wps_access_logged_out_message">
						<?php esc_html_e( 'Logged-Out Message', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</div>
				<div class="wps-defaults-grid__field">
					<textarea id="wps_access_logged_out_message"
						name="wps_access_logged_out_message"
						rows="3"><?php echo esc_textarea( $wps_def_logged_out_msg ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Shown to visitors who are not logged in.', 'subscriptions-for-woocommerce' ); ?>
						<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
							{purchase_options}
						</button>
					</p>
				</div>

				<div class="wps-defaults-grid__label">
					<label for="wps_access_wrong_plan_message">
						<?php esc_html_e( 'Wrong Plan Message', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</div>
				<div class="wps-defaults-grid__field">
					<textarea id="wps_access_wrong_plan_message"
						name="wps_access_wrong_plan_message"
						rows="3"><?php echo esc_textarea( $wps_def_wrong_plan_msg ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Shown to logged-in users who lack the required plan.', 'subscriptions-for-woocommerce' ); ?>
						<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
							{purchase_options}
						</button>
					</p>
				</div>

				<div class="wps-defaults-grid__label">
					<label for="wps_access_redirect_url">
						<?php esc_html_e( 'Default Redirect URL', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</div>
				<div class="wps-defaults-grid__field">
					<input type="url" id="wps_access_redirect_url"
						name="wps_access_redirect_url"
						value="<?php echo esc_attr( $wps_def_redirect_url ); ?>"
						placeholder="https://">
					<p class="description">
						<?php esc_html_e( 'Used when default behavior is Redirect and the rule has no specific URL.', 'subscriptions-for-woocommerce' ); ?>
					</p>
				</div>

				<div class="wps-defaults-grid__label">
					<?php esc_html_e( 'Content Options', 'subscriptions-for-woocommerce' ); ?>
				</div>
				<div class="wps-defaults-grid__field">
					<div class="wps-toggle-list">
						<label class="wps-toggle">
							<input type="checkbox" name="wps_access_restrict_comments"
								value="1"
								<?php checked( '1', $wps_def_restrict_comments ); ?>>
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
							<input type="checkbox" name="wps_access_include_in_archive"
								value="1"
								<?php checked( '1', $wps_def_include_archive ); ?>>
							<span class="wps-toggle__slider"></span>
							<span class="wps-toggle__text">
								<?php
								esc_html_e(
									'Show restricted posts in archive and search results',
									'subscriptions-for-woocommerce'
								);
								?>
							</span>
						</label>
						<label class="wps-toggle">
							<input type="checkbox" name="wps_access_show_purchase_cta"
								value="1"
								<?php checked( '1', $wps_def_show_cta ); ?>>
							<span class="wps-toggle__slider"></span>
							<span class="wps-toggle__text">
								<?php
								esc_html_e(
									'Auto-append purchase CTA when {purchase_options} is not in the message',
									'subscriptions-for-woocommerce'
								);
								?>
							</span>
						</label>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- Access Rules -->
	<div class="wps-access-section">
		<div class="wps-access-section__head">
			<h2 class="wps-access-section__title">
				<?php esc_html_e( 'Access Rules', 'subscriptions-for-woocommerce' ); ?>
			</h2>
			<p class="wps-access-section__desc">
				<?php esc_html_e( 'Evaluated in priority order — lower number = higher priority.', 'subscriptions-for-woocommerce' ); ?>
			</p>
		</div>
		<div class="wps-access-section__body">

			<div id="wps-rules-list">

			<?php if ( empty( $wps_rules ) ) : ?>
				<div class="wps-rules-empty" id="wps-rules-empty">
					<p class="wps-rules-empty__text">
						<?php esc_html_e( 'No access rules yet. Click "+ Add Rule" to restrict content.', 'subscriptions-for-woocommerce' ); ?>
					</p>
				</div>
			<?php endif; ?>

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
				$wps_diff_plans    = array_diff( $wps_rule_plans, array_keys( $wps_plans_map ) );
				$wps_no_plans      = ( empty( $wps_rule_plans )
					|| ( array( 'any' ) !== $wps_rule_plans && ! empty( $wps_diff_plans ) ) );
				$wps_smry          = wps_rule_summary(
					$wps_rule,
					$wps_plans_map,
					$wps_public_post_types,
					$wps_public_taxonomies
				);
				$wps_card_class    = $wps_no_plans ? 'wps-rule-card wps-rule-card--warn' : 'wps-rule-card';
				$wps_behavior_lbl  = 'redirect' === $wps_smry['behavior']
					? esc_html__( 'Redirect', 'subscriptions-for-woocommerce' )
					: esc_html__( 'Message', 'subscriptions-for-woocommerce' );
				?>
			<div class="<?php echo esc_attr( $wps_card_class ); ?>"
				data-index="<?php echo esc_attr( $wps_ri ); ?>">

				<input type="hidden"
					name="<?php echo esc_attr( "wps_rules[{$wps_ri}][id]" ); ?>"
					value="<?php echo esc_attr( $wps_rule['id'] ); ?>">

				<div class="wps-rule-card__header">
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
					<span class="wps-rule-card__prio">
						<?php
						/* translators: %d priority number */
						printf( esc_html__( 'Priority: %d', 'subscriptions-for-woocommerce' ), (int) $wps_rule_priority );
						?>
					</span>
					<div class="wps-rule-card__actions">
						<button type="button" class="wps-rule-card__toggle" aria-expanded="false">
							<?php esc_html_e( 'Edit', 'subscriptions-for-woocommerce' ); ?>
							<i class="wps-chevron">&#9662;</i>
						</button>
						<button type="button"
							class="wps-rule-card__remove-btn wps-remove-rule"
							aria-label="<?php esc_attr_e( 'Remove rule', 'subscriptions-for-woocommerce' ); ?>"
							title="<?php esc_attr_e( 'Remove rule', 'subscriptions-for-woocommerce' ); ?>">
							&times;
						</button>
					</div>
				</div>

				<div class="wps-rule-card__body" hidden>
					<div class="wps-rule-fields">

						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'Target Type', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<select name="<?php echo esc_attr( "wps_rules[{$wps_ri}][target_type]" ); ?>"
								class="wps-rule-target-type">
								<option value="post_type"
									<?php selected( $wps_rule_type, 'post_type' ); ?>>
									<?php esc_html_e( 'Post Type', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="post"
									<?php selected( $wps_rule_type, 'post' ); ?>>
									<?php esc_html_e( 'Specific Post(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="page"
									<?php selected( $wps_rule_type, 'page' ); ?>>
									<?php esc_html_e( 'Specific Page(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="product"
									<?php selected( $wps_rule_type, 'product' ); ?>>
									<?php esc_html_e( 'Specific Product(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
								<option value="taxonomy"
									<?php selected( $wps_rule_type, 'taxonomy' ); ?>>
									<?php esc_html_e( 'Taxonomy Term(s)', 'subscriptions-for-woocommerce' ); ?>
								</option>
							</select>

							<?php
							$wps_pt_hide = 'post_type' !== $wps_rule_type ? 'style="display:none;"' : '';
							?>
							<div class="wps-target-sub wps-target-post_type" <?php echo esc_attr( $wps_pt_hide ); ?>>
								<select name="<?php echo esc_attr( "wps_rules[{$wps_ri}][post_type]" ); ?>"
									class="wps-rule-post-type-select">
									<?php foreach ( $wps_public_post_types as $wps_pt ) : ?>
										<option value="<?php echo esc_attr( $wps_pt->name ); ?>"
											<?php selected( $wps_rule_pt, $wps_pt->name ); ?>>
											<?php echo esc_html( $wps_pt->label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<?php
							$wps_obj_types = array( 'post', 'page', 'product' );
							$wps_obj_hide  = ! in_array( $wps_rule_type, $wps_obj_types, true )
								? 'style="display:none;"'
								: '';
							?>
							<div class="wps-target-sub wps-target-object" <?php echo esc_attr( $wps_obj_hide ); ?>>
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
												name="<?php echo esc_attr( "wps_rules[{$wps_ri}][object_ids][]" ); ?>"
												value="<?php echo esc_attr( $wps_oid ); ?>">
											<button type="button" class="wps-remove-tag"
												aria-label="<?php esc_attr_e( 'Remove', 'subscriptions-for-woocommerce' ); ?>">
												&times;
											</button>
										</span>
									<?php endforeach; ?>
								</div>
								<div class="wps-search-wrap" style="margin-top:4px;">
									<input type="text" class="wps-object-search wps-ajax-search"
										placeholder="<?php esc_attr_e( 'Search by title…', 'subscriptions-for-woocommerce' ); ?>"
										autocomplete="off">
									<span class="wps-search-spinner"></span>
									<ul class="wps-search-results"></ul>
								</div>
							</div>

							<?php
							$wps_tax_hide = 'taxonomy' !== $wps_rule_type ? 'style="display:none;"' : '';
							?>
							<div class="wps-target-sub wps-target-taxonomy" <?php echo esc_attr( $wps_tax_hide ); ?>>
								<select name="<?php echo esc_attr( "wps_rules[{$wps_ri}][taxonomy]" ); ?>"
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
												name="<?php echo esc_attr( "wps_rules[{$wps_ri}][term_ids][]" ); ?>"
												value="<?php echo esc_attr( $wps_tid ); ?>">
											<button type="button" class="wps-remove-tag"
												aria-label="<?php esc_attr_e( 'Remove', 'subscriptions-for-woocommerce' ); ?>">
												&times;
											</button>
										</span>
									<?php endforeach; ?>
								</div>
								<div class="wps-search-wrap" style="margin-top:4px;">
									<input type="text" class="wps-term-search wps-ajax-search"
										placeholder="<?php esc_attr_e( 'Search terms…', 'subscriptions-for-woocommerce' ); ?>"
										autocomplete="off">
									<span class="wps-search-spinner"></span>
									<ul class="wps-search-results"></ul>
								</div>
							</div>

						</div>

						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'Required Plans', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<div class="wps-plans-list">
								<label class="wps-toggle">
									<input type="checkbox"
										name="<?php echo esc_attr( "wps_rules[{$wps_ri}][plans][]" ); ?>"
										value="any"
										class="wps-plan-any-check"
										<?php echo in_array( 'any', $wps_rule_plans, true ) ? 'checked' : ''; ?>>
									<span class="wps-toggle__slider"></span>
									<span class="wps-toggle__text">
										<?php esc_html_e( '— Any Plan —', 'subscriptions-for-woocommerce' ); ?>
									</span>
								</label>
								<?php foreach ( $wps_all_plans as $wps_plan ) : ?>
									<label class="wps-toggle">
										<input type="checkbox"
											name="<?php echo esc_attr( "wps_rules[{$wps_ri}][plans][]" ); ?>"
											value="<?php echo esc_attr( $wps_plan['slug'] ); ?>"
											class="wps-plan-specific-check"
											<?php echo in_array( $wps_plan['slug'], $wps_rule_plans, true ) ? 'checked' : ''; ?>>
										<span class="wps-toggle__slider"></span>
										<span class="wps-toggle__text">
											<?php echo esc_html( $wps_plan['name'] ); ?>
										</span>
									</label>
								<?php endforeach; ?>
								<?php if ( empty( $wps_all_plans ) ) : ?>
									<p class="description" style="margin:0;">
										<?php esc_html_e( 'No active plans. Create plans in the Membership Plans tab.', 'subscriptions-for-woocommerce' ); ?>
									</p>
								<?php endif; ?>
							</div>
						</div>

						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'Behavior', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<div class="wps-behavior-radios">
								<label>
									<input type="radio"
										name="<?php echo esc_attr( "wps_rules[{$wps_ri}][behavior]" ); ?>"
										value="message"
										class="wps-rule-behavior-radio"
										<?php checked( $wps_rule_behavior, 'message' ); ?>>
									<?php esc_html_e( 'Show Message', 'subscriptions-for-woocommerce' ); ?>
								</label>
								<label>
									<input type="radio"
										name="<?php echo esc_attr( "wps_rules[{$wps_ri}][behavior]" ); ?>"
										value="redirect"
										class="wps-rule-behavior-radio"
										<?php checked( $wps_rule_behavior, 'redirect' ); ?>>
									<?php esc_html_e( 'Redirect', 'subscriptions-for-woocommerce' ); ?>
								</label>
							</div>
						</div>

						<?php
						$wps_msg_hide = 'message' !== $wps_rule_behavior ? 'style="display:none;"' : '';
						?>
						<div class="wps-field-group wps-behavior-message" <?php echo esc_attr( $wps_msg_hide ); ?>>
							<span class="wps-field-label">
								<?php esc_html_e( 'Custom Message', 'subscriptions-for-woocommerce' ); ?>
								<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
									{purchase_options}
								</button>
							</span>
							<textarea
								name="<?php echo esc_attr( "wps_rules[{$wps_ri}][message]" ); ?>"
								rows="3"
								placeholder="<?php esc_attr_e( 'Leave blank to use the global default.', 'subscriptions-for-woocommerce' ); ?>">
								<?php echo esc_textarea( isset( $wps_rule['message'] ) ? $wps_rule['message'] : '' ); ?>
							</textarea>
						</div>

						<?php
						$wps_redir_hide = 'redirect' !== $wps_rule_behavior ? 'style="display:none;"' : '';
						?>
						<div class="wps-field-group wps-behavior-redirect" <?php echo esc_attr( $wps_redir_hide ); ?>>
							<span class="wps-field-label">
								<?php esc_html_e( 'Redirect URL', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<input type="url"
								name="<?php echo esc_attr( "wps_rules[{$wps_ri}][redirect_url]" ); ?>"
								value="<?php echo esc_attr( isset( $wps_rule['redirect_url'] ) ? $wps_rule['redirect_url'] : '' ); ?>"
								placeholder="<?php esc_attr_e( 'Leave blank for global default.', 'subscriptions-for-woocommerce' ); ?>">
						</div>

						<div class="wps-field-group">
							<span class="wps-field-label">
								<?php esc_html_e( 'Priority', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<div style="display:flex;align-items:center;gap:8px;">
								<input type="number" min="1" max="999"
									name="<?php echo esc_attr( "wps_rules[{$wps_ri}][priority]" ); ?>"
									value="<?php echo esc_attr( $wps_rule_priority ); ?>"
									class="wps-rule-priority"
									style="width:80px;">
								<span class="description">
									<?php esc_html_e( 'Lower = higher priority', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</div>
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
				<span class="wps-rule-card__prio">
					<?php esc_html_e( 'Priority: 10', 'subscriptions-for-woocommerce' ); ?>
				</span>
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
				<div class="wps-rule-fields">

					<div class="wps-field-group">
						<span class="wps-field-label">
							<?php esc_html_e( 'Target Type', 'subscriptions-for-woocommerce' ); ?>
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
							<select name="wps_rules[__IDX__][post_type]"
								class="wps-rule-post-type-select">
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
									placeholder="<?php esc_attr_e( 'Search by title…', 'subscriptions-for-woocommerce' ); ?>"
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
									placeholder="<?php esc_attr_e( 'Search terms…', 'subscriptions-for-woocommerce' ); ?>"
									autocomplete="off">
								<span class="wps-search-spinner"></span>
								<ul class="wps-search-results"></ul>
							</div>
						</div>
					</div>

					<div class="wps-field-group">
						<span class="wps-field-label">
							<?php esc_html_e( 'Required Plans', 'subscriptions-for-woocommerce' ); ?>
						</span>
						<div class="wps-plans-list">
							<label class="wps-toggle">
								<input type="checkbox"
									name="wps_rules[__IDX__][plans][]"
									value="any"
									class="wps-plan-any-check"
									checked>
								<span class="wps-toggle__slider"></span>
								<span class="wps-toggle__text">
									<?php esc_html_e( '— Any Plan —', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</label>
							<?php foreach ( $wps_all_plans as $wps_plan ) : ?>
								<label class="wps-toggle">
									<input type="checkbox"
										name="wps_rules[__IDX__][plans][]"
										value="<?php echo esc_attr( $wps_plan['slug'] ); ?>"
										class="wps-plan-specific-check">
									<span class="wps-toggle__slider"></span>
									<span class="wps-toggle__text">
										<?php echo esc_html( $wps_plan['name'] ); ?>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="wps-field-group">
						<span class="wps-field-label">
							<?php esc_html_e( 'Behavior', 'subscriptions-for-woocommerce' ); ?>
						</span>
						<div class="wps-behavior-radios">
							<label>
								<input type="radio"
									name="wps_rules[__IDX__][behavior]"
									value="message"
									class="wps-rule-behavior-radio"
									checked>
								<?php esc_html_e( 'Show Message', 'subscriptions-for-woocommerce' ); ?>
							</label>
							<label>
								<input type="radio"
									name="wps_rules[__IDX__][behavior]"
									value="redirect"
									class="wps-rule-behavior-radio">
								<?php esc_html_e( 'Redirect', 'subscriptions-for-woocommerce' ); ?>
							</label>
						</div>
					</div>

					<div class="wps-field-group wps-behavior-message">
						<span class="wps-field-label">
							<?php esc_html_e( 'Custom Message', 'subscriptions-for-woocommerce' ); ?>
							<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
								{purchase_options}
							</button>
						</span>
						<textarea name="wps_rules[__IDX__][message]"
							rows="3"
							placeholder="<?php esc_attr_e( 'Leave blank to use the global default.', 'subscriptions-for-woocommerce' ); ?>">
						</textarea>
					</div>

					<div class="wps-field-group wps-behavior-redirect" style="display:none;">
						<span class="wps-field-label">
							<?php esc_html_e( 'Redirect URL', 'subscriptions-for-woocommerce' ); ?>
						</span>
						<input type="url"
							name="wps_rules[__IDX__][redirect_url]"
							placeholder="<?php esc_attr_e( 'Leave blank for global default.', 'subscriptions-for-woocommerce' ); ?>">
					</div>

					<div class="wps-field-group">
						<span class="wps-field-label">
							<?php esc_html_e( 'Priority', 'subscriptions-for-woocommerce' ); ?>
						</span>
						<div style="display:flex;align-items:center;gap:8px;">
							<input type="number" min="1" max="999"
								name="wps_rules[__IDX__][priority]"
								value="10"
								class="wps-rule-priority"
								style="width:80px;">
							<span class="description">
								<?php esc_html_e( 'Lower = higher priority', 'subscriptions-for-woocommerce' ); ?>
							</span>
						</div>
					</div>

				</div>
			</div>
		</div>
	</template>

</form>
