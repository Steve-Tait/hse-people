<?php
/**
 * /{directory-page}/category/{term-slug}/ -- a category-locked view of
 * the directory page: same template, but pre-filtered to one
 * business_genre term with the category facet hidden (since it's fixed
 * by the URL).
 *
 * The directory page isn't a fixed page/URL -- "Business Directory" is
 * a normal, selectable Page Template (page-templates/page-business-
 * directory.php), so this resolves whichever published page currently
 * has it assigned, rather than hardcoding a slug. get_page_uri() (not
 * post_name) so a nested page ("about/business-directory") still gets a
 * correct pagename match, matching how WordPress's own `pagename` query
 * var expects the full hierarchical path.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The ID of whichever published page has the Business Directory template
 * assigned, or 0 if none do. Cached in a transient -- this runs on every
 * front-end request (to register the rewrite rule below), so it's worth
 * avoiding a fresh query each time; invalidated whenever any page is
 * saved, which covers the template being assigned/moved/removed.
 */
function hse_get_business_directory_page_id() {
	$cached = get_transient( 'hse_business_directory_page_id' );
	if ( false !== $cached ) {
		return (int) $cached;
	}

	$pages = get_posts( [
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-templates/page-business-directory.php',
	] );

	$page_id = $pages ? (int) $pages[0] : 0;
	set_transient( 'hse_business_directory_page_id', $page_id, DAY_IN_SECONDS );

	return $page_id;
}

add_action( 'save_post_page', function () {
	delete_transient( 'hse_business_directory_page_id' );
} );

/**
 * The directory page's own base URL, for building links to it (and its
 * category sub-pages) from elsewhere in the theme -- e.g. the category
 * pills on single-business.php. Falls back to the pre-dynamic-lookup
 * default ('/business-directory/') if no page currently has the
 * template assigned, so a link still points somewhere sensible rather
 * than silently breaking.
 */
function hse_get_business_directory_url() {
	$page_id = hse_get_business_directory_page_id();
	return $page_id ? trailingslashit( get_permalink( $page_id ) ) : home_url( '/business-directory/' );
}

add_action( 'init', function () {
	$page_id = hse_get_business_directory_page_id();
	if ( ! $page_id ) {
		return;
	}

	$page_path = get_page_uri( $page_id );
	if ( ! $page_path ) {
		return;
	}

	add_rewrite_rule(
		'^' . preg_quote( $page_path, '/' ) . '/category/([^/]+)/?$',
		'index.php?pagename=' . $page_path . '&business_genre=$matches[1]',
		'top'
	);
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'business_genre';
	return $vars;
} );
