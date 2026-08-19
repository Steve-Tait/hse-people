<?php
/**
 * Shared icon and badge-rendering helpers for the Business/Supplier
 * directory, used by both single-business.php and the card partial.
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
 * Icon + colour per badge slug. New badge terms fall back to a plain tag
 * icon in the default badge colour rather than failing to render.
 */
function hse_business_badge_meta( $slug ) {
	$map = [
		'featured' => [ 'icon' => 'star', 'color' => '#b8860b' ],
		'verified' => [ 'icon' => 'check-circle', 'color' => '#2e7d32' ],
		'bsif-affiliate-member' => [ 'icon' => 'shield-check', 'color' => '#044f8d' ],
		'bsif-rsss-member' => [ 'icon' => 'shield-check', 'color' => '#0f6674' ],
		'accredited' => [ 'icon' => 'award', 'color' => '#6a4c93' ],
	];

	return $map[ $slug ] ?? [ 'icon' => 'tag', 'color' => '#555555' ];
}

/**
 * Renders a list of business_badge term objects as coloured, icon-led pills.
 */
function hse_business_render_badges( $badges ) {
	if ( empty( $badges ) ) {
		return;
	}
	foreach ( $badges as $badge ) {
		$meta = hse_business_badge_meta( $badge->slug );
		printf(
			'<span class="business-badge" style="--badge-color:%1$s;">%2$s%3$s</span>',
			esc_attr( $meta['color'] ),
			hse_business_icon( $meta['icon'] ),
			esc_html( $badge->name )
		);
	}
}
