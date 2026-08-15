<?php
/**
 * Migration: extend the pre-existing "Business" directory feature into the
 * full Supplier directory.
 *
 * The taxonomy/ACF-field/FacetWP-facet *definitions* for this feature live
 * in code (see wp-content/themes/astra/inc/business-directory/) and need
 * no migration step -- they register themselves automatically once this
 * theme is deployed. This script only handles the pieces that are
 * fundamentally WordPress content/data, not code:
 *
 *   - seeding the business_badge terms
 *   - the actual Elementor template designs (single/loop/archive/directory
 *     page), which are stored as JSON and have no code equivalent
 *   - the directory page's nav menu item
 *   - removing now-redundant database copies of anything that used to be
 *     stored in the DB before it moved into the code files above (safe to
 *     run even if those were never created on this environment)
 *
 * Assumes the baseline "business" feature already exists on the target site
 * (CPT `business`, taxonomy `business_genre`, Elementor templates
 * 1050/1016/1162, page 1001 "Business Directory") -- these came from the
 * original site build, not from this migration, and are expected to have
 * the same post IDs on any environment restored from the same production
 * database.
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

function bdf_gen_id() {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
}

function bdf_acf_tag( $id, $name, $settings ) {
	return '[elementor-tag id="' . $id . '" name="' . $name . '" settings="' . rawurlencode( wp_json_encode( $settings ) ) . '"]';
}

function bdf_clear_elementor_cache( array $post_ids ) {
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::instance()->files_manager->clear_cache();
	}
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'elementor_atomic_cache_validity__%'" );
	foreach ( $post_ids as $id ) {
		delete_post_meta( $id, '_elementor_css' );
		delete_post_meta( $id, '_elementor_element_cache_unique_id' );
	}
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
// 2. Remove now-redundant database copies of anything that moved into
//    code (harmless no-op if this environment never had them).
// ---------------------------------------------------------------------
function bdf_remove_redundant_db_copies() {
	// Old DB-based ACF field group, found by its original key rather than
	// a hardcoded post ID, in case IDs differ on another environment.
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
// 3. Elementor single template (1050): cover image, logo, badges row,
//    independent review/demo video visibility.
// ---------------------------------------------------------------------
function bdf_update_single_template() {
	$post_id = 1050;
	if ( ! get_post( $post_id ) ) {
		bdf_log( "WARNING: template $post_id not found, skipping single template update." );
		return;
	}
	$raw = get_post_meta( $post_id, '_elementor_data', true );
	if ( strpos( $raw, 'Demonstration Video' ) !== false ) {
		bdf_log( 'Single template already updated, skipping.' );
		return;
	}

	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		bdf_log( "WARNING: could not decode _elementor_data for $post_id" );
		return;
	}

	// Cover image
	$data[0]['settings']['__dynamic__']['background_image'] = bdf_acf_tag( bdf_gen_id(), 'acf-image', [ 'key' => 'field_4b6768ef1b45:cover_image' ] );

	// Logo into the empty first column of the title row
	$data[1]['elements'][0]['elements'][] = [
		'id' => bdf_gen_id(), 'elType' => 'widget',
		'settings' => [
			'image' => [ 'url' => '', 'id' => '' ],
			'__dynamic__' => [ 'image' => bdf_acf_tag( bdf_gen_id(), 'acf-image', [ 'key' => 'field_7d33c72367b4:logo' ] ) ],
			'image_size' => 'medium', 'align' => 'center',
		],
		'elements' => [], 'widgetType' => 'image',
	];

	// Videos: rename heading, apply independent per-field visibility, clone for demo video
	$map_videos = function ( $els ) use ( &$map_videos ) {
		$out = [];
		foreach ( $els as $el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'heading' && ( $el['settings']['title'] ?? '' ) === 'Videos' ) {
				$el['settings']['title'] = 'Review Video';
			}
			if ( ( $el['widgetType'] ?? '' ) === 'heading' && in_array( $el['settings']['title'] ?? '', [ 'Review Video', 'Demonstration Video' ], true ) ) {
				$field_key = ( $el['settings']['title'] === 'Review Video' ) ? 'field_5f1ebb4c95cc1:video' : 'field_b0a5e9148813:demonstration_video';
				$el['settings']['dynamicconditions_visibility'] = 'show';
				$el['settings']['dynamicconditions_condition'] = 'not_empty';
				$el['settings']['__dynamic__']['dynamicconditions_dynamic'] = bdf_acf_tag( bdf_gen_id(), 'acf-text', [ 'key' => $field_key ] );
			}
			if ( ( $el['widgetType'] ?? '' ) === 'video' ) {
				$el['settings']['video_type'] = 'youtube';
				$el['settings']['__dynamic__']['youtube_url'] = bdf_acf_tag( bdf_gen_id(), 'acf-text', [ 'key' => 'field_5f1ebb4c95cc1:video' ] );
				$el['settings']['dynamicconditions_visibility'] = 'show';
				$el['settings']['dynamicconditions_condition'] = 'not_empty';
				$el['settings']['__dynamic__']['dynamicconditions_dynamic'] = bdf_acf_tag( bdf_gen_id(), 'acf-text', [ 'key' => 'field_5f1ebb4c95cc1:video' ] );
				$out[] = $el;

				$demo_heading = [
					'id' => bdf_gen_id(), 'elType' => 'widget',
					'settings' => [
						'title' => 'Demonstration Video', 'header_size' => 'h4',
						'dynamicconditions_visibility' => 'show', 'dynamicconditions_condition' => 'not_empty',
						'__dynamic__' => [ 'dynamicconditions_dynamic' => bdf_acf_tag( bdf_gen_id(), 'acf-text', [ 'key' => 'field_b0a5e9148813:demonstration_video' ] ) ],
					],
					'elements' => [], 'widgetType' => 'heading',
				];
				$demo_divider = [ 'id' => bdf_gen_id(), 'elType' => 'widget', 'settings' => [], 'elements' => [], 'widgetType' => 'divider' ];
				$demo_video = $el;
				$demo_video['id'] = bdf_gen_id();
				$demo_video['settings']['__dynamic__']['youtube_url'] = bdf_acf_tag( bdf_gen_id(), 'acf-text', [ 'key' => 'field_b0a5e9148813:demonstration_video' ] );
				$demo_video['settings']['__dynamic__']['dynamicconditions_dynamic'] = bdf_acf_tag( bdf_gen_id(), 'acf-text', [ 'key' => 'field_b0a5e9148813:demonstration_video' ] );

				$out[] = $demo_heading;
				$out[] = $demo_divider;
				$out[] = $demo_video;
				continue;
			}
			if ( ! empty( $el['elements'] ) ) {
				$el['elements'] = $map_videos( $el['elements'] );
			}
			$out[] = $el;
		}
		return $out;
	};
	$data = $map_videos( $data );

	// Remove the blanket not_empty condition from the section wrapping the videos column
	$strip_section_condition = function ( &$els ) use ( &$strip_section_condition ) {
		foreach ( $els as &$el ) {
			if ( ! empty( $el['elements'] ) ) {
				foreach ( $el['elements'] as $col ) {
					foreach ( ( $col['elements'] ?? [] ) as $w ) {
						if ( ( $w['widgetType'] ?? '' ) === 'heading' && ( $w['settings']['title'] ?? '' ) === 'Review Video' ) {
							$el['settings']['dynamicconditions_condition'] = null;
							$el['settings']['__dynamic__'] = [];
						}
					}
				}
				$strip_section_condition( $el['elements'] );
			}
		}
	};
	$strip_section_condition( $data );

	// Badges row: clone the "Zip Code:" row
	$is_label_row = function ( $el, $label ) {
		if ( ( $el['elType'] ?? '' ) !== 'section' ) return false;
		$col0 = $el['elements'][0] ?? null;
		if ( ! $col0 || empty( $col0['elements'] ) ) return false;
		$w = $col0['elements'][0];
		return ( $w['widgetType'] ?? '' ) === 'heading' && ( $w['settings']['title'] ?? '' ) === $label;
	};
	$build_badge_row = function ( $zip_row ) {
		$row = $zip_row;
		$row['id'] = bdf_gen_id();
		$row['settings']['dynamicconditions_condition'] = null;
		$row['settings']['__dynamic__'] = [];
		$row['elements'][0]['id'] = bdf_gen_id();
		$row['elements'][0]['elements'][0]['id'] = bdf_gen_id();
		$row['elements'][0]['elements'][0]['settings']['title'] = 'Badges:';
		$row['elements'][0]['elements'][0]['settings']['__dynamic__'] = [];
		$row['elements'][1]['id'] = bdf_gen_id();
		$row['elements'][1]['elements'][0]['id'] = bdf_gen_id();
		$row['elements'][1]['elements'][0]['settings']['title'] = '';
		$row['elements'][1]['elements'][0]['settings']['__dynamic__'] = [
			'title' => bdf_acf_tag( bdf_gen_id(), 'post-terms', [ 'taxonomy' => 'business_badge', 'separator' => ', ' ] ),
		];
		unset( $row['elements'][1]['elements'][0]['settings']['link'] );
		return $row;
	};
	$badges_inserted = false;
	$map_badges = function ( $els ) use ( &$map_badges, &$badges_inserted, $is_label_row, $build_badge_row ) {
		$out = [];
		foreach ( $els as $el ) {
			$out[] = $el;
			if ( ! $badges_inserted && $is_label_row( $el, 'Zip Code:' ) ) {
				$out[] = $build_badge_row( $el );
				$badges_inserted = true;
				continue;
			}
			if ( ! empty( $el['elements'] ) ) {
				$new_children = $map_badges( $el['elements'] );
				$out[ count( $out ) - 1 ]['elements'] = $new_children;
			}
		}
		return $out;
	};
	$data = $map_badges( $data );

	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	bdf_log( 'Updated single template 1050.' );
}

// ---------------------------------------------------------------------
// 4. Loop card (1016): logo thumbnail + badge indicator.
// ---------------------------------------------------------------------
function bdf_update_loop_card() {
	$post_id = 1016;
	if ( ! get_post( $post_id ) ) {
		bdf_log( "WARNING: template $post_id not found, skipping loop card update." );
		return;
	}
	$raw = get_post_meta( $post_id, '_elementor_data', true );
	if ( strpos( $raw, 'business_badge' ) !== false ) {
		bdf_log( 'Loop card already updated, skipping.' );
		return;
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		bdf_log( "WARNING: could not decode _elementor_data for $post_id" );
		return;
	}

	$logo_widget = [
		'id' => bdf_gen_id(), 'elType' => 'widget',
		'settings' => [
			'image' => [ 'url' => '', 'id' => '' ],
			'__dynamic__' => [ 'image' => bdf_acf_tag( bdf_gen_id(), 'acf-image', [ 'key' => 'field_7d33c72367b4:logo' ] ) ],
			'image_size' => 'thumbnail', 'align' => 'left', '_element_width' => 'initial',
		],
		'elements' => [], 'widgetType' => 'image',
	];
	$badges_heading = [
		'id' => bdf_gen_id(), 'elType' => 'widget',
		'settings' => [
			'title' => '', 'header_size' => 'p', 'align' => 'left',
			'__dynamic__' => [
				'title' => bdf_acf_tag( bdf_gen_id(), 'post-terms', [ 'taxonomy' => 'business_badge', 'separator' => ' &middot; ' ] ),
				'dynamicconditions_dynamic' => bdf_acf_tag( bdf_gen_id(), 'post-terms', [ 'taxonomy' => 'business_badge', 'separator' => ' &middot; ' ] ),
			],
			'dynamicconditions_visibility' => 'show', 'dynamicconditions_condition' => 'not_empty',
		],
		'elements' => [], 'widgetType' => 'heading',
	];

	$col = &$data[0]['elements'][0];
	$new_elements = [];
	foreach ( $col['elements'] as $el ) {
		$new_elements[] = $el;
		if ( ( $el['widgetType'] ?? '' ) === 'image' ) {
			$new_elements[] = $logo_widget;
		}
		if ( ( $el['elType'] ?? '' ) === 'widget' && ( $el['widgetType'] ?? '' ) === 'divider' ) {
			$new_elements[] = $badges_heading;
		}
	}
	$col['elements'] = $new_elements;
	unset( $col );

	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	bdf_log( 'Updated loop card 1016.' );
}

// ---------------------------------------------------------------------
// 5. Archive template (1162): tighten display condition.
// ---------------------------------------------------------------------
function bdf_tighten_archive_condition() {
	$post_id = 1162;
	if ( ! get_post( $post_id ) ) {
		bdf_log( "WARNING: template $post_id not found, skipping archive condition update." );
		return;
	}
	$current = get_post_meta( $post_id, '_elementor_conditions', true );
	$target = [ 'include/archive/business_archive/business_genre' ];
	if ( $current === $target ) {
		bdf_log( 'Archive condition already tightened, skipping.' );
		return;
	}
	update_post_meta( $post_id, '_elementor_conditions', $target );
	bdf_log( 'Tightened archive template 1162 condition to business_genre taxonomy archives only.' );
}

// ---------------------------------------------------------------------
// 6. Directory page (1001): real search facet + badge filter tab.
// ---------------------------------------------------------------------
function bdf_update_directory_page() {
	$post_id = 1001;
	if ( ! get_post( $post_id ) ) {
		bdf_log( "WARNING: page $post_id not found, skipping directory page update." );
		return;
	}
	$raw = get_post_meta( $post_id, '_elementor_data', true );
	if ( strpos( $raw, 'business_search' ) !== false && strpos( $raw, 'Filter by Badge' ) !== false ) {
		bdf_log( 'Directory page already updated, skipping.' );
		return;
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		bdf_log( "WARNING: could not decode _elementor_data for $post_id" );
		return;
	}

	$replace_search = function ( &$els ) use ( &$replace_search ) {
		foreach ( $els as &$el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'search-form' ) {
				$el['widgetType'] = 'shortcode';
				$el['settings'] = [ 'shortcode' => '[facetwp facet="business_search"]' ];
			}
			if ( ! empty( $el['elements'] ) ) $replace_search( $el['elements'] );
		}
	};
	$replace_search( $data );

	$add_badge_tab = function ( &$els ) use ( &$add_badge_tab ) {
		foreach ( $els as &$el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'toggle' ) {
				$tabs = $el['settings']['tabs'] ?? [];
				foreach ( $tabs as $t ) {
					if ( ( $t['tab_title'] ?? '' ) === 'Filter by Badge' ) return true;
				}
				$tabs[] = [ 'tab_title' => 'Filter by Badge', 'tab_content' => '[facetwp facet="business_badge"]', '_id' => bdf_gen_id() ];
				$el['settings']['tabs'] = $tabs;
				return true;
			}
			if ( ! empty( $el['elements'] ) ) {
				if ( $add_badge_tab( $el['elements'] ) ) return true;
			}
		}
		return false;
	};
	$add_badge_tab( $data );

	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	bdf_log( 'Updated directory page 1001.' );
}

// ---------------------------------------------------------------------
// 7. Nav menu: add the directory page if not already present.
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
// Run
// ---------------------------------------------------------------------
bdf_seed_badge_terms();
bdf_remove_redundant_db_copies();
bdf_update_single_template();
bdf_update_loop_card();
bdf_tighten_archive_condition();
bdf_update_directory_page();
bdf_add_nav_menu_item();
bdf_clear_elementor_cache( [ 1050, 1016, 1162, 1001 ] );

bdf_log( 'Migration complete.' );
