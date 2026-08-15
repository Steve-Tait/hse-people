<?php
/**
 * Business directory taxonomies: Badges and Tags.
 *
 * `business_genre` (the category-equivalent) remains managed via CPT UI's
 * admin screens, matching the site's pre-existing convention for that
 * taxonomy. These two are new additions for the supplier directory feature
 * and are registered in code so they're version-controlled.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {

	register_taxonomy( 'business_badge', [ 'business' ], [
		'label'             => 'Business Badges',
		'labels'            => [
			'name'          => 'Business Badges',
			'singular_name' => 'Business Badge',
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

} );
