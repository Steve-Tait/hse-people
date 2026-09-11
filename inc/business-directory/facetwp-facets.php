<?php
/**
 * Business directory FacetWP facets, registered in code via FacetWP's
 * `facetwp_facets` filter instead of the database-stored settings option.
 *
 * `business_genre` (the category filter) remains database-managed via
 * FacetWP's admin screen, matching the site's pre-existing convention.
 * The Accreditations and Location facets that used to live here were
 * removed along with their dropdown filters on the directory page --
 * those taxonomies have no frontend presence at all now (see
 * taxonomies.php), so a facet for filtering by them serves nothing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'facetwp_facets', function ( $facets ) {

	$names = wp_list_pluck( $facets, 'name' );

	if ( ! in_array( 'business_search', $names, true ) ) {
		$facets[] = [
			'name'              => 'business_search',
			'label'             => 'Search',
			'type'              => 'search',
			'source'            => '',
			'search_engine'     => '',
			'auto_refresh'      => 'yes',
			'enable_relevance'  => 'checked',
		];
	}

	return $facets;

} );
