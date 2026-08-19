<?php
/**
 * Migration: extend the pre-existing "Business" directory feature into the
 * full Supplier directory -- classic PHP templates, no Elementor.
 *
 * The taxonomy/ACF-field/FacetWP-facet *definitions* for this feature live
 * in code (see wp-content/themes/astra/inc/business-directory/) and need
 * no migration step -- they register themselves automatically once this
 * theme is deployed. The display templates (single-business.php,
 * taxonomy-business_genre.php, page-templates/page-business-directory.php)
 * are also code, auto-discovered by WordPress's template hierarchy.
 *
 * This script only handles what's fundamentally WordPress content/data,
 * not code:
 *
 *   - seeding the business_badge terms
 *   - clearing out the placeholder/test business posts, so real supplier
 *     content entry starts from a clean slate
 *   - removing the Elementor Theme Builder templates this feature used to
 *     rely on, and stripping Elementor from the directory page in favour
 *     of the new Page Template
 *   - the directory page's nav menu item
 *   - removing now-redundant database copies of anything that used to be
 *     stored in the DB before it moved into the code files above (safe to
 *     run even if those were never created on this environment)
 *
 * Assumes the baseline "business" feature already exists on the target site
 * (CPT `business`, taxonomy `business_genre`, page 1001 "Business
 * Directory") -- these came from the original site build, not from this
 * migration, and are expected to have the same post IDs on any environment
 * restored from the same production database.
 *
 * Usage (run once per environment, after deploying the theme code):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-14-business-directory-feature.php --allow-root
 *
 * Idempotent: safe to re-run; each step checks whether it already applied
 * before making changes.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

function bdf_log( $msg ) {
	WP_CLI::log( $msg );
}

// ---------------------------------------------------------------------
// 1. Seed business_badge terms (content, not code).
// ---------------------------------------------------------------------
function bdf_seed_badge_terms() {
	if ( ! taxonomy_exists( 'business_badge' ) ) {
		bdf_log( 'WARNING: business_badge taxonomy not registered -- deploy the theme code first.' );
		return;
	}
	foreach ( [ 'Featured', 'Verified', 'BSIF Affiliate Member', 'BSIF RSSS Member' ] as $term ) {
		if ( ! term_exists( $term, 'business_badge' ) ) {
			wp_insert_term( $term, 'business_badge' );
			bdf_log( "Created business_badge term: $term" );
		}
	}
}

// ---------------------------------------------------------------------
// 2. Delete the ORIGINAL placeholder/test business posts that came with
//    the site's initial "Business" feature build (and any media attached
//    directly to them), so real content entry starts clean.
//
//    Deliberately targets only this specific, known ID list rather than
//    "every business post" -- once this step has run, any business posts
//    added afterwards (demo content, real suppliers) must never be
//    touched by re-running this migration. Guarded with an option flag
//    as a second safeguard against ever re-deleting real content.
// ---------------------------------------------------------------------
function bdf_delete_placeholder_business_posts() {
	if ( get_option( 'bdf_placeholder_posts_cleared' ) ) {
		bdf_log( 'Original placeholder business posts already cleared, skipping.' );
		return;
	}

	$original_placeholder_ids = [ 43028, 26398, 1134, 1127, 1011, 1009, 1006 ];
	$deleted = 0;

	foreach ( $original_placeholder_ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'business' ) {
			continue;
		}
		$attachments = get_posts( [ 'post_type' => 'attachment', 'post_parent' => $post_id, 'numberposts' => -1, 'fields' => 'ids' ] );
		foreach ( $attachments as $att_id ) {
			wp_delete_attachment( $att_id, true );
		}
		wp_delete_post( $post_id, true );
		$deleted++;
	}

	update_option( 'bdf_placeholder_posts_cleared', 1 );
	bdf_log( "Deleted $deleted original placeholder business post(s)." );
}

// ---------------------------------------------------------------------
// 3. Fix the sitewide Search Archive template (1144, condition
//    include/archive/search -- renders on WordPress's search results
//    page, not specific to suppliers at all) BEFORE deleting the loop
//    card below: it displays each result using a "Custom" skin pointing
//    at template 1016. Once 1016 is deleted, that reference would go
//    dangling and every search result would render empty. Switch it to
//    Elementor's built-in "Classic" skin instead, which needs no
//    external template and works for any post type.
// ---------------------------------------------------------------------
function bdf_fix_search_archive_skin() {
	$post_id = 1144;
	if ( ! get_post( $post_id ) ) {
		bdf_log( "Search Archive template ($post_id) not found, skipping." );
		return;
	}
	$raw = get_post_meta( $post_id, '_elementor_data', true );
	if ( strpos( $raw, '"archive_custom_skin_template":"1016"' ) === false ) {
		bdf_log( 'Search Archive template already fixed or does not reference the loop card, skipping.' );
		return;
	}

	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		bdf_log( "WARNING: could not decode _elementor_data for $post_id" );
		return;
	}

	$fix = function ( &$els ) use ( &$fix ) {
		foreach ( $els as &$el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'archive-posts' && ( $el['settings']['_skin'] ?? '' ) === 'archive_custom' ) {
				$el['settings']['_skin'] = 'archive_classic';
				unset( $el['settings']['archive_custom_skin_template'] );
			}
			if ( ! empty( $el['elements'] ) ) {
				$fix( $el['elements'] );
			}
		}
	};
	$fix( $data );

	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $post_id, '_elementor_css' );
	delete_post_meta( $post_id, '_elementor_element_cache_unique_id' );
	bdf_log( 'Fixed Search Archive template: switched from the (about to be deleted) custom loop skin to Classic.' );
}

// ---------------------------------------------------------------------
// 4. Remove the Elementor Theme Builder templates this feature used to
//    rely on -- Elementor is no longer involved in this section.
// ---------------------------------------------------------------------
function bdf_delete_elementor_templates() {
	foreach ( [ 1050 => 'single template', 1016 => 'loop card', 1162 => 'archive template' ] as $id => $label ) {
		$post = get_post( $id );
		if ( $post && $post->post_type === 'elementor_library' ) {
			wp_delete_post( $id, true );
			bdf_log( "Deleted Elementor $label ($id)." );
		} else {
			bdf_log( "Elementor $label ($id) not found or already removed, skipping." );
		}
	}
}

// ---------------------------------------------------------------------
// 5. Strip Elementor from the directory page and assign the new,
//    code-based Page Template instead.
// ---------------------------------------------------------------------
function bdf_convert_directory_page() {
	$post_id = 1001;
	if ( ! get_post( $post_id ) ) {
		bdf_log( "WARNING: page $post_id not found, skipping directory page conversion." );
		return;
	}
	$current_template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $current_template === 'page-templates/page-business-directory.php' ) {
		bdf_log( 'Directory page already converted, skipping.' );
		return;
	}

	foreach ( [ '_elementor_data', '_elementor_edit_mode', '_elementor_template_type', '_elementor_version', '_elementor_page_settings', '_elementor_css', '_elementor_element_cache_unique_id' ] as $key ) {
		delete_post_meta( $post_id, $key );
	}
	update_post_meta( $post_id, '_wp_page_template', 'page-templates/page-business-directory.php' );

	bdf_log( 'Converted directory page 1001 to the new Page Template.' );
}

// ---------------------------------------------------------------------
// 6. Nav menu: add the directory page if not already present.
// ---------------------------------------------------------------------
function bdf_add_nav_menu_item() {
	$menu = wp_get_nav_menu_object( 'Primary Menu' );
	if ( ! $menu ) {
		bdf_log( 'WARNING: "Primary Menu" not found, skipping nav menu item.' );
		return;
	}
	$items = wp_get_nav_menu_items( $menu->term_id );
	foreach ( $items as $item ) {
		if ( (int) $item->object_id === 1001 && $item->object === 'page' ) {
			bdf_log( 'Nav menu item already present, skipping.' );
			return;
		}
	}
	wp_update_nav_menu_item( $menu->term_id, 0, [
		'menu-item-title' => 'Business Directory',
		'menu-item-object-id' => 1001,
		'menu-item-object' => 'page',
		'menu-item-type' => 'post_type',
		'menu-item-status' => 'publish',
		'menu-item-position' => 8,
	] );
	bdf_log( 'Added Business Directory to Primary Menu.' );
}

// ---------------------------------------------------------------------
// 7. Remove now-redundant database copies of anything that moved into
//    code (harmless no-op if this environment never had them).
// ---------------------------------------------------------------------
function bdf_remove_redundant_db_copies() {
	$old_group = get_posts( [
		'post_type'   => 'acf-field-group',
		'name'        => 'group_5f1e76dad85cd',
		'numberposts' => 1,
	] );
	if ( $old_group ) {
		$children = get_children( [ 'post_parent' => $old_group[0]->ID, 'post_type' => 'acf-field', 'numberposts' => -1 ] );
		foreach ( $children as $c ) {
			wp_delete_post( $c->ID, true );
		}
		wp_delete_post( $old_group[0]->ID, true );
		bdf_log( 'Removed redundant DB-based ACF field group (now code-based).' );
	}

	$taxes = get_option( 'cptui_taxonomies', [] );
	if ( isset( $taxes['business_badge'] ) || isset( $taxes['business_tag'] ) ) {
		unset( $taxes['business_badge'], $taxes['business_tag'] );
		update_option( 'cptui_taxonomies', $taxes );
		bdf_log( 'Removed redundant CPT UI taxonomy entries (now code-based).' );
	}

	$settings = json_decode( get_option( 'facetwp_settings' ), true );
	if ( is_array( $settings ) && ! empty( $settings['facets'] ) ) {
		$names_before = wp_list_pluck( $settings['facets'], 'name' );
		if ( in_array( 'business_badge', $names_before, true ) || in_array( 'business_search', $names_before, true ) ) {
			$settings['facets'] = array_values( array_filter( $settings['facets'], function ( $f ) {
				return ! in_array( $f['name'], [ 'business_badge', 'business_search' ], true );
			} ) );
			update_option( 'facetwp_settings', wp_json_encode( $settings ) );
			bdf_log( 'Removed redundant FacetWP facet entries (now code-based).' );
		}
	}
}

// ---------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------
bdf_seed_badge_terms();
bdf_delete_placeholder_business_posts();
bdf_fix_search_archive_skin();
bdf_delete_elementor_templates();
bdf_convert_directory_page();
bdf_add_nav_menu_item();
bdf_remove_redundant_db_copies();

flush_rewrite_rules();

bdf_log( 'Migration complete.' );
