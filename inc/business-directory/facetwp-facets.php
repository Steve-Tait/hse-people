<?php
/**
 * Business directory FacetWP facets, registered in code via FacetWP's
 * `facetwp_facets` filter instead of the database-stored settings option.
 *
 * `business_genre` (the category filter) remains database-managed via
 * FacetWP's admin screen, matching the site's pre-existing convention.
 * These two are new additions for the supplier directory feature.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'facetwp_facets', function ( $facets ) {

	$names = wp_list_pluck( $facets, 'name' );

	if ( ! in_array( 'business_badge', $names, true ) ) {
		$facets[] = [
			'name'            => 'business_badge',
			'label'           => 'Badges',
			'type'            => 'checkboxes',
			'source'          => 'tax/business_badge',
			'parent_term'     => '',
			'modifier_type'   => 'off',
			'modifier_values' => '',
			'hierarchical'    => 'no',
			'orderby'         => 'count',
			'count'           => '10',
			'source_other'    => '',
			'show_expanded'   => 'no',
			// 'yes': badges that would no longer match (0 results) once a
			// filter is applied stay visible but disabled/greyed out,
			// instead of disappearing -- so the full list is always there
			// to filter further, not just whatever's left after the first
			// selection.
			'ghosts'          => 'yes',
			'preserve_ghosts' => 'no',
			// 'or': a business matching ANY selected badge is included.
			// 'and' (FacetWP's default) required matching ALL of them,
			// which meant selecting two badges usually matched nothing.
			'operator'        => 'or',
			// Rendered inside a scrollable dropdown panel (see
			// template-parts/business/facet-dropdown.php), so all options
			// are output directly rather than behind FacetWP's own
			// "+ N more" soft-limit toggle.
			'soft_limit'      => '',
		];
	}

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
