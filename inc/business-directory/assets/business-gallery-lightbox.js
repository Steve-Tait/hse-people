/**
 * Lightbox for the single-supplier Business Gallery: click a thumbnail to
 * open a full-screen overlay showing that image, with prev/next to cycle
 * through the rest of the gallery. Plain vanilla JS, no external lightbox
 * library -- same dependency-free approach as the category banner slider
 * (business-directory-banner-slider.js).
 */
( function () {
	'use strict';

	document.querySelectorAll( '.business-single__gallery-grid' ).forEach( function ( grid ) {
		var thumbs = Array.prototype.slice.call( grid.querySelectorAll( '.business-single__gallery-thumb' ) );
		if ( ! thumbs.length ) {
			return;
		}

		var overlay = document.createElement( 'div' );
		overlay.className = 'business-gallery-lightbox';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.hidden = true;
		overlay.innerHTML =
			'<button type="button" class="business-gallery-lightbox__close" aria-label="Close">&times;</button>' +
			'<button type="button" class="business-gallery-lightbox__nav business-gallery-lightbox__nav--prev" aria-label="Previous image">&#8249;</button>' +
			'<img class="business-gallery-lightbox__image" alt="" />' +
			'<button type="button" class="business-gallery-lightbox__nav business-gallery-lightbox__nav--next" aria-label="Next image">&#8250;</button>';
		document.body.appendChild( overlay );

		var image = overlay.querySelector( '.business-gallery-lightbox__image' );
		var closeBtn = overlay.querySelector( '.business-gallery-lightbox__close' );
		var prevBtn = overlay.querySelector( '.business-gallery-lightbox__nav--prev' );
		var nextBtn = overlay.querySelector( '.business-gallery-lightbox__nav--next' );
		var currentIndex = 0;
		var lastFocused = null;

		if ( thumbs.length < 2 ) {
			prevBtn.hidden = true;
			nextBtn.hidden = true;
		}

		function show( index ) {
			currentIndex = ( index + thumbs.length ) % thumbs.length;
			var thumb = thumbs[ currentIndex ];
			var thumbImg = thumb.querySelector( 'img' );
			image.src = thumb.getAttribute( 'data-full' );
			image.alt = thumbImg ? thumbImg.alt : '';
		}

		function open( index ) {
			lastFocused = document.activeElement;
			show( index );
			overlay.hidden = false;
			document.body.classList.add( 'business-gallery-lightbox-open' );
			closeBtn.focus();
		}

		function close() {
			overlay.hidden = true;
			document.body.classList.remove( 'business-gallery-lightbox-open' );
			if ( lastFocused && lastFocused.focus ) {
				lastFocused.focus();
			}
		}

		thumbs.forEach( function ( thumb, index ) {
			thumb.addEventListener( 'click', function () {
				open( index );
			} );
		} );

		closeBtn.addEventListener( 'click', close );
		prevBtn.addEventListener( 'click', function () { show( currentIndex - 1 ); } );
		nextBtn.addEventListener( 'click', function () { show( currentIndex + 1 ); } );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				close();
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( overlay.hidden ) {
				return;
			}
			if ( 'Escape' === e.key ) {
				close();
			} else if ( 'ArrowLeft' === e.key ) {
				show( currentIndex - 1 );
			} else if ( 'ArrowRight' === e.key ) {
				show( currentIndex + 1 );
			}
		} );
	} );
} )();
