/**
 * Business Details meta box:
 *  - multi-image gallery picker using WordPress's own core media library
 *    (wp.media), since the ACF Gallery field this replaces requires ACF
 *    PRO. Selected attachment IDs are kept in a hidden input,
 *    comma-separated, read by meta-box.php's save handler.
 *  - shows/hides tier-gated field groups (Social Media Links, Review,
 *    Catalog URL) based on the selected Tier radio. This is purely an
 *    editing-UI restriction -- hidden fields still submit their existing
 *    values on save, so downgrading the tier doesn't clear anything.
 *  - a live "Open in a new tab" preview link under the Catalog URL field
 *    that updates as the field is typed into.
 *
 * Also used, unmodified, on the business_genre term-edit screen (see
 * inc/business-directory/genre-meta.php) -- the gallery picker there is
 * the Category Banner Images field, plus a single-image variant below
 * for the Results Grid Promo Image field. Everything here is purely
 * class-based with no post-specific assumptions, so the same file works
 * on both screens.
 */
( function ( $ ) {
	'use strict';

	// business_accreditation is hierarchical (accreditation schemes
	// grouped under their issuing body); a business should only ever be
	// tagged with a specific scheme, never the issuing-body group itself
	// (see taxonomies.php, which also enforces this server-side as the
	// real guard -- this is just so unchecking one silently reverting on
	// save doesn't look broken). WordPress's own checkbox meta box
	// (#business_accreditationchecklist) puts top-level terms as direct
	// <li> children of the list and nested ones inside a `.children` <ul>,
	// so this only reaches the top-level checkboxes. No-ops entirely on
	// screens without that element (e.g. the term-edit screen).
	document.querySelectorAll( '#business_accreditationchecklist > li > label' ).forEach( function ( label ) {
		var checkbox = label.querySelector( 'input[type="checkbox"]' );
		if ( ! checkbox ) {
			return;
		}
		checkbox.disabled = true;
		checkbox.checked = false;
		label.classList.add( 'hse-accreditation-parent' );
		label.title = 'This is an issuing body, not an accreditation itself -- select one of the specific schemes underneath it instead.';
	} );

	function updateTierVisibility() {
		var checked = document.querySelector( 'input[name="business_tier"]:checked' );
		var tier = checked ? checked.value : 'free';

		document.querySelectorAll( '.hse-business-fields__group[data-show-for-tiers]' ).forEach( function ( tbody ) {
			var tiers = tbody.getAttribute( 'data-show-for-tiers' ).split( ',' );
			tbody.style.display = tiers.indexOf( tier ) !== -1 ? '' : 'none';
		} );
	}

	document.addEventListener( 'change', function ( e ) {
		if ( 'business_tier' === e.target.name ) {
			updateTierVisibility();
		}
	} );

	updateTierVisibility();

	document.querySelectorAll( '.hse-business-live-preview-input' ).forEach( function ( input ) {
		var link = document.getElementById( input.getAttribute( 'data-preview-target' ) );
		if ( ! link ) {
			return;
		}

		input.addEventListener( 'input', function () {
			var value = input.value.trim();
			if ( value ) {
				link.href = value;
				link.style.display = '';
			} else {
				link.style.display = 'none';
			}
		} );
	} );

	function refreshHiddenInput( $wrap ) {
		var items = $wrap.find( '.hse-business-gallery__item' ).map( function () {
			return {
				id: $( this ).data( 'id' ),
				link: $( this ).find( '.hse-business-gallery__link' ).val() || ''
			};
		} ).get();
		$wrap.find( '.hse-business-gallery__input' ).val( JSON.stringify( items ) );
	}

	$( document ).on( 'click', '.hse-business-gallery__add', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.hse-business-gallery' );
		var $list = $wrap.find( '.hse-business-gallery__items' );

		var frame = wp.media( {
			title: 'Select Images',
			button: { text: 'Add to gallery' },
			multiple: true,
		} );

		frame.on( 'select', function () {
			frame.state().get( 'selection' ).each( function ( attachment ) {
				attachment = attachment.toJSON();

				if ( $list.find( '[data-id="' + attachment.id + '"]' ).length ) {
					return;
				}

				var thumbUrl = ( attachment.sizes && attachment.sizes.thumbnail )
					? attachment.sizes.thumbnail.url
					: attachment.url;

				$list.append(
					$( '<li class="hse-business-gallery__item"><img alt=""><input type="text" class="hse-business-gallery__link" placeholder="Link URL (optional)"><button type="button" class="hse-business-gallery__remove" aria-label="Remove image">&times;</button></li>' )
						.attr( 'data-id', attachment.id )
						.find( 'img' ).attr( 'src', thumbUrl ).end()
				);
			} );

			refreshHiddenInput( $wrap );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.hse-business-gallery__remove', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.hse-business-gallery' );
		$( this ).closest( '.hse-business-gallery__item' ).remove();
		refreshHiddenInput( $wrap );
	} );

	$( document ).on( 'input', '.hse-business-gallery__link', function () {
		refreshHiddenInput( $( this ).closest( '.hse-business-gallery' ) );
	} );

	$( document ).on( 'click', '.hse-single-image-picker__select', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.hse-single-image-picker' );

		var frame = wp.media( {
			title: 'Select Image',
			button: { text: 'Use this image' },
			multiple: false,
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var thumbUrl = ( attachment.sizes && attachment.sizes.medium )
				? attachment.sizes.medium.url
				: attachment.url;

			$wrap.find( '.hse-single-image-picker__input' ).val( attachment.id );
			$wrap.find( '.hse-single-image-picker__preview' ).html( '<img src="' + thumbUrl + '" alt="">' );
			$wrap.find( '.hse-single-image-picker__remove' ).show();
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.hse-single-image-picker__remove', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.hse-single-image-picker' );
		$wrap.find( '.hse-single-image-picker__input' ).val( '' );
		$wrap.find( '.hse-single-image-picker__preview' ).empty();
		$wrap.find( '.hse-single-image-picker__link' ).val( '' );
		$( this ).hide();
	} );
} )( jQuery );
