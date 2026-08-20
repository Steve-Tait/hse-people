<?php
/**
 * The Category and Badges filters on the Business Directory page now render
 * inside a scrollable dropdown panel (see
 * template-parts/business/facet-dropdown.php) with their own Apply/Reset
 * buttons, instead of FacetWP's default inline checkbox list.
 *
 * FacetWP's `soft_limit` setting hides checkboxes beyond that count behind
 * a "+ N more" toggle link -- redundant now that the whole list scrolls
 * inside the dropdown panel, and the toggle link doesn't fit that layout.
 *
 * `business_badge` is a code-registered facet (see
 * inc/business-directory/facetwp-facets.php) so it needed no DB change.
 * `business_genre` remains DB-managed via FacetWP's admin screen (the
 * site's pre-existing convention), so its `soft_limit` is fixed here.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-20-facet-dropdown-soft-limit-fix.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$raw = get_option( 'facetwp_settings' );
$settings = is_string( $raw ) ? json_decode( $raw, true ) : $raw;

if ( ! is_array( $settings ) || empty( $settings['facets'] ) ) {
	WP_CLI::error( 'facetwp_settings option not found or has no facets -- is FacetWP installed?' );
}

$changed = false;

foreach ( $settings['facets'] as &$facet ) {
	if ( 'business_genre' === ( $facet['name'] ?? '' ) ) {
		if ( ! empty( $facet['soft_limit'] ) ) {
			WP_CLI::log( "Clearing business_genre soft_limit (was '{$facet['soft_limit']}')." );
			$facet['soft_limit'] = '';
			$changed = true;
		} else {
			WP_CLI::log( 'business_genre soft_limit already empty, nothing to do.' );
		}
	}
}
unset( $facet );

if ( $changed ) {
	update_option( 'facetwp_settings', is_string( $raw ) ? wp_json_encode( $settings ) : $settings );
	WP_CLI::success( 'Saved.' );
} else {
	WP_CLI::success( 'No changes needed.' );
}
