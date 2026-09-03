<?php
/**
 * Sets "Category Banner Images" (see genre-meta.php) on one
 * business_genre term -- Head Protection -- purely so the banner slider
 * built for /business-directory/category/{slug}/ has something real to
 * look at while testing, using the same 5 images already on the
 * homepage's own Elementor slider (page 5, widget f440d3e). Not meant as
 * permanent content -- swap or clear it once real category-specific
 * banner images are available.
 *
 * These attachment IDs (47105, 47360, 47409, 47682, 47700) are existing
 * media library items, not new uploads -- on production they're already
 * whole (the homepage slider uses them there), so this migration is
 * self-contained. On another *local* DB-only-synced environment without
 * the actual media files on disk, the images will 404 until the files
 * themselves are fetched (they were pulled from
 * https://www.hsepeople.com/wp-content/uploads/... onto this machine by
 * hand, matching each attachment's real `_wp_attached_file` path -- note
 * the `.webp` extension, not the original .png/.gif/.jpg some of these
 * were uploaded as; an image-optimisation plugin converted the actual
 * files in place without updating every reference to them).
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-09-03-category-banner-test-images.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$term_slug = 'head-protection';
$image_ids = [ 47105, 47360, 47409, 47682, 47700 ];

$term = get_term_by( 'slug', $term_slug, 'business_genre' );
if ( ! $term ) {
	WP_CLI::error( "Category '$term_slug' not found." );
}

$existing = get_term_meta( $term->term_id, 'banner_images', true );
$existing = is_array( $existing ) ? array_map( 'absint', $existing ) : [];

if ( $existing === $image_ids ) {
	WP_CLI::success( "banner_images on '{$term->name}' already set to the test images -- nothing to do." );
} else {
	update_term_meta( $term->term_id, 'banner_images', $image_ids );
	WP_CLI::success( "Set banner_images on '{$term->name}' to the 5 homepage slider images." );
}
