<?php
/**
 * Replaces the pre-loaded `business_genre` ("Category") terms -- the
 * original set was business-TYPE categories (Manufacturer, Consultants,
 * Recruitment Agencies, ...) inherited from the site's original build.
 * The new set is PPE product-TYPE categories instead, per direct request.
 *
 * All 8 demo businesses get cleared on go-live, so exact semantic fit
 * doesn't matter here -- assignments below favour demonstrating the
 * multi-category behaviour (several businesses get 2+ categories, e.g.
 * Apex Safety Solutions is Head Protection + Eye Protection +
 * Respiratory Protection, all at once) over strict accuracy. Two
 * businesses (CloudSafety Software, Elite HSE Recruitment) are left
 * uncategorised on purpose, so the "no category" state has real
 * coverage too.
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

$assignments = [
	47972 => [ 'Head Protection', 'Eye Protection', 'Respiratory Protection' ], // Apex Safety Solutions
	47975 => [ 'Protective Clothing', 'Hi-Vis Clothing', 'Hand Protection', 'Foot Protection' ], // ProTech PPE Supplies (broad-line PPE distributor)
	47974 => [ 'Face Fit Testing', 'Respiratory Protection' ], // SafeWork Training Academy (trains/certifies on these)
	47973 => [ 'Fall Protection', 'Protective Clothing' ], // Guardian Compliance Consultants (audits cover these)
	47978 => [ 'Hand Protection', 'Marine / Water Safety' ], // Independent HSE Advisors
	47979 => [ 'Hearing Protection', 'FR / Arc Flash / AS Clothing' ], // National Safety Federation
	// 47977 (CloudSafety Software) and 47976 (Elite HSE Recruitment) are
	// left with no category, on purpose -- see file docblock.
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
