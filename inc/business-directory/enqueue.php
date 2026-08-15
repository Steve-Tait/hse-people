<?php
/**
 * Styles for the Business/Supplier directory templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( is_singular( 'business' ) || is_tax( 'business_genre' ) || is_page_template( 'page-templates/page-business-directory.php' ) ) {
		wp_enqueue_style(
			'business-directory',
			get_template_directory_uri() . '/inc/business-directory/assets/business-directory.css',
			[],
			defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false
		);
	}
} );
