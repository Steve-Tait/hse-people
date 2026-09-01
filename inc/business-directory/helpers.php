<?php
/**
 * Shared icon and accreditation-pill-rendering helpers for the
 * Business/Supplier directory, used by both single-business.php and the
 * card partial.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline SVG icons (24x24, stroke-based) so they inherit `currentColor`
 * without an extra HTTP request per icon.
 */
function hse_business_icon( $name ) {
	$icons = [
		'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'email' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'address' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'website' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
		'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
		'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
		'shield-check' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
		'award' => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>',
		'tag' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
	];

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return '<svg class="hse-icon hse-icon--' . esc_attr( $name ) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

/**
 * Social platform icons (24x24, filled) -- brand marks read better solid
 * than as the stroke-based line icons above, so these get their own
 * wrapper with fill instead of stroke.
 */
function hse_business_social_icon( $network ) {
	$icons = [
		'facebook'  => '<path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/>',
		'youtube'   => '<path d="M23.5 6.19a3 3 0 0 0-2.11-2.12C19.51 3.5 12 3.5 12 3.5s-7.51 0-9.39.57A3 3 0 0 0 .5 6.2 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.81 3 3 0 0 0 2.11 2.12c1.88.57 9.39.57 9.39.57s7.51 0 9.39-.57a3 3 0 0 0 2.11-2.12A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.81ZM9.6 15.5v-7l6.2 3.5Z"/>',
		'instagram' => '<path d="M12 2c-2.72 0-3.06.01-4.13.06-1.06.05-1.79.22-2.43.47a4.9 4.9 0 0 0-1.77 1.15A4.9 4.9 0 0 0 2.53 5.44c-.25.64-.42 1.37-.47 2.44C2.01 8.94 2 9.28 2 12s.01 3.06.06 4.12c.05 1.07.22 1.8.47 2.44a4.9 4.9 0 0 0 1.15 1.77 4.9 4.9 0 0 0 1.77 1.15c.64.25 1.37.42 2.43.47C8.94 21.99 9.28 22 12 22s3.06-.01 4.13-.06c1.06-.05 1.79-.22 2.43-.47a4.9 4.9 0 0 0 1.77-1.15 4.9 4.9 0 0 0 1.15-1.77c.25-.64.42-1.37.47-2.44.05-1.06.06-1.4.06-4.12s-.01-3.06-.06-4.12c-.05-1.07-.22-1.8-.47-2.44a4.9 4.9 0 0 0-1.15-1.77A4.9 4.9 0 0 0 18.56 2.53c-.64-.25-1.37-.42-2.43-.47C15.06 2.01 14.72 2 12 2Zm0 1.8c2.67 0 2.99.01 4.04.06.97.04 1.5.2 1.85.34.46.18.79.4 1.14.75.35.35.57.68.75 1.14.14.35.3.88.34 1.85.05 1.05.06 1.37.06 4.06s-.01 3.01-.06 4.06c-.04.97-.2 1.5-.34 1.85-.18.46-.4.79-.75 1.14-.35.35-.68.57-1.14.75-.35.14-.88.3-1.85.34-1.05.05-1.37.06-4.04.06s-2.99-.01-4.04-.06c-.97-.04-1.5-.2-1.85-.34a3.1 3.1 0 0 1-1.14-.75 3.1 3.1 0 0 1-.75-1.14c-.14-.35-.3-.88-.34-1.85C3.81 15 3.8 14.68 3.8 12s.01-3.01.06-4.06c.04-.97.2-1.5.34-1.85.18-.46.4-.79.75-1.14.35-.35.68-.57 1.14-.75.35-.14.88-.3 1.85-.34C9.03 3.81 9.35 3.8 12 3.8Zm0 3.06a5.14 5.14 0 1 0 0 10.28 5.14 5.14 0 0 0 0-10.28Zm0 8.48a3.34 3.34 0 1 1 0-6.68 3.34 3.34 0 0 1 0 6.68Zm5.34-8.68a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0Z"/>',
		'linkedin'  => '<path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.03-1.85-3.03-1.86 0-2.15 1.45-2.15 2.94v5.66H9.36V9h3.41v1.56h.05c.48-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12ZM7.12 20.45H3.56V9h3.56v11.45ZM22.22 0H1.77C.8 0 0 .78 0 1.75v20.5C0 23.22.8 24 1.77 24h20.45c.98 0 1.78-.78 1.78-1.75V1.75C24 .78 23.2 0 22.22 0Z"/>',
		'x'         => '<path d="M18.9 2H22l-7.6 8.72L23.3 22h-7.1l-5.55-7.26L4.42 22H1.3l8.13-9.32L1 2h7.28l5.02 6.64L18.9 2Zm-1.24 18h1.94L7.44 4H5.36l12.3 16Z"/>',
	];

	if ( ! isset( $icons[ $network ] ) ) {
		return '';
	}

	return '<svg class="hse-icon hse-icon--' . esc_attr( $network ) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' . $icons[ $network ] . '</svg>';
}

/**
 * Icon + colour per accreditation slug. New accreditation terms fall back
 * to a plain tag icon in the default colour rather than failing to
 * render. 'featured' isn't handled here -- Featured is a checkbox on the
 * business post itself (see inc/business-directory/featured.php), not an
 * accreditation term.
 */
function hse_business_accreditation_meta( $slug ) {
	$map = [
		'verified' => [ 'icon' => 'check-circle', 'color' => '#2e7d32' ],
		'bsif-affiliate-member' => [ 'icon' => 'shield-check', 'color' => '#044f8d' ],
		'bsif-rsss-member' => [ 'icon' => 'shield-check', 'color' => '#0f6674' ],
		'accredited' => [ 'icon' => 'award', 'color' => '#6a4c93' ],
	];

	return $map[ $slug ] ?? [ 'icon' => 'tag', 'color' => '#555555' ];
}

/**
 * Renders a list of business_accreditation term objects as coloured,
 * icon-led pills.
 */
function hse_business_render_accreditations( $accreditations ) {
	if ( empty( $accreditations ) ) {
		return;
	}
	foreach ( $accreditations as $accreditation ) {
		$meta = hse_business_accreditation_meta( $accreditation->slug );
		printf(
			'<span class="business-accreditation" style="--accreditation-color:%1$s;">%2$s%3$s</span>',
			esc_attr( $meta['color'] ),
			hse_business_icon( $meta['icon'] ),
			esc_html( $accreditation->name )
		);
	}
}

/**
 * Renders the same pill style as an accreditation, for a business marked
 * Featured (see inc/business-directory/featured.php) -- shown first,
 * ahead of any real accreditation pills, when is_sticky() is true for
 * this post.
 */
function hse_business_render_featured_badge( $post_id ) {
	if ( ! is_sticky( $post_id ) ) {
		return;
	}
	printf(
		'<span class="business-accreditation" style="--accreditation-color:%1$s;">%2$s%3$s</span>',
		esc_attr( '#b8860b' ),
		hse_business_icon( 'star' ),
		esc_html__( 'Featured', 'astra' )
	);
}
