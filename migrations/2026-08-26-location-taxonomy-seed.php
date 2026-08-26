<?php
/**
 * Seeds dummy `location` taxonomy terms (a new hierarchical taxonomy --
 * see inc/business-directory/taxonomies.php) and assigns a few of them to
 * the existing demo business posts, so the Location dropdown filter has
 * something real to show and test (partial/full parent states, ghost/
 * disabled leaves with no businesses, etc).
 *
 * The taxonomy and FacetWP facet *definitions* live in code and need no
 * migration step -- only this term/content data does.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-26-location-taxonomy-seed.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

/**
 * Parent locations, each with a list of child locations. A couple of
 * children per region are deliberately left with no business assigned,
 * so the "ghost" (disabled, zero-count) state has something to show too.
 */
$locations = [
	'London'      => [],
	'South East'  => [ 'Brighton', 'Reading', 'Oxford' ],
	'North West'  => [ 'Manchester', 'Liverpool' ],
	'Scotland'    => [ 'Edinburgh', 'Glasgow' ],
	'Midlands'    => [ 'Birmingham', 'Nottingham' ],
];

/**
 * business post ID => location term name to assign (a child term where
 * the location has one, so parent-fill/partial states are exercised).
 */
$assignments = [
	47972 => 'Manchester',    // Apex Safety Solutions        (North West)
	47973 => 'Brighton',      // Guardian Compliance Consultants (South East)
	47974 => 'Edinburgh',     // SafeWork Training Academy    (Scotland)
	47975 => 'Birmingham',    // ProTech PPE Supplies         (Midlands)
	47976 => 'London',        // Elite HSE Recruitment        (London)
	47977 => 'Liverpool',     // CloudSafety Software         (North West)
	47978 => 'Reading',       // Independent HSE Advisors     (South East)
	47979 => 'Glasgow',       // National Safety Federation   (Scotland)
];

$term_ids = [];
$created  = 0;

foreach ( $locations as $parent_name => $children ) {
	$parent_term = term_exists( $parent_name, 'location' );
	if ( ! $parent_term ) {
		$parent_term = wp_insert_term( $parent_name, 'location' );
		if ( is_wp_error( $parent_term ) ) {
			WP_CLI::error( "Failed to create location '$parent_name': " . $parent_term->get_error_message() );
		}
		$created++;
	}
	$parent_id = (int) $parent_term['term_id'];
	$term_ids[ $parent_name ] = $parent_id;

	foreach ( $children as $child_name ) {
		$child_term = term_exists( $child_name, 'location', $parent_id );
		if ( ! $child_term ) {
			$child_term = wp_insert_term( $child_name, 'location', [ 'parent' => $parent_id ] );
			if ( is_wp_error( $child_term ) ) {
				WP_CLI::error( "Failed to create location '$child_name': " . $child_term->get_error_message() );
			}
			$created++;
		}
		$term_ids[ $child_name ] = (int) $child_term['term_id'];
	}
}

WP_CLI::log( $created ? "Created $created new location term(s)." : 'All location terms already exist, nothing to create.' );

$assigned = 0;

foreach ( $assignments as $post_id => $location_name ) {
	if ( ! get_post( $post_id ) ) {
		WP_CLI::warning( "Post $post_id not found, skipping location assignment." );
		continue;
	}
	if ( ! isset( $term_ids[ $location_name ] ) ) {
		WP_CLI::warning( "Location '$location_name' not found, skipping assignment for post $post_id." );
		continue;
	}

	$current = wp_get_object_terms( $post_id, 'location', [ 'fields' => 'ids' ] );
	if ( is_wp_error( $current ) ) {
		$current = [];
	}

	if ( in_array( $term_ids[ $location_name ], $current, true ) ) {
		continue;
	}

	wp_set_object_terms( $post_id, [ $term_ids[ $location_name ] ], 'location', false );
	$assigned++;
}

WP_CLI::log( $assigned ? "Assigned a location to $assigned business post(s)." : 'All business posts already have their location assigned, nothing to do.' );

// FacetWP's index doesn't pick up new term assignments made outside the
// normal post-save flow (wp_set_object_terms here bypasses save_post),
// so it needs an explicit rebuild for the Location facet's counts to be
// correct. `launch => false` runs it in-process (reusing this same PHP
// process/DB connection) instead of spawning a new wp-cli subprocess,
// which would miss the -c php.ini flag this script itself needs to run
// at all and fail to connect to the database.
WP_CLI::runcommand( 'facetwp index', [ 'launch' => false ] );

WP_CLI::success( 'Done.' );
