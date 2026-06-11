<?php
/**
 * Access Rules — single rule card (Content or Product).
 *
 * One reusable card used both for server-rendered saved rules and for the two
 * JS clone <template>s. The card's shape is driven by $wps_kind:
 *
 *   'content' — What → Plans → Behavior → Options wizard. Targets posts, pages,
 *               whole post types, or taxonomy terms; behaviors message /
 *               redirect / template.
 *   'product' — Products → Plans wizard. Targets products or product categories;
 *               always blocks add-to-cart (no behavior selector, no content-only
 *               options). This keeps product and content config from mixing.
 *
 * Expected in scope (set by the including template):
 *   string $wps_idx               Row index token — a real integer or '__IDX__'.
 *   string $wps_kind              'content' | 'product'.
 *   array  $wps_rule              The rule array (empty for the clone templates).
 *   array  $wps_all_plans         Active plans.
 *   array  $wps_plans_map         slug => [name, method, color].
 *   array  $wps_public_post_types Public post-type objects (content cards).
 *   array  $wps_public_taxonomies Public taxonomy objects.
 *   string $wps_attr_remove / $wps_attr_prio_title / $wps_attr_search_title /
 *          $wps_attr_search_terms  Pre-escaped attribute strings.
 *   string $wps_tpl_lock_cls / $wps_tpl_disabled  Pro-lock classes for Template.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_kind = isset( $wps_kind ) && in_array( $wps_kind, array( 'content', 'product' ), true )
	? $wps_kind
	: 'content';
$wps_rule = isset( $wps_rule ) && is_array( $wps_rule ) ? $wps_rule : array();
$wps_idx  = isset( $wps_idx ) ? $wps_idx : '__IDX__';

$wps_is_product = ( 'product' === $wps_kind );

// Current values with defaults matching wps_sanitize_access_rule().
$wps_rule_plans    = isset( $wps_rule['plans'] ) ? (array) $wps_rule['plans'] : array( 'any' );
$wps_rule_behavior = isset( $wps_rule['behavior'] ) ? $wps_rule['behavior'] : 'message';
$wps_rule_obj_ids  = isset( $wps_rule['object_ids'] ) ? (array) $wps_rule['object_ids'] : array();
$wps_rule_term_ids = isset( $wps_rule['term_ids'] ) ? (array) $wps_rule['term_ids'] : array();
$wps_rule_pt       = isset( $wps_rule['post_type'] ) ? $wps_rule['post_type'] : '';
$wps_rule_tax      = isset( $wps_rule['taxonomy'] ) ? $wps_rule['taxonomy'] : ( $wps_is_product ? 'product_cat' : '' );
$wps_rule_priority = isset( $wps_rule['priority'] ) ? (int) $wps_rule['priority'] : 10;
$wps_rule_enabled  = isset( $wps_rule['enabled'] ) ? $wps_rule['enabled'] : '1';
$wps_rule_msg      = isset( $wps_rule['message'] ) ? $wps_rule['message'] : '';
$wps_rule_redir    = isset( $wps_rule['redirect_url'] ) ? $wps_rule['redirect_url'] : '';
$wps_opt_comments  = isset( $wps_rule['restrict_comments'] ) ? $wps_rule['restrict_comments'] : '0';
$wps_opt_archive   = isset( $wps_rule['include_archive'] ) ? $wps_rule['include_archive'] : '0';
$wps_opt_cta       = isset( $wps_rule['show_cta'] ) ? $wps_rule['show_cta'] : '0';

// Default target type per kind.
if ( $wps_is_product ) {
	$wps_rule_type = isset( $wps_rule['target_type'] ) ? $wps_rule['target_type'] : 'product';
} else {
	$wps_rule_type = isset( $wps_rule['target_type'] ) ? $wps_rule['target_type'] : 'post_type';
}

// Warn styling when the rule references plans that no longer exist.
$wps_diff_plans   = array_diff( $wps_rule_plans, array_keys( $wps_plans_map ) );
$wps_no_plans     = (
	empty( $wps_rule_plans )
	|| ( array( 'any' ) !== $wps_rule_plans && ! empty( $wps_diff_plans ) )
);
$wps_card_classes = implode(
	' ',
	array_filter(
		array(
			'wps-rule-card',
			'wps-rule-card--' . $wps_kind,
			$wps_no_plans ? 'wps-rule-card--warn' : '',
			'1' !== (string) $wps_rule_enabled ? 'wps-rule-card--disabled' : '',
		)
	)
);

// Header summary.
if ( ! empty( $wps_rule ) && function_exists( 'wps_rule_summary' ) ) {
	$wps_smry       = wps_rule_summary( $wps_rule, $wps_plans_map, $wps_public_post_types, $wps_public_taxonomies );
	$wps_target_lbl = $wps_smry['target_lbl'];
	$wps_plans_lbl  = $wps_smry['plans_lbl'];
} else {
	$wps_target_lbl = $wps_is_product
		? esc_html__( 'New Product Rule', 'subscriptions-for-woocommerce' )
		: esc_html__( 'New Content Rule', 'subscriptions-for-woocommerce' );
	$wps_plans_lbl  = esc_html__( 'Any Plan', 'subscriptions-for-woocommerce' );
}

// Behavior badge label.
if ( $wps_is_product ) {
	$wps_behavior_lbl   = esc_html__( 'Blocks purchase', 'subscriptions-for-woocommerce' );
	$wps_behavior_class = 'product';
} elseif ( 'redirect' === $wps_rule_behavior ) {
	$wps_behavior_lbl   = esc_html__( 'Redirect', 'subscriptions-for-woocommerce' );
	$wps_behavior_class = 'redirect';
} elseif ( 'template' === $wps_rule_behavior ) {
	$wps_behavior_lbl   = esc_html__( 'Template', 'subscriptions-for-woocommerce' );
	$wps_behavior_class = 'template';
} else {
	$wps_behavior_lbl   = esc_html__( 'Message', 'subscriptions-for-woocommerce' );
	$wps_behavior_class = 'message';
}

// Field name helper closure.
$wps_fld = function ( $key ) use ( $wps_idx ) {
	return esc_attr( "wps_rules[{$wps_idx}][{$key}]" );
};

// Conditional inline styles for target sub-fields.
$wps_obj_target_types = $wps_is_product ? array( 'product' ) : array( 'post', 'page' );
$wps_div_pt           = ( ! $wps_is_product && 'post_type' === $wps_rule_type ) ? '' : ' style="display:none;"';
$wps_div_obj          = in_array( $wps_rule_type, $wps_obj_target_types, true ) ? '' : ' style="display:none;"';
$wps_div_tax          = 'taxonomy' === $wps_rule_type ? '' : ' style="display:none;"';
$wps_msg_visible      = in_array( $wps_rule_behavior, array( 'message', 'template' ), true );
$wps_div_msg          = $wps_msg_visible ? '' : ' style="display:none;"';
$wps_div_redir        = 'redirect' === $wps_rule_behavior ? '' : ' style="display:none;"';

// Empty tag containers stay hidden until a chip is added (JS reveals them).
$wps_obj_tags_style  = empty( $wps_rule_obj_ids ) ? ' style="display:none;"' : '';
$wps_term_tags_style = empty( $wps_rule_term_ids ) ? ' style="display:none;"' : '';

// Total wizard steps (drives the "Done" button label in the wizard JS).
$wps_total_steps = $wps_is_product ? 2 : 4;

// Product cards only offer product taxonomies in the taxonomy picker.
$wps_card_taxonomies = $wps_public_taxonomies;
if ( $wps_is_product ) {
	$wps_card_taxonomies = array_filter(
		$wps_public_taxonomies,
		function ( $tx ) {
			return ! empty( $tx->object_type ) && in_array( 'product', (array) $tx->object_type, true );
		}
	);
}
?>
<div class="<?php echo esc_attr( $wps_card_classes ); ?>"
	data-index="<?php echo esc_attr( $wps_idx ); ?>"
	data-kind="<?php echo esc_attr( $wps_kind ); ?>">

	<input type="hidden" name="<?php echo $wps_fld( 'id' ); // phpcs:ignore ?>"
		value="<?php echo esc_attr( isset( $wps_rule['id'] ) ? $wps_rule['id'] : '' ); ?>">
	<input type="hidden" name="<?php echo $wps_fld( 'rule_kind' ); // phpcs:ignore ?>"
		value="<?php echo esc_attr( $wps_kind ); ?>">

	<div class="wps-rule-card__header">

		<label class="wps-rule-enable-toggle"
			title="<?php esc_attr_e( 'Enable / disable this rule', 'subscriptions-for-woocommerce' ); ?>">
			<input type="hidden" name="<?php echo $wps_fld( 'enabled' ); // phpcs:ignore ?>" value="0">
			<input type="checkbox" name="<?php echo $wps_fld( 'enabled' ); // phpcs:ignore ?>" value="1"
				class="wps-rule-enabled-check" <?php checked( '1', (string) $wps_rule_enabled ); ?>>
			<span class="wps-toggle__slider"></span>
		</label>

		<span class="wps-rule-card__kind wps-rule-card__kind--<?php echo esc_attr( $wps_kind ); ?>">
			<?php
			echo $wps_is_product
				? esc_html__( 'Product', 'subscriptions-for-woocommerce' )
				: esc_html__( 'Content', 'subscriptions-for-woocommerce' );
			?>
		</span>

		<div class="wps-rule-card__summary">
			<span class="wps-rule-card__target-lbl"><?php echo esc_html( $wps_target_lbl ); ?></span>
			<span class="wps-rule-card__sep">&#8594;</span>
			<span class="wps-rule-card__plans-lbl"><?php echo esc_html( $wps_plans_lbl ); ?></span>
			<span class="wps-badge wps-badge--<?php echo esc_attr( $wps_behavior_class ); ?>">
				<?php echo esc_html( $wps_behavior_lbl ); ?>
			</span>
			<span class="wps-badge wps-badge--disabled-chip">
				<?php esc_html_e( 'Disabled', 'subscriptions-for-woocommerce' ); ?>
			</span>
		</div>

		<label class="wps-rule-prio-label" title="<?php echo esc_attr( $wps_attr_prio_title ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'Priority', 'subscriptions-for-woocommerce' ); ?></span>
			<input type="number" min="1" max="999"
				name="<?php echo $wps_fld( 'priority' ); // phpcs:ignore ?>"
				value="<?php echo esc_attr( $wps_rule_priority ); ?>"
				class="wps-rule-priority">
		</label>

		<div class="wps-rule-card__actions">
			<button type="button" class="wps-rule-card__preview-btn wps-preview-rule"
				title="<?php esc_attr_e( 'Preview the non-member view', 'subscriptions-for-woocommerce' ); ?>">
				<?php esc_html_e( 'Preview', 'subscriptions-for-woocommerce' ); ?>
			</button>
			<button type="button" class="wps-rule-card__toggle" aria-expanded="false">
				<?php esc_html_e( 'Edit', 'subscriptions-for-woocommerce' ); ?>
				<i class="wps-chevron">&#9662;</i>
			</button>
			<button type="button" class="wps-rule-card__remove-btn wps-remove-rule"
				aria-label="<?php esc_attr_e( 'Remove rule', 'subscriptions-for-woocommerce' ); ?>">
				&times;
			</button>
		</div>
	</div>

	<div class="wps-rule-card__body" <?php echo empty( $wps_rule ) ? '' : 'hidden'; ?>>
		<div class="wps-wizard" data-total-steps="<?php echo esc_attr( $wps_total_steps ); ?>">

			<!-- Step indicators -->
			<div class="wps-wizard__steps">
				<button type="button" class="wps-wizard__step wps-wizard__step--active" data-step="1">
					<span class="wps-wizard__step-num">1</span>
					<span class="wps-wizard__step-label">
						<?php
						echo $wps_is_product
							? esc_html__( 'Products', 'subscriptions-for-woocommerce' )
							: esc_html__( 'What', 'subscriptions-for-woocommerce' );
						?>
					</span>
				</button>
				<span class="wps-wizard__sep">&rsaquo;</span>
				<button type="button" class="wps-wizard__step" data-step="2">
					<span class="wps-wizard__step-num">2</span>
					<span class="wps-wizard__step-label">
						<?php esc_html_e( 'Plans', 'subscriptions-for-woocommerce' ); ?>
					</span>
				</button>
				<?php if ( ! $wps_is_product ) : ?>
					<span class="wps-wizard__sep">&rsaquo;</span>
					<button type="button" class="wps-wizard__step" data-step="3">
						<span class="wps-wizard__step-num">3</span>
						<span class="wps-wizard__step-label">
							<?php esc_html_e( 'Behavior', 'subscriptions-for-woocommerce' ); ?>
						</span>
					</button>
					<span class="wps-wizard__sep">&rsaquo;</span>
					<button type="button" class="wps-wizard__step" data-step="4">
						<span class="wps-wizard__step-num">4</span>
						<span class="wps-wizard__step-label">
							<?php esc_html_e( 'Options', 'subscriptions-for-woocommerce' ); ?>
						</span>
					</button>
				<?php endif; ?>
			</div>

			<!-- Step 1: target -->
			<div class="wps-wizard__panel" data-panel="1">
				<div class="wps-field-group">
					<span class="wps-field-label">
						<?php
						echo $wps_is_product
							? esc_html__( 'Which products to restrict', 'subscriptions-for-woocommerce' )
							: esc_html__( 'What to Restrict', 'subscriptions-for-woocommerce' );
						?>
					</span>
					<select name="<?php echo $wps_fld( 'target_type' ); // phpcs:ignore ?>" class="wps-rule-target-type">
						<?php if ( $wps_is_product ) : ?>
							<option value="post_type" <?php selected( $wps_rule_type, 'post_type' ); ?>>
								<?php esc_html_e( 'All Products', 'subscriptions-for-woocommerce' ); ?>
							</option>
							<option value="product" <?php selected( $wps_rule_type, 'product' ); ?>>
								<?php esc_html_e( 'Specific Product(s)', 'subscriptions-for-woocommerce' ); ?>
							</option>
							<option value="taxonomy" <?php selected( $wps_rule_type, 'taxonomy' ); ?>>
								<?php esc_html_e( 'Product Category / Tag', 'subscriptions-for-woocommerce' ); ?>
							</option>
						<?php else : ?>
							<option value="post_type" <?php selected( $wps_rule_type, 'post_type' ); ?>>
								<?php esc_html_e( 'Post Type', 'subscriptions-for-woocommerce' ); ?>
							</option>
							<option value="post" <?php selected( $wps_rule_type, 'post' ); ?>>
								<?php esc_html_e( 'Specific Post(s)', 'subscriptions-for-woocommerce' ); ?>
							</option>
							<option value="page" <?php selected( $wps_rule_type, 'page' ); ?>>
								<?php esc_html_e( 'Specific Page(s)', 'subscriptions-for-woocommerce' ); ?>
							</option>
							<option value="taxonomy" <?php selected( $wps_rule_type, 'taxonomy' ); ?>>
								<?php esc_html_e( 'Taxonomy Term(s)', 'subscriptions-for-woocommerce' ); ?>
							</option>
						<?php endif; ?>
					</select>

					<?php if ( ! $wps_is_product ) : ?>
						<div class="wps-target-sub wps-target-post_type"<?php echo $wps_div_pt; // phpcs:ignore ?>>
							<select name="<?php echo $wps_fld( 'post_type' ); // phpcs:ignore ?>"
								class="wps-rule-post-type-select">
								<?php foreach ( $wps_public_post_types as $wps_pt ) : ?>
									<option value="<?php echo esc_attr( $wps_pt->name ); ?>"
										<?php selected( $wps_rule_pt, $wps_pt->name ); ?>>
										<?php echo esc_html( $wps_pt->label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<div class="wps-target-sub wps-target-object"<?php echo $wps_div_obj; // phpcs:ignore ?>>
						<div class="wps-tag-container"<?php echo $wps_obj_tags_style; // phpcs:ignore ?>>
							<?php foreach ( $wps_rule_obj_ids as $wps_oid ) : ?>
								<?php
								$wps_obj = get_post( (int) $wps_oid );
								if ( ! $wps_obj ) {
									continue;
								}
								?>
								<span class="wps-tag">
									<?php echo esc_html( $wps_obj->post_title ); ?>
									<input type="hidden" name="<?php echo $wps_fld( 'object_ids' ); // phpcs:ignore ?>[]"
										value="<?php echo esc_attr( $wps_oid ); ?>">
									<button type="button" class="wps-remove-tag"
										aria-label="<?php echo esc_attr( $wps_attr_remove ); ?>">&times;</button>
								</span>
							<?php endforeach; ?>
						</div>
						<div class="wps-search-wrap" style="margin-top:4px;">
							<input type="text" class="wps-object-search wps-ajax-search"
								placeholder="<?php echo esc_attr( $wps_attr_search_title ); ?>" autocomplete="off">
							<span class="wps-search-spinner"></span>
							<ul class="wps-search-results"></ul>
						</div>
					</div>

					<div class="wps-target-sub wps-target-taxonomy"<?php echo $wps_div_tax; // phpcs:ignore ?>>
						<select name="<?php echo $wps_fld( 'taxonomy' ); // phpcs:ignore ?>"
							class="wps-rule-taxonomy-select" style="margin-bottom:6px;">
							<?php foreach ( $wps_card_taxonomies as $wps_tx ) : ?>
								<option value="<?php echo esc_attr( $wps_tx->name ); ?>"
									<?php selected( $wps_rule_tax, $wps_tx->name ); ?>>
									<?php echo esc_html( $wps_tx->label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<div class="wps-tag-container"<?php echo $wps_term_tags_style; // phpcs:ignore ?>>
							<?php foreach ( $wps_rule_term_ids as $wps_tid ) : ?>
								<?php
								$wps_term = get_term( (int) $wps_tid );
								if ( ! $wps_term || is_wp_error( $wps_term ) ) {
									continue;
								}
								?>
								<span class="wps-tag">
									<?php echo esc_html( $wps_term->name ); ?>
									<input type="hidden" name="<?php echo $wps_fld( 'term_ids' ); // phpcs:ignore ?>[]"
										value="<?php echo esc_attr( $wps_tid ); ?>">
									<button type="button" class="wps-remove-tag"
										aria-label="<?php echo esc_attr( $wps_attr_remove ); ?>">&times;</button>
								</span>
							<?php endforeach; ?>
						</div>
						<div class="wps-search-wrap" style="margin-top:4px;">
							<input type="text" class="wps-term-search wps-ajax-search"
								placeholder="<?php echo esc_attr( $wps_attr_search_terms ); ?>" autocomplete="off">
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
							<input type="checkbox" name="<?php echo $wps_fld( 'plans' ); // phpcs:ignore ?>[]"
								value="any" class="wps-plan-any-check"
								<?php echo in_array( 'any', $wps_rule_plans, true ) ? 'checked' : ''; ?>>
							<span><?php esc_html_e( 'Any Plan', 'subscriptions-for-woocommerce' ); ?></span>
						</label>
						<?php foreach ( $wps_all_plans as $wps_plan ) : ?>
							<?php
							$wps_pill_checked = in_array( $wps_plan['slug'], $wps_rule_plans, true ) ? 'checked' : '';
							$wps_pill_row     = isset( $wps_plans_map[ $wps_plan['slug'] ] )
								? $wps_plans_map[ $wps_plan['slug'] ]
								: array();
							$wps_pill_method  = isset( $wps_pill_row['method'] ) ? $wps_pill_row['method'] : 'purchase';
							$wps_pill_color   = isset( $wps_pill_row['color'] ) ? $wps_pill_row['color'] : '';
							$wps_method_badge = 'purchase' !== $wps_pill_method
								? wps_grant_method_short_label( $wps_pill_method )
								: '';
							?>
							<label class="wps-plan-pill">
								<input type="checkbox" name="<?php echo $wps_fld( 'plans' ); // phpcs:ignore ?>[]"
									value="<?php echo esc_attr( $wps_plan['slug'] ); ?>"
									class="wps-plan-specific-check"
									data-grant-method="<?php echo esc_attr( $wps_pill_method ); ?>"
									<?php echo esc_attr( $wps_pill_checked ); ?>>
								<span>
									<?php if ( $wps_pill_color ) : ?>
										<i class="wps-plan-pill__dot"
											style="background:<?php echo esc_attr( $wps_pill_color ); ?>;"></i>
									<?php endif; ?>
									<?php echo esc_html( $wps_plan['name'] ); ?>
									<?php if ( $wps_method_badge ) : ?>
										<em class="wps-plan-pill__method wps-plan-pill__method--<?php echo esc_attr( $wps_pill_method ); ?>">
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

					<?php if ( $wps_is_product ) : ?>
						<?php
						$wps_ph_pmsg = __(
							'Optional notice shown where the Add to Cart button would be. Leave blank for the default.',
							'subscriptions-for-woocommerce'
						);
						?>
						<div class="wps-behavior-message" style="margin-top:12px;">
							<span class="wps-field-label">
								<?php esc_html_e( 'Members-only notice', 'subscriptions-for-woocommerce' ); ?>
							</span>
							<textarea name="<?php echo $wps_fld( 'message' ); // phpcs:ignore ?>" rows="3"
								placeholder="<?php echo esc_attr( $wps_ph_pmsg ); ?>"
							><?php echo esc_textarea( $wps_rule_msg ); ?></textarea>
							<p class="description" style="margin-top:4px;">
								<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
									{purchase_options}
								</button>
								<?php esc_html_e( '— inserts a buy-link.', 'subscriptions-for-woocommerce' ); ?>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! $wps_is_product ) : ?>
				<!-- Step 3: Behavior (content only) -->
				<div class="wps-wizard__panel" data-panel="3" hidden>
					<div class="wps-field-group">
						<span class="wps-field-label">
							<?php esc_html_e( 'When Restricted', 'subscriptions-for-woocommerce' ); ?>
						</span>
						<div class="wps-seg-control" style="margin-bottom:10px;">
							<label class="wps-seg-control__option">
								<input type="radio" name="<?php echo $wps_fld( 'behavior' ); // phpcs:ignore ?>"
									value="message" class="wps-rule-behavior-radio"
									<?php checked( $wps_rule_behavior, 'message' ); ?>>
								<span><?php esc_html_e( 'Show Message', 'subscriptions-for-woocommerce' ); ?></span>
							</label>
							<label class="wps-seg-control__option">
								<input type="radio" name="<?php echo $wps_fld( 'behavior' ); // phpcs:ignore ?>"
									value="redirect" class="wps-rule-behavior-radio"
									<?php checked( $wps_rule_behavior, 'redirect' ); ?>>
								<span><?php esc_html_e( 'Redirect', 'subscriptions-for-woocommerce' ); ?></span>
							</label>
							<label class="wps-seg-control__option<?php echo esc_attr( $wps_tpl_lock_cls ); ?>">
								<input type="radio" name="<?php echo $wps_fld( 'behavior' ); // phpcs:ignore ?>"
									value="template" class="wps-rule-behavior-radio"
									<?php checked( $wps_rule_behavior, 'template' ); ?>
									<?php echo $wps_tpl_disabled; // phpcs:ignore ?>>
								<span><?php esc_html_e( 'Template', 'subscriptions-for-woocommerce' ); ?></span>
							</label>
						</div>

						<div class="wps-behavior-message"<?php echo $wps_div_msg; // phpcs:ignore ?>>
							<?php
							$wps_ph_msg = __(
								'Leave blank to show the default members-only message.',
								'subscriptions-for-woocommerce'
							);
							?>
							<textarea name="<?php echo $wps_fld( 'message' ); // phpcs:ignore ?>" rows="3"
								placeholder="<?php echo esc_attr( $wps_ph_msg ); ?>"
							><?php echo esc_textarea( $wps_rule_msg ); ?></textarea>
							<p class="description" style="margin-top:4px;">
								<button type="button" class="wps-copy-tag" data-tag="{purchase_options}">
									{purchase_options}
								</button>
								<?php esc_html_e( '— inserts a buy-link.', 'subscriptions-for-woocommerce' ); ?>
							</p>
						</div>

						<div class="wps-behavior-redirect"<?php echo $wps_div_redir; // phpcs:ignore ?>>
							<?php
							$wps_ph_url = __(
								'URL to send non-members to — e.g. a pricing or login page.',
								'subscriptions-for-woocommerce'
							);
							?>
							<input type="url" name="<?php echo $wps_fld( 'redirect_url' ); // phpcs:ignore ?>"
								value="<?php echo esc_attr( $wps_rule_redir ); ?>"
								placeholder="<?php echo esc_attr( $wps_ph_url ); ?>">
						</div>
						<?php
						// Template-behavior teaser fields (Pro).
						$wps_rule_tpl = $wps_rule;
						$wps_tpl_show = 'template' === $wps_rule_behavior;
						require __DIR__ . '/access-rules-template-fields.php';
						?>
					</div>
				</div>

				<!-- Step 4: Extra Options (content only) -->
				<div class="wps-wizard__panel" data-panel="4" hidden>
					<div class="wps-field-group">
						<span class="wps-field-label">
							<?php esc_html_e( 'Extra Options', 'subscriptions-for-woocommerce' ); ?>
						</span>
						<div class="wps-toggle-list">
							<label class="wps-toggle">
								<input type="hidden" name="<?php echo $wps_fld( 'restrict_comments' ); // phpcs:ignore ?>"
									value="0">
								<input type="checkbox" name="<?php echo $wps_fld( 'restrict_comments' ); // phpcs:ignore ?>"
									value="1" <?php checked( '1', $wps_opt_comments ); ?>>
								<span class="wps-toggle__slider"></span>
								<span class="wps-toggle__text">
									<?php esc_html_e( 'Disable comments on restricted posts', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</label>
							<label class="wps-toggle">
								<input type="hidden" name="<?php echo $wps_fld( 'include_archive' ); // phpcs:ignore ?>"
									value="0">
								<input type="checkbox" name="<?php echo $wps_fld( 'include_archive' ); // phpcs:ignore ?>"
									value="1" <?php checked( '1', $wps_opt_archive ); ?>>
								<span class="wps-toggle__slider"></span>
								<span class="wps-toggle__text">
									<?php esc_html_e( 'Show restricted posts in archives &amp; search', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</label>
							<label class="wps-toggle">
								<input type="hidden" name="<?php echo $wps_fld( 'show_cta' ); // phpcs:ignore ?>" value="0">
								<input type="checkbox" name="<?php echo $wps_fld( 'show_cta' ); // phpcs:ignore ?>"
									value="1" <?php checked( '1', $wps_opt_cta ); ?>>
								<span class="wps-toggle__slider"></span>
								<span class="wps-toggle__text">
									<?php esc_html_e( 'Auto-append purchase CTA to restriction messages', 'subscriptions-for-woocommerce' ); ?>
								</span>
							</label>
						</div>
						<?php
						// Advanced (Pro) controls: drip scheduling + exclusions.
						$wps_rule_adv = $wps_rule;
						require __DIR__ . '/access-rules-advanced-fields.php';
						?>
					</div>
				</div>
			<?php else : ?>
				<!-- Product cards always block purchase; one toggle for the CTA. -->
				<input type="hidden" name="<?php echo $wps_fld( 'behavior' ); // phpcs:ignore ?>" value="message">
			<?php endif; ?>

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
