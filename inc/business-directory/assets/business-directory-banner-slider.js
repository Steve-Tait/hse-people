/**
 * Category banner slider on the locked directory page. Plain horizontal
 * scroll-snap strip (see business-directory.css) -- this just wires the
 * prev/next buttons to scroll by one slide's width. No autoplay.
 */
( function () {
	'use strict';

	document.querySelectorAll( '[data-banner-slider]' ).forEach( function ( slider ) {
		var track = slider.querySelector( '.business-directory__banner-track' );
		var prev = slider.querySelector( '.business-directory__banner-nav--prev' );
		var next = slider.querySelector( '.business-directory__banner-nav--next' );

		if ( ! track ) {
			return;
		}

		function slideStep() {
			var slide = track.querySelector( '.business-directory__banner-slide' );
			return slide ? slide.getBoundingClientRect().width + 12 : track.clientWidth;
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				track.scrollBy( { left: -slideStep(), behavior: 'smooth' } );
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				track.scrollBy( { left: slideStep(), behavior: 'smooth' } );
			} );
		}
	} );
} )();
