( function( $ ) {
	'use strict';

	var selectors = {
		provider: '[data-wps-ai-provider]',
		panel: '[data-wps-ai-provider-panel]',
		testButton: '[data-wps-ai-test-connection]',
		testStatus: '[data-wps-ai-test-status]'
	};

	function getActivePanel() {
		var provider = $( selectors.provider ).val();
		return $( selectors.panel + '[data-wps-ai-provider-panel="' + provider + '"]' );
	}

	function syncProviderPanels() {
		var provider = $( selectors.provider ).val();

		$( selectors.panel ).hide();

		if ( provider ) {
			$( selectors.panel + '[data-wps-ai-provider-panel="' + provider + '"]' ).show();
		}

		$( selectors.testButton ).toggle( !! provider );
		syncCustomModelFields();
	}

	function getSelectedModel( $panel ) {
		var model = $panel.find( '[data-wps-ai-model]' ).val() || '';

		if ( '__custom' === model ) {
			return $panel.find( '[data-wps-ai-custom-model]' ).val() || '';
		}

		return model;
	}

	function syncCustomModelFields() {
		$( '[data-wps-ai-model]' ).each( function() {
			var $select = $( this );
			var $wrap = $select.closest( '.wps-sfw-setting-field__control' ).find( '[data-wps-ai-custom-model-wrap]' );

			$wrap.toggle( '__custom' === $select.val() );
		} );
	}

	function setStatus( message, type ) {
		$( selectors.testStatus )
			.removeClass( 'is-success is-error is-loading' )
			.addClass( type ? 'is-' + type : '' )
			.text( message || '' );
	}

	function testConnection() {
		var provider = $( selectors.provider ).val();
		var $panel = getActivePanel();
		var $button = $( selectors.testButton );

		if ( ! provider ) {
			setStatus( wpsAiSettings.selectProvider, 'error' );
			return;
		}

		setStatus( wpsAiSettings.testingText, 'loading' );
		$button.prop( 'disabled', true );

		$.ajax( {
			url: wpsAiSettings.ajaxurl,
			method: 'POST',
			dataType: 'json',
			data: {
				action: wpsAiSettings.action,
				nonce: wpsAiSettings.nonce,
				provider: provider,
				api_key: $panel.find( '[data-wps-ai-api-key]' ).val() || '',
				model: getSelectedModel( $panel ),
				custom_endpoint: $( '[data-wps-ai-custom-endpoint]' ).val() || ''
			}
		} )
			.done( function( response ) {
				if ( response && response.success ) {
					setStatus( response.data && response.data.message ? response.data.message : wpsAiSettings.connectedText, 'success' );
					return;
				}

				setStatus( response && response.data && response.data.message ? response.data.message : wpsAiSettings.errorText, 'error' );
			} )
			.fail( function( xhr ) {
				var message = wpsAiSettings.errorText;

				if ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
					message = xhr.responseJSON.data.message;
				}

				setStatus( message, 'error' );
			} )
			.always( function() {
				$button.prop( 'disabled', false );
			} );
	}

	$( function() {
		syncProviderPanels();

		$( document ).on( 'change', selectors.provider, function() {
			setStatus( '', '' );
			syncProviderPanels();
		} );

		$( document ).on( 'change', '[data-wps-ai-model]', syncCustomModelFields );

		$( document ).on( 'click', selectors.testButton, function( event ) {
			event.preventDefault();
			testConnection();
		} );
	} );
} )( jQuery );
