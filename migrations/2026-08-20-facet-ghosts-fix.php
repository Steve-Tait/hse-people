<?php
/**
 * Once a Business Directory filter narrowed the results, checkboxes for
 * terms that no longer matched anything (0 results) disappeared from
 * their facet's list entirely -- FacetWP's default checkboxes behaviour.
 * That made it look like the rest of the options had vanished instead of
 * still being there to combine with.
 *
 * Switched to FacetWP's `ghosts: 'yes'`, which keeps zero-result terms
 * visible (shown disabled/greyed out, per FacetWP's own front.css) rather
 * than removing them, so the full list is always available.
 *
 * `business_badge` is a code-registered facet (see
 * inc/business-directory/facetwp-facets.php) so it needed no DB change.
 * `business_genre` remains DB-managed via FacetWP's admin screen (the
 * site's pre-existing convention), so its `ghosts` setting is fixed here.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-20-facet-ghosts-fix.php --allow-root
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
		if ( 'yes' !== ( $facet['ghosts'] ?? '' ) ) {
			WP_CLI::log( "Changing business_genre ghosts '" . ( $facet['ghosts'] ?? '(unset)' ) . "' -> 'yes'." );
			$facet['ghosts'] = 'yes';
			$changed = true;
		} else {
			WP_CLI::log( "business_genre ghosts already 'yes', nothing to do." );
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
