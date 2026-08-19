<?php
/**
 * Registers the HSE Magazine Archive Elementor widget and its assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	require_once ASTRA_THEME_DIR . 'inc/magazine-archive/widget.php';
	$widgets_manager->register( new HSE_Magazine_Archive_Widget() );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style(
		'magazine-archive-style',
		get_template_directory_uri() . '/inc/magazine-archive/assets/magazine-archive.css',
		[],
		defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false
	);
	wp_register_script(
		'magazine-archive-script',
		get_template_directory_uri() . '/inc/magazine-archive/assets/magazine-archive.js',
		[ 'elementor-frontend' ],
		defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false,
		true
	);
} );
