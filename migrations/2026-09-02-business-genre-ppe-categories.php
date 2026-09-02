<?php
/**
 * Replaces the pre-loaded `business_genre` ("Category") terms -- the
 * original set was business-TYPE categories (Manufacturer, Consultants,
 * Recruitment Agencies, ...) inherited from the site's original build.
 * The new set is PPE product-TYPE categories instead, per direct request.
 *
 * Of the 8 demo businesses, only two actually sell PPE products and
 * genuinely fit the new scheme -- reassigned below. The other six
 * (recruitment, training, software, consultancy, a membership body) are
 * professional-services businesses with no honest fit in a PPE-product
 * category list, so they're left uncategorised here rather than forced
 * into a category that doesn't describe them. They'll need either real
 * categories added by hand, or the category list revisited, once real
 * (non-PPE-product) suppliers are on the site.
 *
 * `business_genre` remains DB-managed via CPT UI (the site's pre-existing
 * convention for this taxonomy, unlike business_accreditation/location
 * which are code-registered) -- this migration only touches its term
 * *content*, not the taxonomy registration itself.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-09-02-business-genre-ppe-categories.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$old_terms = [
	'Consultancies',
	'Consultants',
	'General',
	'Manufacturer',
	'Personal Protective Equipment (PPE)',
	'Product Providers',
	'Professional Organisations',
	'Recruitment Agencies',
	'Software and App Providers',
	'Training Providers',
	'Virtual Business Card',
];

$new_terms = [
	'Head Protection',
	'Hearing Protection',
	'Eye Protection',
	'Face Fit Testing',
	'Respiratory Protection',
	'Hand Protection',
	'Hi-Vis Clothing',
	'FR / Arc Flash / AS Clothing',
	'Protective Clothing',
	'Foot Protection',
	'Marine / Water Safety',
	'Fall Protection',
];

$removed = 0;
foreach ( $old_terms as $name ) {
	$term = term_exists( $name, 'business_genre' );
	if ( $term ) {
		wp_delete_term( (int) $term['term_id'], 'business_genre' );
		WP_CLI::log( "Removed old category: $name" );
		$removed++;
	}
}
WP_CLI::log( $removed ? "Removed $removed old categor(y/ies)." : 'No old categories found -- already removed.' );

$term_ids = [];
$created = 0;
foreach ( $new_terms as $name ) {
	$term = term_exists( $name, 'business_genre' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'business_genre' );
		if ( is_wp_error( $term ) ) {
			WP_CLI::error( "Failed to create category '$name': " . $term->get_error_message() );
		}
		WP_CLI::log( "Created category: $name" );
		$created++;
	}
	$term_ids[ $name ] = (int) $term['term_id'];
}
WP_CLI::log( $created ? "Created $created new categor(y/ies)." : 'All new categories already exist.' );

// The only two demo businesses that genuinely sell PPE products, so the
// only two that honestly fit the new scheme.
$assignments = [
	47972 => [ 'Head Protection', 'Eye Protection', 'Respiratory Protection' ], // Apex Safety Solutions
	47975 => [ 'Protective Clothing', 'Hi-Vis Clothing' ], // ProTech PPE Supplies
];

$assigned = 0;
foreach ( $assignments as $post_id => $names ) {
	if ( ! get_post( $post_id ) ) {
		WP_CLI::warning( "Post $post_id not found, skipping category assignment." );
		continue;
	}

	$ids = array_map( function ( $name ) use ( $term_ids ) {
		return $term_ids[ $name ];
	}, $names );

	$current = wp_get_object_terms( $post_id, 'business_genre', [ 'fields' => 'ids' ] );
	if ( is_wp_error( $current ) ) {
		$current = [];
	}
	sort( $current );
	$sorted_ids = $ids;
	sort( $sorted_ids );

	if ( $current === $sorted_ids ) {
		continue;
	}

	wp_set_object_terms( $post_id, $ids, 'business_genre', false );
	WP_CLI::log( "Assigned post $post_id categories: " . implode( ', ', $names ) );
	$assigned++;
}
WP_CLI::log( $assigned ? "Updated categories on $assigned business post(s)." : 'Category assignments already up to date.' );

// Category assignments changed, so FacetWP's index needs rebuilding for
// the Category facet's counts to be correct. `launch => false` runs
// in-process (reusing this script's own DB connection/php.ini) instead
// of spawning a subprocess that would miss the -c flag this script
// itself needed just to run.
WP_CLI::runcommand( 'facetwp index', [ 'launch' => false ] );

WP_CLI::success( 'Done.' );
