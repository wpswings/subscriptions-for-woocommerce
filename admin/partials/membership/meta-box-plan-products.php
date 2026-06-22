<?php
/**
 * Meta box partial — Grant Methods (mutually exclusive selector).
 *
 * Variables available from WPS_Membership_Plan_CPT::render_products_meta_box():
 *   $post     WP_Post  Current plan post.
 *   $wps_plan array    Normalised plan data from wps_get_plan(), or null for a new post.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_method         = $wps_plan ? $wps_plan['grant_method'] : 'purchase';
$wps_linked         = $wps_plan ? array_map( 'absint', (array) $wps_plan['products'] ) : array();
$wps_sub_linked     = $wps_plan
	? array_map( 'absint', (array) $wps_plan['subscription_products'] )
	: array();
$wps_sub_single_id  = ! empty( $wps_sub_linked ) ? $wps_sub_linked[0] : 0;
$wps_sub_single_obj = $wps_sub_single_id ? wc_get_product( $wps_sub_single_id ) : null;
$wps_plan_id        = $post->ID;
$wps_ph_purchase    = esc_attr__( 'Search for a product&hellip;', 'subscriptions-for-woocommerce' );
$wps_ph_sub         = esc_attr__(
	'Search for a subscription product&hellip;',
	'subscriptions-for-woocommerce'
);
?>
<div class="wps-grant-methods">

	<!-- ── Method selector ── -->
	<div class="wps-grant-selector">

		<label class="wps-grant-option<?php echo 'purchase' === $wps_method ? ' is-active' : ''; ?>">
			<input type="radio" name="_wps_plan_grant_method" value="purchase"
				<?php checked( $wps_method, 'purchase' ); ?>>
			<span class="wps-grant-option__inner">
				<svg class="wps-grant-option__icon" xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24" fill="none" stroke="currentColor"
					stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<circle cx="9" cy="21" r="1"></circle>
					<circle cx="20" cy="21" r="1"></circle>
					<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72
						a2 2 0 0 0 2-1.61L23 6H6"></path>
				</svg>
				<strong><?php esc_html_e( 'Product Purchase', 'subscriptions-for-woocommerce' ); ?></strong>
				<span>
					<?php
					esc_html_e(
						'One-time buy grants membership.',
						'subscriptions-for-woocommerce'
					);
					?>
				</span>
			</span>
		</label>

		<label class="wps-grant-option<?php echo 'subscription' === $wps_method ? ' is-active' : ''; ?>">
			<input type="radio" name="_wps_plan_grant_method" value="subscription"
				<?php checked( $wps_method, 'subscription' ); ?>>
			<span class="wps-grant-option__inner">
				<svg class="wps-grant-option__icon" xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24" fill="none" stroke="currentColor"
					stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<polyline points="23 4 23 10 17 10"></polyline>
					<polyline points="1 20 1 14 7 14"></polyline>
					<path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10
						M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
				</svg>
				<strong>
					<?php esc_html_e( 'Active Subscription', 'subscriptions-for-woocommerce' ); ?>
				</strong>
				<span>
					<?php
					esc_html_e(
						'Membership tied to subscription lifecycle.',
						'subscriptions-for-woocommerce'
					);
					?>
				</span>
			</span>
		</label>

		<label class="wps-grant-option<?php echo 'auto_enroll' === $wps_method ? ' is-active' : ''; ?>">
			<input type="radio" name="_wps_plan_grant_method" value="auto_enroll"
				<?php checked( $wps_method, 'auto_enroll' ); ?>>
			<span class="wps-grant-option__inner">
				<svg class="wps-grant-option__icon" xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24" fill="none" stroke="currentColor"
					stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
					<circle cx="12" cy="7" r="4"></circle>
				</svg>
				<strong>
					<?php esc_html_e( 'Free / Auto-Enroll', 'subscriptions-for-woocommerce' ); ?>
				</strong>
				<span>
					<?php
					esc_html_e(
						'Grant on registration automatically.',
						'subscriptions-for-woocommerce'
					);
					?>
				</span>
			</span>
		</label>

	</div><!-- .wps-grant-selector -->

	<!-- ── Body: Product Purchase ── -->
	<div class="wps-grant-body" id="wps-grant-body-purchase"
		<?php echo 'purchase' !== $wps_method ? 'style="display:none"' : ''; ?>>
		<select
			class="wc-product-search"
			multiple="multiple"
			style="width:100%;"
			id="wps_plan_products"
			name="_wps_plan_products[]"
			data-placeholder="<?php echo esc_attr( $wps_ph_purchase ); ?>"
			data-action="woocommerce_json_search_products_and_variations"
		>
			<?php foreach ( $wps_linked as $wps_pid ) : ?>
				<?php $wps_p = wc_get_product( $wps_pid ); ?>
				<?php if ( $wps_p ) : ?>
					<option value="<?php echo absint( $wps_pid ); ?>" selected="selected">
						<?php echo esc_html( wp_strip_all_tags( $wps_p->get_formatted_name() ) ); ?>
					</option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php
			esc_html_e(
				'Buying any selected product grants this membership to the buyer.',
				'subscriptions-for-woocommerce'
			);
			?>
		</p>
	</div>

	<!-- ── Body: Active Subscription ── -->
	<div class="wps-grant-body" id="wps-grant-body-subscription"
		<?php echo 'subscription' !== $wps_method ? 'style="display:none"' : ''; ?>>
		<div class="wps-grant-sub-notice">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="2" stroke-linecap="round"
				stroke-linejoin="round" aria-hidden="true">
				<circle cx="12" cy="12" r="10"></circle>
				<line x1="12" y1="8" x2="12" y2="12"></line>
				<line x1="12" y1="16" x2="12.01" y2="16"></line>
			</svg>
			<?php
			esc_html_e(
				'Access duration is controlled by the subscription lifecycle. The Access Length setting is ignored.',
				'subscriptions-for-woocommerce'
			);
			?>
		</div>
		<select
			class="wc-product-search"
			style="width:100%;"
			id="wps_plan_sub_product"
			name="_wps_plan_sub_product"
			data-placeholder="<?php echo esc_attr( $wps_ph_sub ); ?>"
			data-action="wps_search_subscription_products"
			data-allow_clear="true"
		>
			<?php if ( $wps_sub_single_obj ) : ?>
				<option value="<?php echo absint( $wps_sub_single_id ); ?>" selected="selected">
					<?php echo esc_html( wp_strip_all_tags( $wps_sub_single_obj->get_formatted_name() ) ); ?>
				</option>
			<?php endif; ?>
		</select>
		<div id="wps-sub-billing-preview" class="wps-sub-billing-preview"
			<?php echo $wps_sub_single_obj ? '' : 'style="display:none"'; ?>>
			<span class="wps-sub-billing-preview__period" id="wps-sub-billing-period">
				<?php
				if ( $wps_sub_single_id ) {
					$wps_sub_num = (int) get_post_meta( $wps_sub_single_id, 'wps_sfw_subscription_number', true );
					$wps_sub_int = get_post_meta( $wps_sub_single_id, 'wps_sfw_subscription_interval', true );
					if ( $wps_sub_single_obj->is_type( 'variation' ) && ! $wps_sub_num ) {
						$wps_sub_check = $wps_sub_single_obj->get_parent_id();
						$wps_sub_num   = (int) get_post_meta( $wps_sub_check, 'wps_sfw_subscription_number', true );
						$wps_sub_int   = get_post_meta( $wps_sub_check, 'wps_sfw_subscription_interval', true );
					}
					if ( $wps_sub_num && $wps_sub_int ) {
						$wps_int_labels = array(
							'day'   => _n( 'day', 'days', $wps_sub_num, 'subscriptions-for-woocommerce' ),
							'week'  => _n( 'week', 'weeks', $wps_sub_num, 'subscriptions-for-woocommerce' ),
							'month' => _n( 'month', 'months', $wps_sub_num, 'subscriptions-for-woocommerce' ),
							'year'  => _n( 'year', 'years', $wps_sub_num, 'subscriptions-for-woocommerce' ),
						);
						$wps_unit_lbl   = isset( $wps_int_labels[ $wps_sub_int ] )
							? $wps_int_labels[ $wps_sub_int ]
							: $wps_sub_int;
						echo esc_html(
							sprintf(
								/* translators: 1: number, 2: period unit */
								__( 'Billed every %1$s %2$s', 'subscriptions-for-woocommerce' ),
								$wps_sub_num,
								$wps_unit_lbl
							)
						);
					}
				}
				?>
			</span>
		</div>
		<p class="description">
			<?php
			esc_html_e(
				'Membership is granted on activation and revoked on cancellation or expiry.',
				'subscriptions-for-woocommerce'
			);
			?>
		</p>
	</div>

	<!-- ── Body: Free / Auto-Enroll ── -->
	<div class="wps-grant-body" id="wps-grant-body-auto-enroll"
		<?php echo 'auto_enroll' !== $wps_method ? 'style="display:none"' : ''; ?>>
		<p class="description">
			<?php
			esc_html_e(
				'Every new user who registers on your site will automatically receive this membership.',
				'subscriptions-for-woocommerce'
			);
			?>
		</p>
	</div>

