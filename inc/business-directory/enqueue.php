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
			get_stylesheet_directory_uri() . '/inc/business-directory/assets/business-directory.css',
			[],
			defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false
		);

		wp_enqueue_script(
			'business-directory-facet-dropdown',
			get_stylesheet_directory_uri() . '/inc/business-directory/assets/business-directory-facet-dropdown.js',
			[],
			defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false,
			true
		);
	}

	if ( is_page_template( 'page-templates/page-business-directory.php' ) ) {
		wp_enqueue_script(
			'business-directory-banner-slider',
			get_stylesheet_directory_uri() . '/inc/business-directory/assets/business-directory-banner-slider.js',
			[],
			defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false,
			true
		);
	}

	if ( is_singular( 'business' ) ) {
		wp_enqueue_script(
			'business-gallery-lightbox',
			get_stylesheet_directory_uri() . '/inc/business-directory/assets/business-gallery-lightbox.js',
			[],
			defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false,
			true
		);
	}
} );
