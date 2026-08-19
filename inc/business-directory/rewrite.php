<?php
/**
 * /business-directory/category/{slug}/ -- a category-locked view of the
 * directory page: same template, but pre-filtered to one business_genre
 * term with the category facet hidden (since it's fixed by the URL).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	add_rewrite_rule(
		'^business-directory/category/([^/]+)/?$',
		'index.php?pagename=business-directory&business_genre=$matches[1]',
		'top'
	);
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'business_genre';
	return $vars;
} );
