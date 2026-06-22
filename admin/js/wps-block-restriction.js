/**
 * Membership Layer — Gutenberg block restriction (editor side, Day 17).
 *
 * Adds two attributes to every block (wpsRestricted, wpsRequiredPlans) and an
 * "Membership Restriction" panel to the block inspector. The panel ALWAYS
 * renders; when the localized `isPro` flag is false the controls are disabled
 * and a "Pro" notice is shown. Frontend enforcement lives in the Pro plugin.
 *
 * Plain ES5 against the wp.* globals — no build step required.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.hooks || ! wp.element || ! wp.compose ) {
		return;
	}

	var el         = wp.element.createElement;
	var Fragment   = wp.element.Fragment;
	var addFilter  = wp.hooks.addFilter;
	var createHOC  = wp.compose.createHigherOrderComponent;
	var Inspector  = ( wp.blockEditor && wp.blockEditor.InspectorControls ) ||
		( wp.editor && wp.editor.InspectorControls );
	var components = wp.components || {};
	var PanelBody       = components.PanelBody;
	var ToggleControl   = components.ToggleControl;
	var CheckboxControl = components.CheckboxControl;
	var Notice          = components.Notice;
	var __ = ( wp.i18n && wp.i18n.__ ) ? wp.i18n.__ : function ( s ) { return s; };

	var cfg  = window.wpsBlockRestriction || { isPro: false, plans: [], i18n: {} };
	var i18n = cfg.i18n || {};
	var locked = ! cfg.isPro;

	/**
	 * Inject the membership attributes into every block's settings.
	 *
	 * @param {Object} settings Block settings.
	 * @return {Object} Settings with the membership attributes added.
	 */
	function addAttributes( settings ) {
		if ( ! settings || typeof settings !== 'object' ) {
			return settings;
		}
		if ( ! settings.attributes ) {
			settings.attributes = {};
		}
		if ( ! settings.attributes.wpsRestricted ) {
			settings.attributes.wpsRestricted = { type: 'boolean', default: false };
		}
		if ( ! settings.attributes.wpsRequiredPlans ) {
			settings.attributes.wpsRequiredPlans = { type: 'array', default: [] };
		}
		return settings;
	}

	addFilter(
		'blocks.registerBlockType',
		'wps/membership-restriction/attributes',
		addAttributes
	);

	/**
	 * Build the plan checkbox controls for the panel.
	 *
	 * @param {Array}    required Currently selected plan slugs.
	 * @param {Function} setAttr  props.setAttributes.
	 * @return {Array} Control elements.
	 */
	function buildPlanControls( required, setAttr ) {
		var plans = cfg.plans || [];
		if ( ! plans.length ) {
			return [ el( 'p', { key: 'wps-no-plans', className: 'description' }, i18n.noPlans || '' ) ];
		}
		return plans.map( function ( plan ) {
			return el( CheckboxControl, {
				key: 'wps-plan-' + plan.slug,
				label: plan.name,
				checked: required.indexOf( plan.slug ) !== -1,
				disabled: locked,
				onChange: function ( checked ) {
					var next = required.slice();
					var idx  = next.indexOf( plan.slug );
					if ( checked && idx === -1 ) {
						next.push( plan.slug );
					} else if ( ! checked && idx !== -1 ) {
						next.splice( idx, 1 );
					}
					setAttr( { wpsRequiredPlans: next } );
				}
			} );
		} );
	}

	/**
	 * Higher-order component adding the membership panel to the inspector.
	 */
	var withControls = createHOC( function ( BlockEdit ) {
		return function ( props ) {
			if ( ! props.isSelected || ! Inspector || ! PanelBody || ! ToggleControl ) {
				return el( BlockEdit, props );
			}

			var attributes  = props.attributes || {};
			var restricted  = !! attributes.wpsRestricted;
			var required    = Array.isArray( attributes.wpsRequiredPlans )
				? attributes.wpsRequiredPlans
				: [];

			var children = [];

			if ( locked && Notice ) {
				children.push(
					el( Notice, {
						key: 'wps-pro-notice',
						status: 'warning',
						isDismissible: false
					}, i18n.proNotice || '' )
				);
			}

			children.push(
				el( ToggleControl, {
					key: 'wps-restrict-toggle',
					label: i18n.restrict || '',
					checked: restricted,
					disabled: locked,
					onChange: function ( value ) {
						props.setAttributes( { wpsRestricted: !! value } );
					}
				} )
			);

			if ( restricted ) {
				children.push(
					el( 'p', { key: 'wps-plans-label', className: 'description' },
						i18n.requiredPlans || '' )
				);
				children = children.concat( buildPlanControls( required, props.setAttributes ) );
				children.push(
					el( 'p', { key: 'wps-any-hint', className: 'description' },
						i18n.anyPlanHint || '' )
				);
			}

			var title = ( i18n.panelTitle || 'Membership Restriction' )
				+ ( locked ? ' (' + ( i18n.proTag || 'Pro' ) + ')' : '' );

			return el( Fragment, {},
				el( BlockEdit, props ),
				el( Inspector, {},
					el( PanelBody, { title: title, initialOpen: false }, children )
				)
			);
		};
	}, 'withWpsMembershipControls' );

	addFilter(
		'editor.BlockEdit',
		'wps/membership-restriction/controls',
		withControls
	);
} )( window.wp );
