<?php
/**
 * Business directory taxonomies: Accreditations, Tags, and Location.
 *
 * `business_genre` (the category-equivalent) remains managed via CPT UI's
 * admin screens, matching the site's pre-existing convention for that
 * taxonomy. These are new additions for the supplier directory feature
 * and are registered in code so they're version-controlled.
 *
 * `business_accreditation` briefly went by `business_badge` ("Business
 * Badges") early in this feature's own local development, before it was
 * renamed in code -- that name was never deployed anywhere, so there was
 * never any real content to migrate. It's hierarchical (accreditation
 * schemes grouped under their issuing body, e.g. BSiF -> Registered
 * Safety Supplier Scheme) -- see
 * migrations/2026-09-02-business-accreditation-hierarchy.php for the
 * term content, and hse_business_strip_parent_accreditations() below for
 * why a business can only ever end up tagged with a child term.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {

	register_taxonomy( 'business_accreditation', [ 'business' ], [
		'label'             => 'Accreditations',
		'labels'            => [
			'name'          => 'Accreditations',
			'singular_name' => 'Accreditation',
		],
		'public'            => true,
		'publicly_queryable' => true,
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => true,
		'rewrite'           => true,
	] );

	register_taxonomy( 'business_tag', [ 'business' ], [
		'label'             => 'Business Tags',
		'labels'            => [
			'name'          => 'Business Tags',
			'singular_name' => 'Business Tag',
		],
		'public'            => true,
		'publicly_queryable' => true,
		'hierarchical'      => false,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => true,
		'rewrite'           => true,
	] );

	register_taxonomy( 'location', [ 'business' ], [
		'label'             => 'Locations',
		'labels'            => [
			'name'          => 'Locations',
			'singular_name' => 'Location',
			'parent_item'   => 'Parent Location',
		],
		'public'            => true,
		'publicly_queryable' => true,
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => true,
		'rewrite'           => true,
	] );

} );

/**
 * Businesses may only be tagged with a specific accreditation scheme
 * (e.g. "Registered Safety Supplier Scheme (RSSS)"), never with the
 * issuing-body group it sits under (e.g. "BSiF") -- the parent terms
 * exist purely to organise the child terms in the admin checklist and
 * the Accreditations filter dropdown, not as accreditations in their
 * own right.
 *
 * The admin checkbox meta box also disables the top-level checkboxes
 * (see business-meta-box-admin.js) so this is a defence-in-depth check,
 * not the only guard -- it re-reads whatever ended up assigned after any
 * save (regardless of how: the checkbox meta box, quick edit, the REST
 * API, an import script) and strips out anything that isn't a child
 * term, so the "no parent tagging" rule holds regardless of the path
 * terms were assigned through.
 */
add_action( 'save_post_business', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$terms = get_the_terms( $post_id, 'business_accreditation' );
	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return;
	}

	$child_ids = [];
	$has_parent = false;

	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			$has_parent = true;
			continue;
		}
		$child_ids[] = $term->term_id;
	}

	if ( $has_parent ) {
		wp_set_object_terms( $post_id, $child_ids, 'business_accreditation', false );
	}
} );
