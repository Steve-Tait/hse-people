/**
 * Business Details meta box: multi-image gallery picker using WordPress's
 * own core media library (wp.media), since the ACF Gallery field this
 * replaces requires ACF PRO. Selected attachment IDs are kept in a hidden
 * input, comma-separated, read by meta-box.php's save handler.
 */
( function ( $ ) {
	'use strict';

	function refreshHiddenInput( $wrap ) {
		var ids = $wrap.find( '.hse-business-gallery__item' ).map( function () {
			return $( this ).data( 'id' );
		} ).get();
		$wrap.find( '.hse-business-gallery__input' ).val( ids.join( ',' ) );
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
					$( '<li class="hse-business-gallery__item"><img alt=""><button type="button" class="hse-business-gallery__remove" aria-label="Remove image">&times;</button></li>' )
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
} )( jQuery );