</div><!-- .wps-grant-methods -->

<?php
$wps_js_i18n_select = esc_js(
	__( 'Select a subscription product above to preview its billing period.', 'subscriptions-for-woocommerce' )
);
?>
<script>
( function() {
	var radios      = document.querySelectorAll( '[name="_wps_plan_grant_method"]' );
	var bodies      = {
		purchase:     document.getElementById( 'wps-grant-body-purchase' ),
		subscription: document.getElementById( 'wps-grant-body-subscription' ),
		auto_enroll:  document.getElementById( 'wps-grant-body-auto-enroll' ),
	};
	var options     = document.querySelectorAll( '.wps-grant-option' );
	var accessBox   = document.getElementById( 'wps-plan-access' );
	var accessWrap  = document.querySelector( '.wps-plan-access-wrap' );
	var subPreview  = document.getElementById( 'wps-access-sub-preview' );
	var subSelect   = document.getElementById( 'wps_plan_sub_product' );
	var billingPrev = document.getElementById( 'wps-sub-billing-preview' );
	var billingPeriod = document.getElementById( 'wps-sub-billing-period' );
	var nonce        = '<?php echo esc_js( wp_create_nonce( 'wps_membership_admin_nonce' ) ); ?>';
	var i18nSelect   = '<?php echo esc_js( $wps_js_i18n_select ); ?>';
	var i18nLoading  = '<?php echo esc_js( __( 'Loading billing details…', 'subscriptions-for-woocommerce' ) ); ?>';
	var i18nBilling  = '<?php echo esc_js( __( 'Billing:', 'subscriptions-for-woocommerce' ) ); ?>';
	var i18nDuration = '<?php echo esc_js( __( 'Duration:', 'subscriptions-for-woocommerce' ) ); ?>';

	/**
	 * Fetch the subscription product's billing info via AJAX and render a
	 * product card in the Access Length panel + update the inline badge.
	 */
	function fetchBillingPreview( productId ) {
		var body = document.getElementById( 'wps-access-sub-preview__body' );
		if ( ! productId ) {
			if ( billingPrev ) { billingPrev.style.display = 'none'; }
			if ( body ) {
				body.innerHTML = '<p class="wps-access-sub-preview__empty">' + i18nSelect + '</p>';
			}
			return;
		}

		var url = ajaxurl + '?action=wps_get_subscription_duration' +
			'&product_id=' + encodeURIComponent( productId ) +
			'&security=' + encodeURIComponent( nonce );

		if ( body ) {
			body.innerHTML = '<p class="wps-access-sub-preview__loading">' + i18nLoading + '</p>';
		}

		fetch( url )
			.then( function( r ) { return r.json(); } )
			.then( function( res ) {
				if ( ! res.success ) { return; }
				var d = res.data;

				// Update inline billing badge below the select.
				if ( billingPrev && billingPeriod ) {
					billingPeriod.textContent = d.billing || d.duration;
					billingPrev.style.display = '';
				}

				// Render product card in the Access Length panel.
				if ( body ) {
					var imgHtml = d.thumbnail_url
						? '<img src="' + d.thumbnail_url + '" alt="" class="wps-sub-product-card__img">'
						: '<span class="wps-sub-product-card__no-img"></span>';

					body.innerHTML =
						'<div class="wps-sub-product-card">' +
							imgHtml +
							'<div class="wps-sub-product-card__info">' +
								'<p class="wps-sub-product-card__name">' + escHtml( d.name ) + '</p>' +
								( d.price_html
									? '<p class="wps-sub-product-card__price">' + d.price_html + '</p>'
									: '' ) +
								'<div class="wps-sub-product-card__rows">' +
									( d.billing
										? '<span class="wps-sub-product-card__row">' +
											'<strong>' + i18nBilling + '</strong> ' + escHtml( d.billing ) +
											'</span>'
										: '' ) +
									'<span class="wps-sub-product-card__row">' +
										'<strong>' + i18nDuration + '</strong> ' + escHtml( d.duration ) +
									'</span>' +
								'</div>' +
							'</div>' +
						'</div>';
				}
			} )
			.catch( function() {} );
	}

	function escHtml( str ) {
		var d = document.createElement( 'div' );
		d.appendChild( document.createTextNode( str || '' ) );
		return d.innerHTML;
	}

	function applyMethod( value ) {
		Object.keys( bodies ).forEach( function( key ) {
			if ( bodies[ key ] ) {
				bodies[ key ].style.display = ( key === value ) ? '' : 'none';
			}
		} );
		options.forEach( function( opt ) {
			var radio = opt.querySelector( 'input[type="radio"]' );
			opt.classList.toggle( 'is-active', radio && radio.value === value );
		} );

		if ( 'subscription' === value ) {
			// Keep Access Length meta box visible but swap form → billing preview.
			if ( accessBox )  { accessBox.style.display  = ''; }
			if ( accessWrap ) { accessWrap.style.display  = 'none'; }
			if ( subPreview ) { subPreview.style.display  = ''; }
			fetchBillingPreview( subSelect ? subSelect.value : '' );
		} else {
			if ( accessBox )  { accessBox.style.display  = ''; }
			if ( accessWrap ) { accessWrap.style.display  = ''; }
			if ( subPreview ) { subPreview.style.display  = 'none'; }
		}
	}

	// Re-fetch billing preview when the subscription product changes.
	// Select2 fires events via jQuery, so vanilla addEventListener won't catch them.
	if ( subSelect && typeof jQuery !== 'undefined' ) {
		jQuery( subSelect ).on( 'change', function() {
			fetchBillingPreview( this.value );
		} );
	}

	radios.forEach( function( radio ) {
		radio.addEventListener( 'change', function() {
			applyMethod( this.value );
		} );
	} );

	// Apply initial state on page load.
	radios.forEach( function( radio ) {
		if ( radio.checked ) { applyMethod( radio.value ); }
	} );

} )();
</script>
