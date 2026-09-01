<?php
/**
 * Renames the `business_badge` taxonomy to `business_accreditation`
 * (label: "Accreditations"). The registration itself is just a code
 * change (see inc/business-directory/taxonomies.php), but the existing
 * terms/relationships in the database still say `business_badge` in
 * `wp_term_taxonomy.taxonomy` -- WordPress has no built-in "move this
 * term to a different taxonomy" API (wp_update_term() can't change it),
 * so that column needs a direct rename, otherwise the existing terms
 * stop resolving under the new taxonomy name entirely.
 *
 * Also removes the 'featured' term from this taxonomy -- "Featured" is
 * now a checkbox on the business post itself (see
 * inc/business-directory/featured.php), backed by WordPress's native
 * sticky-post mechanism (stick_post()/is_sticky()) rather than a term.
 * Any business currently tagged 'featured' is migrated to sticky before
 * that term is deleted, so no business silently loses Featured status.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-09-01-business-accreditation-rename.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

global $wpdb;

$old_taxonomy = 'business_badge';
$new_taxonomy = 'business_accreditation';

$still_old_tt_ids = $wpdb->get_col( $wpdb->prepare(
	"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
	$old_taxonomy
) );

if ( ! empty( $still_old_tt_ids ) ) {
	$term_ids = $wpdb->get_col(
		'SELECT term_id FROM ' . $wpdb->term_taxonomy . ' WHERE term_taxonomy_id IN (' . implode( ',', array_map( 'absint', $still_old_tt_ids ) ) . ')'
	);

	$wpdb->update( $wpdb->term_taxonomy, [ 'taxonomy' => $new_taxonomy ], [ 'taxonomy' => $old_taxonomy ] );

	// Clear cached term data so WP picks up the renamed taxonomy immediately.
	clean_term_cache( $term_ids, $old_taxonomy );
	clean_term_cache( $term_ids, $new_taxonomy );

	WP_CLI::log( 'Renamed taxonomy on ' . count( $still_old_tt_ids ) . ' term(s): business_badge -> business_accreditation.' );
} else {
	WP_CLI::log( 'No terms found under business_badge -- already renamed (or never existed).' );
}

// Migrate 'featured' term assignments to the sticky-post checkbox, then
// remove the term -- Featured is no longer a taxonomy term.
$featured_term = get_term_by( 'slug', 'featured', $new_taxonomy );

if ( $featured_term ) {
	$featured_posts = get_posts( [
		'post_type'      => 'business',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => [ [
			'taxonomy' => $new_taxonomy,
			'field'    => 'term_id',
			'terms'    => $featured_term->term_id,
		] ],
	] );

	foreach ( $featured_posts as $post_id ) {
		stick_post( $post_id );
		WP_CLI::log( "Migrated post $post_id to Featured (sticky)." );
	}

	wp_delete_term( $featured_term->term_id, $new_taxonomy );
	WP_CLI::log( "Removed the 'featured' term -- it's a post checkbox now, not a taxonomy term." );
} else {
	WP_CLI::log( "No 'featured' term found under $new_taxonomy -- already migrated (or never existed)." );
}

// FacetWP's index still has rows recorded under the old facet_name
// (business_badge) and may still reference the deleted 'featured' term
// -- rebuild so the renamed Accreditations facet reflects both changes.
// `launch => false` runs in-process, reusing this script's own DB
// connection/php.ini instead of spawning a subprocess that would need
// the same -c flag this script itself needed just to run.
WP_CLI::runcommand( 'facetwp index', [ 'launch' => false ] );

WP_CLI::success( 'Done.' );
