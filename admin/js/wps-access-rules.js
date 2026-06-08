/**
 * Access Rules tab — card-layout interactions (Day 12).
 *
 * Handles:
 *  - Card expand/collapse toggle.
 *  - Live summary update when form fields change.
 *  - Target-type switching (post_type / object / taxonomy sub-fields).
 *  - Behavior toggle (Message ↔ Redirect conditional fields).
 *  - AJAX object/term search with debounce + chip selection UI.
 *  - Add-rule button (clone <template> with fresh row index).
 *  - Remove-rule button.
 *  - "Copy merge tag" button.
 *  - "Any Plan" checkbox mutual exclusion.
 *
 * Depends on `wpsAccessRules` global localised by WPS_Access_Rules_Admin::enqueue_scripts().
 *
 * @since 2.0.0
 */
( function () {
	'use strict';

	var cfg   = window.wpsAccessRules || {};
	var AJAX  = cfg.ajaxUrl || '';
	var NONCE = cfg.nonce   || '';

	// -----------------------------------------------------------------------
	// Utilities
	// -----------------------------------------------------------------------

	function debounce( fn, ms ) {
		var t;
		return function () {
			var ctx  = this;
			var args = arguments;
			clearTimeout( t );
			t = setTimeout( function () { fn.apply( ctx, args ); }, ms );
		};
	}

	function setText( el, text ) {
		if ( el ) { el.textContent = text; }
	}

	// -----------------------------------------------------------------------
	// Card expand / collapse
	// -----------------------------------------------------------------------

	function openCard( card ) {
		card.classList.add( 'wps-rule-card--open' );
		var body   = card.querySelector( '.wps-rule-card__body' );
		var toggle = card.querySelector( '.wps-rule-card__toggle' );
		var chevron = toggle && toggle.querySelector( '.wps-chevron' );
		if ( body )    { body.removeAttribute( 'hidden' ); }
		if ( toggle )  { toggle.setAttribute( 'aria-expanded', 'true' ); }
		if ( chevron ) { chevron.style.transform = 'rotate(180deg)'; }
		if ( toggle )  { toggle.textContent = ''; toggle.appendChild( document.createTextNode( cfg.collapseLabel || 'Collapse' ) ); toggle.appendChild( chevron || makeChevron() ); }
	}

	function closeCard( card ) {
		card.classList.remove( 'wps-rule-card--open' );
		var body   = card.querySelector( '.wps-rule-card__body' );
		var toggle = card.querySelector( '.wps-rule-card__toggle' );
		var chevron = toggle && toggle.querySelector( '.wps-chevron' );
		if ( body )    { body.setAttribute( 'hidden', '' ); }
		if ( toggle )  { toggle.setAttribute( 'aria-expanded', 'false' ); }
		if ( chevron ) { chevron.style.transform = ''; }
		if ( toggle )  { toggle.textContent = ''; toggle.appendChild( document.createTextNode( cfg.editLabel || 'Edit' ) ); toggle.appendChild( chevron || makeChevron() ); }
	}

	function makeChevron() {
		var i = document.createElement( 'i' );
		i.className   = 'wps-chevron';
		i.textContent = '▾';
		return i;
	}

	// -----------------------------------------------------------------------
	// Summary update (called whenever a form field changes)
	// -----------------------------------------------------------------------

	function updateSummary( card ) {
		var targetSel  = card.querySelector( '.wps-rule-target-type' );
		var ptSel      = card.querySelector( '.wps-rule-post-type-select' );
		var taxSel     = card.querySelector( '.wps-rule-taxonomy-select' );
		var behaviorRadios = card.querySelectorAll( '.wps-rule-behavior-radio' );
		var planChecks = card.querySelectorAll( '.wps-plan-any-check, .wps-plan-specific-check' );
		var prioInput  = card.querySelector( '.wps-rule-priority' );

		var targetLbl  = card.querySelector( '.wps-rule-card__target-lbl' );
		var plansLbl   = card.querySelector( '.wps-rule-card__plans-lbl' );
		var badge      = card.querySelector( '.wps-badge' );
		var prioLbl    = card.querySelector( '.wps-rule-card__prio' );

		// Determine target summary.
		if ( targetSel ) {
			var type = targetSel.value;
			var targetText = '';
			if ( 'post_type' === type ) {
				var ptLabel = ptSel
					? ptSel.options[ ptSel.selectedIndex ].text
					: '';
				targetText = ( cfg.postTypeLabel || 'Post Type' ) + ': ' + ptLabel;
			} else if ( 'taxonomy' === type ) {
				var taxLabel = taxSel
					? taxSel.options[ taxSel.selectedIndex ].text
					: '';
				var taxTags = card.querySelectorAll( '.wps-target-taxonomy .wps-tag' );
				if ( taxTags.length ) {
					var taxNames = [];
					taxTags.forEach( function ( t ) {
						var clone = t.cloneNode( true );
						var rm = clone.querySelector( '.wps-remove-tag' );
						if ( rm ) { rm.parentNode.removeChild( rm ); }
						taxNames.push( clone.textContent.trim() );
					} );
					targetText = taxLabel + ': ' + taxNames.slice( 0, 2 ).join( ', ' );
					if ( taxNames.length > 2 ) { targetText += ' +' + ( taxNames.length - 2 ); }
				} else {
					targetText = taxLabel;
				}
			} else {
				var objTags = card.querySelectorAll( '.wps-target-object .wps-tag' );
				if ( objTags.length ) {
					var objNames = [];
					objTags.forEach( function ( t ) {
						var clone = t.cloneNode( true );
						var rm = clone.querySelector( '.wps-remove-tag' );
						if ( rm ) { rm.parentNode.removeChild( rm ); }
						objNames.push( clone.textContent.trim() );
					} );
					targetText = objNames.slice( 0, 2 ).join( ', ' );
					if ( objNames.length > 2 ) { targetText += ' +' + ( objNames.length - 2 ); }
				} else {
					targetText = targetSel.options[ targetSel.selectedIndex ].text;
				}
			}
			setText( targetLbl, targetText );
		}

		// Determine plans summary.
		if ( planChecks.length ) {
			var checked = [];
			planChecks.forEach( function ( cb ) {
				if ( cb.checked ) { checked.push( cb ); }
			} );
			var anyCheck = card.querySelector( '.wps-plan-any-check' );
			var plansText;
			if ( ! checked.length || ( anyCheck && anyCheck.checked ) ) {
				plansText = cfg.anyPlanLabel || 'Any Plan';
			} else if ( 1 === checked.length ) {
				plansText = checked[0].parentNode.textContent.trim();
			} else {
				plansText = checked.length + ' ' + ( cfg.plansLabel || 'Plans' );
			}
			setText( plansLbl, plansText );
		}

		// Determine behavior.
		if ( badge ) {
			var behavior = 'message';
			behaviorRadios.forEach( function ( r ) {
				if ( r.checked ) { behavior = r.value; }
			} );
			badge.className = 'wps-badge wps-badge--' + behavior;
			badge.textContent = 'redirect' === behavior
				? ( cfg.redirectLabel || 'Redirect' )
				: ( cfg.messageLabel || 'Message' );
		}

		// Update priority label.
		if ( prioLbl && prioInput ) {
			prioLbl.textContent = ( cfg.priorityLabel || 'Priority' ) + ': ' + prioInput.value;
		}
	}

	// -----------------------------------------------------------------------
	// Target-type sub-field visibility
	// -----------------------------------------------------------------------

	function switchTargetSub( card, type ) {
		card.querySelectorAll( '.wps-target-sub' ).forEach( function ( sub ) {
			sub.style.display = 'none';
		} );
		var objectTypes = [ 'post', 'page', 'product' ];
		if ( objectTypes.indexOf( type ) >= 0 ) {
			var o = card.querySelector( '.wps-target-object' );
			if ( o ) { o.style.display = ''; }
		} else if ( 'taxonomy' === type ) {
			var tx = card.querySelector( '.wps-target-taxonomy' );
			if ( tx ) { tx.style.display = ''; }
		} else {
			var pt = card.querySelector( '.wps-target-post_type' );
			if ( pt ) { pt.style.display = ''; }
		}
	}

	// -----------------------------------------------------------------------
	// Behavior field visibility
	// -----------------------------------------------------------------------

	function switchBehaviorField( card, behavior ) {
		var msgEl  = card.querySelector( '.wps-behavior-message' );
		var redEl  = card.querySelector( '.wps-behavior-redirect' );
		if ( msgEl ) { msgEl.style.display = 'redirect' === behavior ? 'none' : ''; }
		if ( redEl ) { redEl.style.display = 'redirect' === behavior ? '' : 'none'; }
	}

	// -----------------------------------------------------------------------
	// Plan pills — grant method contextual notice
	// -----------------------------------------------------------------------

	function updatePlanNotices( card ) {
		var notice = card.querySelector( '.wps-plan-pills__notice' );
		if ( ! notice ) { return; }

		var anyCheck = card.querySelector( '.wps-plan-any-check' );
		if ( anyCheck && anyCheck.checked ) {
			notice.style.display = 'none';
			return;
		}

		var checks  = card.querySelectorAll( '.wps-plan-specific-check:checked' );
		var hasSub  = false;
		var hasAuto = false;
		checks.forEach( function ( c ) {
			var m = c.getAttribute( 'data-grant-method' );
			if ( 'subscription' === m ) { hasSub  = true; }
			if ( 'auto_enroll'  === m ) { hasAuto = true; }
		} );

		if ( hasSub ) {
			notice.textContent   = cfg.subGrantNotice  || 'Access tied to subscription lifecycle.';
			notice.className     = 'wps-plan-pills__notice';
			notice.style.display = '';
		} else if ( hasAuto ) {
			notice.textContent   = cfg.autoGrantNotice || 'Auto-Enroll plan: access granted on registration.';
			notice.className     = 'wps-plan-pills__notice wps-plan-pills__notice--auto';
			notice.style.display = '';
		} else {
			notice.style.display = 'none';
		}
	}

	// -----------------------------------------------------------------------
	// "Any Plan" mutual exclusion
	// -----------------------------------------------------------------------

	function wireAnyPlan( card ) {
		var anyCheck      = card.querySelector( '.wps-plan-any-check' );
		var specificChecks = card.querySelectorAll( '.wps-plan-specific-check' );

		if ( ! anyCheck ) { return; }

		anyCheck.addEventListener( 'change', function () {
			if ( this.checked ) {
				specificChecks.forEach( function ( c ) { c.checked = false; } );
			}
			updatePlanNotices( card );
		} );

		specificChecks.forEach( function ( c ) {
			c.addEventListener( 'change', function () {
				if ( this.checked && anyCheck.checked ) {
					anyCheck.checked = false;
				}
				updatePlanNotices( card );
			} );
		} );

		// Apply initial state on page load.
		updatePlanNotices( card );
	}

	// -----------------------------------------------------------------------
	// AJAX target search
	// -----------------------------------------------------------------------

	function doSearch( searchInput, resultsList, spinner, targetType, taxonomy, tagContainer, idFieldName ) {
		var term = searchInput.value.trim();
		if ( term.length < 2 ) {
			resultsList.style.display = 'none';
			resultsList.innerHTML     = '';
			return;
		}

		resultsList.innerHTML = '<li class="wps-no-results">' +
			( cfg.searching || 'Searching…' ) + '</li>';
		resultsList.style.display = 'block';
		if ( spinner ) { spinner.classList.add( 'is-active' ); }

		var data = new FormData();
		data.append( 'action',      'wps_search_rule_targets' );
		data.append( 'nonce',       NONCE );
		data.append( 'target_type', targetType );
		data.append( 'term',        term );
		if ( 'taxonomy' === targetType ) {
			data.append( 'taxonomy', taxonomy );
		}

		fetch( AJAX, { method: 'POST', body: data } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( spinner ) { spinner.classList.remove( 'is-active' ); }
				resultsList.innerHTML = '';
				if ( ! res.success || ! res.data.results || ! res.data.results.length ) {
					resultsList.innerHTML = '<li class="wps-no-results">' +
						( cfg.noResults || 'No results found.' ) + '</li>';
					resultsList.style.display = 'block';
					return;
				}
				res.data.results.forEach( function ( item ) {
					var li        = document.createElement( 'li' );
					li.textContent = item.text;
					li.dataset.id   = item.id;
					li.addEventListener( 'mouseenter', function () {
						this.classList.add( 'is-highlighted' );
					} );
					li.addEventListener( 'mouseleave', function () {
						this.classList.remove( 'is-highlighted' );
					} );
					li.addEventListener( 'click', function ( e ) {
						e.preventDefault();
						addTag( tagContainer, idFieldName, item.id, item.text );
						searchInput.value         = '';
						resultsList.style.display = 'none';
						resultsList.innerHTML     = '';
					} );
					resultsList.appendChild( li );
				} );
				resultsList.style.display = 'block';
			} )
			.catch( function () {
				if ( spinner ) { spinner.classList.remove( 'is-active' ); }
				resultsList.style.display = 'none';
			} );
	}

	// -----------------------------------------------------------------------
	// Tag chip add
	// -----------------------------------------------------------------------

	function addTag( container, inputName, id, label ) {
		if ( container.querySelector( 'input[value="' + id + '"]' ) ) { return; }

		var span   = document.createElement( 'span' );
		span.className = 'wps-tag';

		var hidden  = document.createElement( 'input' );
		hidden.type  = 'hidden';
		hidden.name  = inputName;
		hidden.value = id;

		var btn           = document.createElement( 'button' );
		btn.type          = 'button';
		btn.className     = 'wps-remove-tag';
		btn.textContent   = cfg.removeItem || '×';
		btn.setAttribute( 'aria-label', cfg.removeLabel || 'Remove' );
		btn.addEventListener( 'click', function () {
			span.parentNode.removeChild( span );
		} );

		span.appendChild( document.createTextNode( label + ' ' ) );
		span.appendChild( hidden );
		span.appendChild( btn );
		container.appendChild( span );
	}

	// -----------------------------------------------------------------------
	// Wire a single card
	// -----------------------------------------------------------------------

	function wireCard( card ) {
		var idx = card.dataset.index;

		// Toggle button.
		var toggle = card.querySelector( '.wps-rule-card__toggle' );
		if ( toggle ) {
			toggle.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				if ( card.classList.contains( 'wps-rule-card--open' ) ) {
					closeCard( card );
				} else {
					openCard( card );
				}
			} );
		}

		// Header click (excluding action buttons).
		var header = card.querySelector( '.wps-rule-card__header' );
		if ( header ) {
			header.addEventListener( 'click', function ( e ) {
				if ( e.target.closest( '.wps-rule-card__actions' ) ) { return; }
				if ( card.classList.contains( 'wps-rule-card--open' ) ) {
					closeCard( card );
				} else {
					openCard( card );
				}
			} );
		}

		// Remove button.
		var removeBtn = card.querySelector( '.wps-remove-rule' );
		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', function () {
				card.parentNode.removeChild( card );
				toggleEmptyState();
			} );
		}

		// Target-type selector.
		var targetSel = card.querySelector( '.wps-rule-target-type' );
		if ( targetSel ) {
			targetSel.addEventListener( 'change', function () {
				switchTargetSub( card, this.value );
				updateSummary( card );
			} );
			switchTargetSub( card, targetSel.value );
		}

		// Behavior radios.
		card.querySelectorAll( '.wps-rule-behavior-radio' ).forEach( function ( r ) {
			r.addEventListener( 'change', function () {
				switchBehaviorField( card, this.value );
				updateSummary( card );
			} );
		} );

		// Any-plan mutual exclusion.
		wireAnyPlan( card );
		card.querySelectorAll( '.wps-plan-any-check, .wps-plan-specific-check' )
			.forEach( function ( cb ) {
				cb.addEventListener( 'change', function () { updateSummary( card ); } );
			} );

		// Priority change.
		var prioInput = card.querySelector( '.wps-rule-priority' );
		if ( prioInput ) {
			prioInput.addEventListener( 'input', function () { updateSummary( card ); } );
		}

		// Post-type select.
		var ptSel = card.querySelector( '.wps-rule-post-type-select' );
		if ( ptSel ) {
			ptSel.addEventListener( 'change', function () { updateSummary( card ); } );
		}

		// Taxonomy select.
		var taxSel = card.querySelector( '.wps-rule-taxonomy-select' );
		if ( taxSel ) {
			taxSel.addEventListener( 'change', function () { updateSummary( card ); } );
		}

		// Object search.
		var objectSub = card.querySelector( '.wps-target-object' );
		if ( objectSub ) {
			var objSearch  = objectSub.querySelector( '.wps-object-search' );
			var objResults = objectSub.querySelector( '.wps-search-results' );
			var objSpinner = objectSub.querySelector( '.wps-search-spinner' );
			var objTags    = objectSub.querySelector( '.wps-tag-container' );

			if ( objSearch && objResults && objTags ) {
				var doObjSearch = debounce( function () {
					var type = targetSel ? targetSel.value : 'post';
					var name = 'wps_rules[' + idx + '][object_ids][]';
					doSearch( objSearch, objResults, objSpinner, type, '', objTags, name );
					updateSummary( card );
				}, 300 );
				objSearch.addEventListener( 'input', doObjSearch );

				document.addEventListener( 'click', function ( e ) {
					if ( ! objResults.contains( e.target ) && ! objSearch.contains( e.target ) ) {
						objResults.style.display = 'none';
					}
				} );
			}
		}

		// Taxonomy term search.
		var taxSub = card.querySelector( '.wps-target-taxonomy' );
		if ( taxSub ) {
			var termSearch  = taxSub.querySelector( '.wps-term-search' );
			var termResults = taxSub.querySelector( '.wps-search-results' );
			var termSpinner = taxSub.querySelector( '.wps-search-spinner' );
			var termTags    = taxSub.querySelector( '.wps-tag-container' );

			if ( termSearch && termResults && termTags ) {
				var doTermSearch = debounce( function () {
					var taxonomy = taxSel ? taxSel.value : 'category';
					var name     = 'wps_rules[' + idx + '][term_ids][]';
					doSearch( termSearch, termResults, termSpinner, 'taxonomy', taxonomy, termTags, name );
					updateSummary( card );
				}, 300 );
				termSearch.addEventListener( 'input', doTermSearch );

				document.addEventListener( 'click', function ( e ) {
					if ( ! termResults.contains( e.target ) && ! termSearch.contains( e.target ) ) {
						termResults.style.display = 'none';
					}
				} );
			}
		}

		// Server-rendered remove-tag buttons.
		card.querySelectorAll( '.wps-remove-tag' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var tag = btn.closest( '.wps-tag' );
				if ( tag ) {
					tag.parentNode.removeChild( tag );
					updateSummary( card );
				}
			} );
		} );
	}

	// -----------------------------------------------------------------------
	// Empty state visibility
	// -----------------------------------------------------------------------

	function toggleEmptyState() {
		var list  = document.getElementById( 'wps-rules-list' );
		var empty = document.getElementById( 'wps-rules-empty' );
		if ( ! list || ! empty ) { return; }
		var hasCards = list.querySelectorAll( '.wps-rule-card' ).length > 0;
		empty.style.display = hasCards ? 'none' : '';
	}

	// -----------------------------------------------------------------------
	// Copy-merge-tag buttons
	// -----------------------------------------------------------------------

	function wireCopyTagButtons( root ) {
		( root || document ).querySelectorAll( '.wps-copy-tag' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var tag = btn.dataset.tag || '';
				if ( ! tag ) { return; }

				var target = btn.closest( '.wps-field-group, .wps-defaults-grid__field' );
				var textarea = target ? target.querySelector( 'textarea' ) : null;

				if ( textarea ) {
					var start = textarea.selectionStart;
					var end   = textarea.selectionEnd;
					textarea.value = textarea.value.substring( 0, start ) + tag + textarea.value.substring( end );
					textarea.selectionStart = textarea.selectionEnd = start + tag.length;
					textarea.focus();
				} else {
					navigator.clipboard && navigator.clipboard.writeText( tag );
				}

				btn.classList.add( 'wps-copy-tag--copied' );
				setTimeout( function () { btn.classList.remove( 'wps-copy-tag--copied' ); }, 1500 );
			} );
		} );
	}

	// -----------------------------------------------------------------------
	// Init
	// -----------------------------------------------------------------------

	document.addEventListener( 'DOMContentLoaded', function () {
		var list   = document.getElementById( 'wps-rules-list' );
		var addBtn = document.getElementById( 'wps-add-rule' );
		var tpl    = document.getElementById( 'wps-rule-row-template' );

		if ( ! list ) { return; }

		// Wire existing cards.
		list.querySelectorAll( '.wps-rule-card' ).forEach( wireCard );

		// Wire global copy-tag buttons.
		wireCopyTagButtons( document );

		// -----------------------------------------------------------------------
		// Global Defaults — collapsible section
		// -----------------------------------------------------------------------
		var defaultsToggle = document.getElementById( 'wps-defaults-toggle' );
		var defaultsBody   = document.querySelector( '.wps-defaults-body' );

		if ( defaultsToggle && defaultsBody ) {
			function openDefaults() {
				defaultsBody.removeAttribute( 'hidden' );
				defaultsToggle.setAttribute( 'aria-expanded', 'true' );
			}
			function closeDefaults() {
				defaultsBody.setAttribute( 'hidden', '' );
				defaultsToggle.setAttribute( 'aria-expanded', 'false' );
			}
			defaultsToggle.addEventListener( 'click', function () {
				if ( 'true' === defaultsToggle.getAttribute( 'aria-expanded' ) ) {
					closeDefaults();
				} else {
					openDefaults();
				}
			} );
			defaultsToggle.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key || ' ' === e.key ) {
					e.preventDefault();
					defaultsToggle.click();
				}
			} );
		}

		// -----------------------------------------------------------------------
		// Global Defaults — behavior radio shows/hides messages vs redirect URL
		// -----------------------------------------------------------------------
		var defBehaviorRadios = document.querySelectorAll(
			'[name="wps_access_default_behavior"]'
		);
		var defMessagesRow  = document.querySelector( '.wps-defaults-messages' );
		var defRedirectRow  = document.querySelector( '.wps-defaults-redirect' );

		if ( defBehaviorRadios.length && defMessagesRow && defRedirectRow ) {
			function applyDefaultBehavior( value ) {
				if ( 'redirect' === value ) {
					defMessagesRow.style.display = 'none';
					defRedirectRow.style.display  = '';
				} else {
					defMessagesRow.style.display = '';
					defRedirectRow.style.display  = 'none';
				}
			}
			defBehaviorRadios.forEach( function ( r ) {
				r.addEventListener( 'change', function () {
					applyDefaultBehavior( this.value );
				} );
			} );
		}

		// Add-rule button.
		if ( addBtn && tpl ) {
			addBtn.addEventListener( 'click', function () {
				var nextIdx  = list.querySelectorAll( '.wps-rule-card' ).length;
				var fragment = tpl.content.cloneNode( true );
				var newCard  = fragment.querySelector( '.wps-rule-card' );

				newCard.innerHTML    = newCard.innerHTML.replace( /__IDX__/g, nextIdx );
				newCard.dataset.index = nextIdx;

				list.appendChild( newCard );
				var inserted = list.lastElementChild;
				wireCard( inserted );
				wireCopyTagButtons( inserted );
				toggleEmptyState();

				// Scroll new card into view.
				inserted.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			} );
		}
	} );
}() );
