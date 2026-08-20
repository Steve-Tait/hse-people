<?php
/**
 * Selecting more than one checkbox within the same Business Directory
 * facet (e.g. two categories, or two badges) matched nothing, because
 * FacetWP's checkboxes facet defaults to `operator: 'and'` -- a business
 * had to have ALL selected terms, not any of them.
 *
 * Switched to `operator: 'or'` so multiple selections within one facet
 * are treated as "match either", which is what a multi-select checkbox
 * filter is expected to do.
 *
 * `business_badge` is a code-registered facet (see
 * inc/business-directory/facetwp-facets.php) so it needed no DB change.
 * `business_genre` remains DB-managed via FacetWP's admin screen (the
 * site's pre-existing convention), so its `operator` is fixed here.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-20-facet-operator-or-fix.php --allow-root
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
		if ( 'or' !== ( $facet['operator'] ?? '' ) ) {
			WP_CLI::log( "Changing business_genre operator '" . ( $facet['operator'] ?? '(unset)' ) . "' -> 'or'." );
			$facet['operator'] = 'or';
			$changed = true;
		} else {
			WP_CLI::log( "business_genre operator already 'or', nothing to do." );
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
