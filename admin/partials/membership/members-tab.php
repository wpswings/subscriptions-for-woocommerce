<?php
/**
 * Grant Membership form — Members tab partial (Day 09).
 *
 * Included by admin/partials/wps-membership-members.php.
 * Renders a collapsible "Grant Membership" panel wired to the
 * `wp_ajax_wps_membership_admin_action` AJAX endpoint.
 *
 * Variables available from the including file:
 *   $wps_all_plans  array  Active plans from wps_get_all_plans( 'active' ).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="wps-grant-membership-panel" style="display:none;margin:16px 0;padding:16px;background:#fff;border:1px solid #ccd0d4;box-shadow:0 1px 1px rgba(0,0,0,.04);">
	<h3 style="margin-top:0;"><?php esc_html_e( 'Grant New Membership', 'subscriptions-for-woocommerce' ); ?></h3>

	<div id="wps-grant-notice" style="display:none;" class="notice inline"></div>

	<table class="form-table" style="max-width:600px;">
		<tbody>

			<tr>
				<th scope="row">
					<label for="wps-grant-user-search">
						<?php esc_html_e( 'User', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="wps-grant-user-search"
						placeholder="<?php esc_attr_e( 'Search by name or email…', 'subscriptions-for-woocommerce' ); ?>"
						class="regular-text"
						autocomplete="off"
					/>
					<input type="hidden" id="wps-grant-user-id" name="user_id" value="" />
					<ul id="wps-grant-user-results"
						style="display:none;position:absolute;z-index:9999;background:#fff;border:1px solid #ccd0d4;min-width:320px;margin:0;padding:0;list-style:none;max-height:200px;overflow-y:auto;">
					</ul>
					<p class="description">
						<?php esc_html_e( 'Start typing to search WordPress users.', 'subscriptions-for-woocommerce' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wps-grant-plan">
						<?php esc_html_e( 'Plan', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</th>
				<td>
					<?php if ( empty( $wps_all_plans ) ) : ?>
						<p class="description">
							<?php esc_html_e( 'No active plans found. Please create a plan first.', 'subscriptions-for-woocommerce' ); ?>
						</p>
					<?php else : ?>
						<select id="wps-grant-plan" name="plan_slug" class="regular-text">
							<?php foreach ( $wps_all_plans as $wps_grant_plan ) : ?>
								<option value="<?php echo esc_attr( $wps_grant_plan['slug'] ); ?>">
									<?php echo esc_html( $wps_grant_plan['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wps-grant-expiry">
						<?php esc_html_e( 'Expiry Date', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</th>
				<td>
					<input
						type="date"
						id="wps-grant-expiry"
						name="expiry_date"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Leave blank for a lifetime membership.', 'subscriptions-for-woocommerce' ); ?>
					</p>
				</td>
			</tr>

		</tbody>
	</table>

	<p>
		<button type="button" id="wps-grant-submit" class="button button-primary"
			<?php echo empty( $wps_all_plans ) ? 'disabled' : ''; ?>>
			<?php esc_html_e( 'Grant Membership', 'subscriptions-for-woocommerce' ); ?>
		</button>
		<button type="button" id="wps-grant-cancel" class="button">
			<?php esc_html_e( 'Cancel', 'subscriptions-for-woocommerce' ); ?>
		</button>
	</p>
</div>

<script>
( function () {
	var ajaxUrl    = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce      = '<?php echo esc_js( wp_create_nonce( 'wps_membership_admin_action' ) ); ?>';
	var searchDelay;

	// Toggle panel.
	var toggleBtn  = document.getElementById( 'wps-toggle-grant-form' );
	var panel      = document.getElementById( 'wps-grant-membership-panel' );
	var cancelBtn  = document.getElementById( 'wps-grant-cancel' );

	if ( toggleBtn ) {
		toggleBtn.addEventListener( 'click', function () {
			panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
		} );
	}
	if ( cancelBtn ) {
		cancelBtn.addEventListener( 'click', function () {
			panel.style.display = 'none';
			resetForm();
		} );
	}

	// User search autocomplete.
	var searchInput = document.getElementById( 'wps-grant-user-search' );
	var userIdInput = document.getElementById( 'wps-grant-user-id' );
	var resultsList = document.getElementById( 'wps-grant-user-results' );

	if ( searchInput ) {
		searchInput.addEventListener( 'input', function () {
			clearTimeout( searchDelay );
			var term = this.value;
			if ( term.length < 2 ) {
				resultsList.style.display = 'none';
				resultsList.innerHTML = '';
				userIdInput.value = '';
				return;
			}
			searchDelay = setTimeout( function () {
				doUserSearch( term );
			}, 300 );
		} );

		// Hide results when focus leaves the search area.
		document.addEventListener( 'click', function ( e ) {
			if ( ! searchInput.contains( e.target ) && ! resultsList.contains( e.target ) ) {
				resultsList.style.display = 'none';
			}
		} );
	}

	function doUserSearch( term ) {
		var data = new FormData();
		data.append( 'action', 'wps_membership_admin_action' );
		data.append( 'nonce', nonce );
		data.append( 'wps_sub_action', 'search_users' );
		data.append( 'term', term );

		fetch( ajaxUrl, { method: 'POST', body: data } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				resultsList.innerHTML = '';
				if ( ! res.success || ! res.data.results.length ) {
					resultsList.style.display = 'none';
					return;
				}
				res.data.results.forEach( function ( user ) {
					var li = document.createElement( 'li' );
					li.textContent = user.text;
					li.dataset.userId = user.id;
					li.style.cssText = 'padding:6px 10px;cursor:pointer;border-bottom:1px solid #eee;';
					li.addEventListener( 'mouseenter', function () { this.style.background = '#f0f6fc'; } );
					li.addEventListener( 'mouseleave', function () { this.style.background = ''; } );
					li.addEventListener( 'click', function () {
						searchInput.value   = user.text;
						userIdInput.value   = user.id;
						resultsList.style.display = 'none';
					} );
					resultsList.appendChild( li );
				} );
				resultsList.style.display = 'block';
			} )
			.catch( function () {
				resultsList.style.display = 'none';
			} );
	}

	// Grant submit.
	var submitBtn = document.getElementById( 'wps-grant-submit' );
	var noticeEl  = document.getElementById( 'wps-grant-notice' );

	if ( submitBtn ) {
		submitBtn.addEventListener( 'click', function () {
			var userId = userIdInput ? userIdInput.value : '';
			if ( ! userId ) {
				showNotice( '<?php echo esc_js( __( 'Please select a user.', 'subscriptions-for-woocommerce' ) ); ?>', 'error' );
				return;
			}

			var planEl   = document.getElementById( 'wps-grant-plan' );
			var expiryEl = document.getElementById( 'wps-grant-expiry' );

			var data = new FormData();
			data.append( 'action', 'wps_membership_admin_action' );
			data.append( 'nonce', nonce );
			data.append( 'wps_sub_action', 'grant' );
			data.append( 'user_id', userId );
			data.append( 'plan_slug', planEl ? planEl.value : '' );
			data.append( 'expiry_date', expiryEl ? expiryEl.value : '' );

			submitBtn.disabled = true;

			fetch( ajaxUrl, { method: 'POST', body: data } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					submitBtn.disabled = false;
					if ( res.success ) {
						showNotice( res.data.message, 'success' );
						resetForm();
						// Reload the page after a short delay so the new row appears.
						setTimeout( function () { window.location.reload(); }, 1200 );
					} else {
						showNotice( res.data.message, 'error' );
					}
				} )
				.catch( function () {
					submitBtn.disabled = false;
					showNotice( '<?php echo esc_js( __( 'Request failed. Please try again.', 'subscriptions-for-woocommerce' ) ); ?>', 'error' );
				} );
		} );
	}

	function showNotice( message, type ) {
		noticeEl.className   = 'notice notice-' + type + ' inline';
		noticeEl.innerHTML   = '<p>' + message + '</p>';
		noticeEl.style.display = 'block';
	}

	function resetForm() {
		if ( searchInput ) { searchInput.value = ''; }
		if ( userIdInput ) { userIdInput.value = ''; }
		var planEl   = document.getElementById( 'wps-grant-plan' );
		var expiryEl = document.getElementById( 'wps-grant-expiry' );
		if ( planEl ) { planEl.selectedIndex = 0; }
		if ( expiryEl ) { expiryEl.value = ''; }
		noticeEl.style.display = 'none';
	}
} () );
</script>
