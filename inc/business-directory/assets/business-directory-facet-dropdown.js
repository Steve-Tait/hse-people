/**
 * Category / Badges dropdown filters on the Business Directory page.
 *
 * Checkbox clicks inside the panel only toggle their visual state -- they
 * don't trigger FacetWP's normal instant AJAX refresh. The refresh only
 * happens when Apply is pressed; Reset clears the panel's checkboxes and
 * refreshes immediately.
 *
 * FacetWP binds its own checkbox click handler at the bubble phase on
 * `document` (see facetwp/assets/js/src/front-facets.js), which calls
 * FWP.autoload() on every click. To stop that without touching FacetWP's
 * code, a capture-phase listener on `document` intercepts the click first
 * (capture always runs before bubble) and calls stopPropagation() so
 * FacetWP's handler never fires for checkboxes inside a dropdown panel.
 */
( function () {
	'use strict';

	function closeDropdown( wrap ) {
		wrap.classList.remove( 'is-open' );
		var toggle = wrap.querySelector( '.facet-dropdown__toggle' );
		if ( toggle ) {
			toggle.setAttribute( 'aria-expanded', 'false' );
		}
	}

	function openDropdown( wrap ) {
		wrap.classList.add( 'is-open' );
		var toggle = wrap.querySelector( '.facet-dropdown__toggle' );
		if ( toggle ) {
			toggle.setAttribute( 'aria-expanded', 'true' );
		}
	}

	function closeAllExcept( except ) {
		document.querySelectorAll( '[data-facet-dropdown].is-open' ).forEach( function ( wrap ) {
			if ( wrap !== except ) {
				closeDropdown( wrap );
			}
		} );
	}

	function refresh() {
		if ( window.FWP ) {
			window.FWP.autoload();
		}
	}

	// Intercept checkbox clicks before FacetWP's own handler sees them.
	document.addEventListener( 'click', function ( e ) {
		var checkbox = e.target.closest( '.facet-dropdown__panel .facetwp-checkbox:not(.disabled)' );
		if ( ! checkbox ) {
			return;
		}
		e.stopPropagation();
		checkbox.classList.toggle( 'checked' );
	}, true );

	// Toggle / Apply / Reset / click-outside.
	document.addEventListener( 'click', function ( e ) {
		var toggle = e.target.closest( '.facet-dropdown__toggle' );
		if ( toggle ) {
			var toggleWrap = toggle.closest( '[data-facet-dropdown]' );
			var wasOpen = toggleWrap.classList.contains( 'is-open' );
			closeAllExcept( toggleWrap );
			if ( wasOpen ) {
				closeDropdown( toggleWrap );
			} else {
				openDropdown( toggleWrap );
			}
			return;
		}

		var apply = e.target.closest( '.facet-dropdown__apply' );
		if ( apply ) {
			var applyWrap = apply.closest( '[data-facet-dropdown]' );
			refresh();
			closeDropdown( applyWrap );
			return;
		}

		var reset = e.target.closest( '.facet-dropdown__reset' );
		if ( reset ) {
			var resetWrap = reset.closest( '[data-facet-dropdown]' );
			resetWrap.querySelectorAll( '.facetwp-checkbox.checked' ).forEach( function ( cb ) {
				cb.classList.remove( 'checked' );
			} );
			refresh();
			closeDropdown( resetWrap );
			return;
		}

		if ( ! e.target.closest( '[data-facet-dropdown]' ) ) {
			closeAllExcept( null );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			closeAllExcept( null );
		}
	} );
} )();
