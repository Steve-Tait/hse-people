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
// 2. Delete placeholder/test business posts (and any media attached
//    directly to them), so real content entry starts clean.
// ---------------------------------------------------------------------
function bdf_delete_placeholder_business_posts() {
	$posts = get_posts( [ 'post_type' => 'business', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] );
	if ( empty( $posts ) ) {
		bdf_log( 'No business posts present, skipping.' );
		return;
	}
	foreach ( $posts as $post_id ) {
		$attachments = get_posts( [ 'post_type' => 'attachment', 'post_parent' => $post_id, 'numberposts' => -1, 'fields' => 'ids' ] );
		foreach ( $attachments as $att_id ) {
			wp_delete_attachment( $att_id, true );
		}
		wp_delete_post( $post_id, true );
	}
	bdf_log( 'Deleted ' . count( $posts ) . ' placeholder business post(s).' );
}

// ---------------------------------------------------------------------
// 3. Remove the Elementor Theme Builder templates this feature used to
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
// 4. Strip Elementor from the directory page and assign the new,
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
// 5. Nav menu: add the directory page if not already present.
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
// 6. Remove now-redundant database copies of anything that moved into
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
bdf_delete_elementor_templates();
bdf_convert_directory_page();
bdf_add_nav_menu_item();
bdf_remove_redundant_db_copies();

flush_rewrite_rules();

bdf_log( 'Migration complete.' );
