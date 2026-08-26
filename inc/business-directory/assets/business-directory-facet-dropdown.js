/**
 * Category / Badges / Location dropdown filters on the Business Directory
 * page.
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
 *
 * The Location facet is hierarchical (regions containing towns/cities).
 * FacetWP's own hierarchical behaviour treats parent and child as
 * mutually-exclusive alternative filters (checking a parent unchecks its
 * children and vice versa) -- not what's wanted here. Since the capture
 * interceptor above already takes over checkbox click handling entirely
 * for facets inside a dropdown panel, custom tri-state (checked / partial
 * / unchecked) parent-child logic is layered in below instead: checking a
 * parent checks its whole child list; checking/unchecking a child
 * recomputes its parent as fully checked (all children checked), partial
 * (some checked), or unchecked (none checked).
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

	function isCheckbox( el ) {
		return !! ( el && el.classList && el.classList.contains( 'facetwp-checkbox' ) );
	}

	function isDepth( el ) {
		return !! ( el && el.classList && el.classList.contains( 'facetwp-depth' ) );
	}

	// Recomputes every parent checkbox's checked/indeterminate state from
	// its own direct children, deepest level first, so a grandparent's
	// state is derived from already-correct parent state above it.
	function syncHierarchyStates( container ) {
		if ( ! container ) {
			return;
		}

		Array.prototype.forEach.call( container.children, function ( el ) {
			if ( isDepth( el ) ) {
				syncHierarchyStates( el );
			}
		} );

		Array.prototype.forEach.call( container.children, function ( cb ) {
			if ( ! isCheckbox( cb ) ) {
				return;
			}

			var depth = cb.nextElementSibling;
			if ( ! isDepth( depth ) ) {
				return;
			}

			var children = Array.prototype.filter.call( depth.children, isCheckbox );
			if ( ! children.length ) {
				return;
			}

			var allChecked = children.every( function ( c ) {
				return c.classList.contains( 'checked' );
			} );
			var anyChecked = children.some( function ( c ) {
				return c.classList.contains( 'checked' ) || c.classList.contains( 'is-indeterminate' );
			} );

			cb.classList.toggle( 'checked', allChecked );
			cb.classList.toggle( 'is-indeterminate', ! allChecked && anyChecked );
		} );
	}

	// A selection already covered by a fully-checked ancestor (e.g. every
	// child under a checked parent) isn't counted again -- "1 selected"
	// for a whole checked region, not "1 + however many child towns".
	function meaningfulSelections( wrap ) {
		var checked = wrap.querySelectorAll( '.facetwp-checkbox.checked' );
		var meaningful = [];

		checked.forEach( function ( cb ) {
			var depth = cb.closest( '.facetwp-depth' );
			var parentCheckbox = depth ? depth.previousElementSibling : null;

			if ( parentCheckbox && isCheckbox( parentCheckbox ) && parentCheckbox.classList.contains( 'checked' ) ) {
				return;
			}

			meaningful.push( cb );
		} );

		return meaningful;
	}

	// Reflects the toggle's *applied* state, not the panel's live/pending
	// checkbox state -- only called on load and after Apply/Reset, the
	// points at which a selection actually takes effect.
	function updateToggleText( wrap ) {
		var valueEl = wrap.querySelector( '.facet-dropdown__value' );
		if ( ! valueEl ) {
			return;
		}

		var selected = meaningfulSelections( wrap );

		if ( 0 === selected.length ) {
			valueEl.textContent = wrap.getAttribute( 'data-placeholder' ) || '';
			valueEl.classList.add( 'is-placeholder' );
		} else if ( 1 === selected.length ) {
			var display = selected[ 0 ].querySelector( '.facetwp-display-value' );
			valueEl.textContent = display ? display.textContent.trim() : '';
			valueEl.classList.remove( 'is-placeholder' );
		} else {
			valueEl.textContent = selected.length + ' selected';
			valueEl.classList.remove( 'is-placeholder' );
		}
	}

	// Re-derives parent checked/indeterminate state from whatever FacetWP
	// just rendered (it has no concept of the tri-state itself), then
	// updates the toggle label from the result. Used on first load and
	// after every refresh (Apply/Reset/URL-driven).
	function syncWrap( wrap ) {
		syncHierarchyStates( wrap.querySelector( '.facetwp-facet' ) );
		updateToggleText( wrap );
	}

	document.querySelectorAll( '[data-facet-dropdown]' ).forEach( syncWrap );

	// FacetWP re-applies filters carried in the URL (e.g. after a page
	// refresh) via its own async AJAX call on load, so the checked classes
	// aren't in the DOM yet at the point the pass above runs. FacetWP fires
	// this event after every refresh completes -- including that initial
	// one -- so re-sync all toggle labels whenever it does.
	document.addEventListener( 'facetwp-loaded', function () {
		document.querySelectorAll( '[data-facet-dropdown]' ).forEach( syncWrap );
	} );

	// Intercept checkbox clicks before FacetWP's own handler sees them.
	document.addEventListener( 'click', function ( e ) {
		// FacetWP's own expand/collapse "+" link for hierarchical facets
		// lives inside the parent .facetwp-checkbox -- let it (and
		// FacetWP's own handler for it) run normally rather than treating
		// it as a checkbox toggle.
		if ( e.target.closest( '.facetwp-expand' ) ) {
			return;
		}

		var checkbox = e.target.closest( '.facet-dropdown__panel .facetwp-checkbox:not(.disabled)' );
		if ( ! checkbox ) {
			return;
		}
		e.stopPropagation();

		var newChecked = ! checkbox.classList.contains( 'checked' );
		checkbox.classList.toggle( 'checked', newChecked );
		checkbox.classList.remove( 'is-indeterminate' );

		// Checking/unchecking a parent applies the same state to its
		// entire child list (all descendants, any depth).
		var depth = checkbox.nextElementSibling;
		if ( isDepth( depth ) ) {
			depth.querySelectorAll( '.facetwp-checkbox' ).forEach( function ( cb ) {
				cb.classList.toggle( 'checked', newChecked );
				cb.classList.remove( 'is-indeterminate' );
			} );
		}

		syncHierarchyStates( checkbox.closest( '.facetwp-facet' ) );
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
			syncWrap( applyWrap );
			closeDropdown( applyWrap );
			return;
		}

		var reset = e.target.closest( '.facet-dropdown__reset' );
		if ( reset ) {
			var resetWrap = reset.closest( '[data-facet-dropdown]' );
			resetWrap.querySelectorAll( '.facetwp-checkbox.checked, .facetwp-checkbox.is-indeterminate' ).forEach( function ( cb ) {
				cb.classList.remove( 'checked', 'is-indeterminate' );
			} );
			refresh();
			syncWrap( resetWrap );
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
