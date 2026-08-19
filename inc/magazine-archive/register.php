<?php
/**
 * Registers the HSE Magazine Archive Elementor widget, its assets, and the
 * AJAX endpoint that powers site-wide search (across all matching posts,
 * not just whichever page is currently loaded).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ASTRA_THEME_DIR . 'inc/magazine-archive/helpers.php';

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
	wp_localize_script( 'magazine-archive-script', 'hseMagazineSearch', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'hse_magazine_search' ),
	] );
} );

/**
 * Searches across ALL published posts in the given category (not just the
 * currently loaded page), capped generously rather than paginated -- a
 * magazine archive search realistically won't return hundreds of matches.
 */
function hse_magazine_search_handler() {
	check_ajax_referer( 'hse_magazine_search', 'nonce' );

	$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'e-magazines';

	if ( '' === $search ) {
		wp_send_json_error( [ 'message' => 'Empty search term' ] );
	}

	$query = new WP_Query( [
		'post_type' => 'post',
		'posts_per_page' => 100,
		'category_name' => $category,
		'post_status' => 'publish',
		's' => $search,
	] );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			hse_magazine_render_card();
		}
	}
	$html = ob_get_clean();
	wp_reset_postdata();

	wp_send_json_success( [
		'html' => $html,
		'count' => $query->found_posts,
	] );
}
add_action( 'wp_ajax_hse_magazine_search', 'hse_magazine_search_handler' );
add_action( 'wp_ajax_nopriv_hse_magazine_search', 'hse_magazine_search_handler' );
