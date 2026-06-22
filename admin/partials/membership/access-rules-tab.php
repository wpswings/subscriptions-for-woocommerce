<?php
/**
 * Access Rules tab — rule list, Content/Product builders, and Preview modal.
 *
 * Rules come in two kinds, each with its own short builder so the two render
 * paths never mix (a product rule can't be used for content restriction and
 * vice-versa). Each card is rendered by access-rules-card.php; the two JS clone
 * templates and the live rows all share that single partial.
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
		// Products are owned by Product rules, not Content rules.
		return ! in_array( $pt->name, array( 'attachment', 'product', WPS_MEMBERSHIP_PLAN_CPT ), true );
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
		$pt_key = isset( $rule['post_type'] ) ? $rule['post_type'] : '';
		if ( 'product' === $pt_key ) {
			// A whole-product-post-type rule is the "All Products" product rule.
			$target = esc_html__( 'All Products', 'subscriptions-for-woocommerce' );
		} else {
			$pt_label = isset( $post_types[ $pt_key ] ) ? $post_types[ $pt_key ]->label : $pt_key;
			$target   = sprintf(
				/* translators: post type label */
				esc_html__( 'Post Type: %s', 'subscriptions-for-woocommerce' ),
				esc_html( $pt_label )
			);
		}
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

/**
 * Whether Subscriptions for WooCommerce Pro is active.
 *
 * Drives the locked/disabled state of the Pro-only Template behavior and the
 * advanced rule fields.
 *
 * @since 2.0.0
 *
 * @param bool $is_active Default false; Pro flips this true when present.
 */
$wps_is_pro       = (bool) apply_filters( 'wsp_sfw_check_pro_plugin', false );
$wps_tpl_lock_cls = $wps_is_pro ? '' : ' wps_pro_settings_tag wps-ai-pro-locked';
$wps_tpl_disabled = $wps_is_pro ? '' : ' disabled';

// Pre-escaped attribute strings reused by every card.
$wps_attr_remove       = esc_attr__( 'Remove', 'subscriptions-for-woocommerce' );
$wps_attr_prio_title   = esc_attr__( 'Priority — lower number evaluated first', 'subscriptions-for-woocommerce' );
$wps_attr_search_title = esc_attr__( 'Search by title…', 'subscriptions-for-woocommerce' );
$wps_attr_search_terms = esc_attr__( 'Search terms…', 'subscriptions-for-woocommerce' );
?>

<?php if ( $wps_saved ) : ?>
	<div class="wps-mem-toast wps-mem-toast--success" role="status">
		<svg class="wps-mem-toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
			stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M20 6 9 17l-5-5"/>
		</svg>
		<span><?php esc_html_e( 'Access Rules saved.', 'subscriptions-for-woocommerce' ); ?></span>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( $wps_tab_url ); ?>">
	<?php wp_nonce_field( 'wps_save_access_rules', 'wps_access_rules_nonce' ); ?>
	<input type="hidden" name="wps_save_access_rules" value="1">

	<div class="wps-access-section">
		<div class="wps-access-section__head">
			<h2 class="wps-access-section__title">
				<?php esc_html_e( 'Access Rules', 'subscriptions-for-woocommerce' ); ?>
			</h2>
			<span class="wps-access-section__desc">
				<?php
				esc_html_e(
					'Content rules gate posts & pages; Product rules block purchase. Lowest priority number wins.',
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
							'No access rules yet. Add a Content rule or a Product rule to get started.',
							'subscriptions-for-woocommerce'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php foreach ( $wps_rules as $wps_ri => $wps_rule ) : ?>
				<?php
				$wps_idx  = $wps_ri;
				$wps_kind = wps_get_access_rule_kind( $wps_rule );
				require __DIR__ . '/access-rules-card.php';
				?>
			<?php endforeach; ?>

			</div>

			<div class="wps-rules-footer">
				<button type="button" id="wps-add-content-rule" class="button"
					data-template="wps-rule-content-template">
					<?php esc_html_e( '+ Add Content Rule', 'subscriptions-for-woocommerce' ); ?>
				</button>
				<button type="button" id="wps-add-product-rule" class="button"
					data-template="wps-rule-product-template">
					<?php esc_html_e( '+ Add Product Rule', 'subscriptions-for-woocommerce' ); ?>
				</button>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Access Rules', 'subscriptions-for-woocommerce' ); ?>
				</button>
			</div>

		</div>
	</div>

	<!-- Content rule template (cloned by JS) -->
	<template id="wps-rule-content-template">
		<?php
		$wps_idx  = '__IDX__';
		$wps_kind = 'content';
		$wps_rule = array();
		require __DIR__ . '/access-rules-card.php';
		?>
	</template>

	<!-- Product rule template (cloned by JS) -->
	<template id="wps-rule-product-template">
		<?php
		$wps_idx  = '__IDX__';
		$wps_kind = 'product';
		$wps_rule = array();
		require __DIR__ . '/access-rules-card.php';
		?>
	</template>

</form>

<!-- Non-member preview modal -->
<div class="wps-preview-modal" id="wps-preview-modal" hidden>
	<div class="wps-preview-modal__overlay" data-close="1"></div>
	<div class="wps-preview-modal__panel" role="dialog" aria-modal="true"
		aria-labelledby="wps-preview-modal-title">
		<div class="wps-preview-modal__head">
			<h3 class="wps-preview-modal__title" id="wps-preview-modal-title"></h3>
			<button type="button" class="wps-preview-modal__close" data-close="1" aria-label="">&times;</button>
		</div>
		<p class="wps-preview-modal__intro"></p>
		<div class="wps-preview-modal__body"></div>
	</div>
</div>

<script>
( function () {
	function wpsWizardTotal( wizard ) {
		var attr = parseInt( wizard.getAttribute( 'data-total-steps' ), 10 );
		if ( attr ) { return attr; }
		return wizard.querySelectorAll( '.wps-wizard__panel' ).length;
	}

	function wpsWizardGoTo( wizard, step ) {
		var panels = wizard.querySelectorAll( '.wps-wizard__panel' );
		var total  = wpsWizardTotal( wizard );

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
			var cur = wpsWizardCurrentStep( wizard );
			if ( cur >= wpsWizardTotal( wizard ) ) {
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
				if ( card ) { card.classList.remove( 'wps-rule-card--open' ); }
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
				var card = toggleBtn.closest( '.wps-rule-card' );
				var body = card && card.querySelector( '.wps-rule-card__body' );
				if ( ! body || body.hidden ) { return; }
				var wizard = body.querySelector( '.wps-wizard' );
				if ( wizard ) { wpsWizardGoTo( wizard, 1 ); }
			} );
		}
	} );
} )();
</script>
