<?php
/**
 * Replaces the pre-loaded `business_accreditation` ("Accreditations")
 * terms with a hierarchical set, grouped under the issuing body:
 *
 *   BSiF
 *     Registered Safety Supplier Scheme (RSSS)
 *     BSIF Safe Supply Accreditation (SSA)
 *     Fit2Fit RPE Fit Test Providers Accreditation Scheme
 *     First Responders to Liquid Spills accreditation
 *   US PPE
 *     NIOSH Approval
 *     ANSI/ISEA Standards
 *     ASTM Standards
 *     NFPA Certification/Standards
 *     OSHA compliance
 *     UL Certification
 *     CSA Certification
 *     AATCC Standards
 *     ANSI Z87.1
 *     ANSI/ISEA Z89.1
 *
 * The taxonomy itself became hierarchical in code (see taxonomies.php),
 * which also enforces -- server-side, regardless of how a term ends up
 * assigned -- that a business can only ever be tagged with a specific
 * scheme (a child term), never the issuing-body group itself. The old
 * flat terms (Verified, BSIF Affiliate Member, BSIF RSSS Member,
 * Accredited) are removed entirely, not kept alongside the new ones.
 *
 * All demo business posts get cleared on go-live, so reassignments below
 * favour demonstrating multi-accreditation and cross-issuing-body
 * tagging (e.g. Apex Safety Solutions gets a BSiF scheme *and* two US
 * PPE ones at once) over strict real-world accuracy. Two businesses are
 * left with no accreditation on purpose, and two of the fourteen new
 * child terms are left unused, so the "no accreditation" / zero-count
 * ghost states both still have real coverage.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-09-02-business-accreditation-hierarchy.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$old_terms = [
	'Verified',
	'BSIF Affiliate Member',
	'BSIF RSSS Member',
	'Accredited',
];

$new_terms = [
	'BSiF' => [
		'Registered Safety Supplier Scheme (RSSS)',
		'BSIF Safe Supply Accreditation (SSA)',
		'Fit2Fit RPE Fit Test Providers Accreditation Scheme',
		'First Responders to Liquid Spills accreditation',
	],
	'US PPE' => [
		'NIOSH Approval',
		'ANSI/ISEA Standards',
		'ASTM Standards',
		'NFPA Certification/Standards',
		'OSHA compliance',
		'UL Certification',
		'CSA Certification',
		'AATCC Standards',
		'ANSI Z87.1',
		'ANSI/ISEA Z89.1',
	],
];

$removed = 0;
foreach ( $old_terms as $name ) {
	$term = term_exists( $name, 'business_accreditation' );
	if ( $term ) {
		wp_delete_term( (int) $term['term_id'], 'business_accreditation' );
		WP_CLI::log( "Removed old accreditation: $name" );
		$removed++;
	}
}
WP_CLI::log( $removed ? "Removed $removed old accreditation(s)." : 'No old accreditations found -- already removed.' );

$term_ids = [];
$created = 0;

foreach ( $new_terms as $parent_name => $children ) {
	$parent_term = term_exists( $parent_name, 'business_accreditation' );
	if ( ! $parent_term ) {
		$parent_term = wp_insert_term( $parent_name, 'business_accreditation' );
		if ( is_wp_error( $parent_term ) ) {
			WP_CLI::error( "Failed to create accreditation '$parent_name': " . $parent_term->get_error_message() );
		}
		WP_CLI::log( "Created accreditation: $parent_name" );
		$created++;
	}
	$parent_id = (int) $parent_term['term_id'];
	$term_ids[ $parent_name ] = $parent_id;

	foreach ( $children as $child_name ) {
		$child_term = term_exists( $child_name, 'business_accreditation', $parent_id );
		if ( ! $child_term ) {
			$child_term = wp_insert_term( $child_name, 'business_accreditation', [ 'parent' => $parent_id ] );
			if ( is_wp_error( $child_term ) ) {
				WP_CLI::error( "Failed to create accreditation '$child_name': " . $child_term->get_error_message() );
			}
			WP_CLI::log( "Created accreditation: $child_name" );
			$created++;
		}
		$term_ids[ $child_name ] = (int) $child_term['term_id'];
	}
}
WP_CLI::log( $created ? "Created $created new accreditation(s)." : 'All new accreditations already exist.' );

$assignments = [
	47972 => [ 'ANSI Z87.1', 'NIOSH Approval', 'BSIF Safe Supply Accreditation (SSA)' ], // Apex Safety Solutions
	47973 => [ 'First Responders to Liquid Spills accreditation', 'OSHA compliance' ], // Guardian Compliance Consultants
	47974 => [ 'Fit2Fit RPE Fit Test Providers Accreditation Scheme' ], // SafeWork Training Academy
	47975 => [ 'Registered Safety Supplier Scheme (RSSS)', 'ANSI/ISEA Standards' ], // ProTech PPE Supplies
	47978 => [ 'UL Certification', 'ANSI/ISEA Z89.1' ], // Independent HSE Advisors
	47979 => [ 'ASTM Standards', 'NFPA Certification/Standards' ], // National Safety Federation
	// 47976 (Elite HSE Recruitment) and 47977 (CloudSafety Software) are
	// left with no accreditation, on purpose -- see file docblock.
];

$assigned = 0;
foreach ( $assignments as $post_id => $names ) {
	if ( 'business' !== get_post_type( $post_id ) ) {
		// Covers both "doesn't exist" and "exists but isn't one of our
		// demo businesses" -- these IDs are only meaningful on an
		// environment that ran the original demo-data migrations; on
		// production they could just as easily belong to a real,
		// unrelated post/page/attachment, which must never get
		// accreditation terms written onto it by ID coincidence.
		WP_CLI::warning( "Post $post_id is not a business post, skipping accreditation assignment." );
		continue;
	}

	$ids = array_map( function ( $name ) use ( $term_ids ) {
		return $term_ids[ $name ];
	}, $names );

	$current = wp_get_object_terms( $post_id, 'business_accreditation', [ 'fields' => 'ids' ] );
	if ( is_wp_error( $current ) ) {
		$current = [];
	}
	sort( $current );
	$sorted_ids = $ids;
	sort( $sorted_ids );

	if ( $current === $sorted_ids ) {
		continue;
	}

	wp_set_object_terms( $post_id, $ids, 'business_accreditation', false );
	WP_CLI::log( "Assigned post $post_id accreditations: " . implode( ', ', $names ) );
	$assigned++;
}
WP_CLI::log( $assigned ? "Updated accreditations on $assigned business post(s)." : 'Accreditation assignments already up to date.' );

// Term/assignment changes need a FacetWP index rebuild for the
// Accreditations facet's counts to be correct. `launch => false` runs
// in-process (reusing this script's own DB connection/php.ini) instead
// of spawning a subprocess that would miss the -c flag this script
// itself needed just to run.
WP_CLI::runcommand( 'facetwp index', [ 'launch' => false ] );

WP_CLI::success( 'Done.' );
