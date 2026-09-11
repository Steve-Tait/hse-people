<?php
/**
 * HSE People Astra child theme: bootstraps every site-specific feature.
 * Astra (the parent) is loaded first automatically by WordPress and
 * supplies everything else -- header/footer, Customizer options, core
 * enqueues, etc. This file only ever adds to that, never replaces it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HSE_CHILD_DIR', trailingslashit( get_stylesheet_directory() ) );

/**
 * Business directory (supplier directory) feature.
 * business_genre taxonomy and the base "business" CPT remain managed via
 * CPT UI, matching the site's existing convention; these files register
 * the new pieces added on top of that in version-controlled code instead.
 */
require_once HSE_CHILD_DIR . 'inc/business-directory/helpers.php';
require_once HSE_CHILD_DIR . 'inc/business-directory/taxonomies.php';
require_once HSE_CHILD_DIR . 'inc/business-directory/meta-box.php';
require_once HSE_CHILD_DIR . 'inc/business-directory/genre-meta.php';
require_once HSE_CHILD_DIR . 'inc/business-directory/featured.php';
require_once HSE_CHILD_DIR . 'inc/business-directory/facetwp-facets.php';
require_once HSE_CHILD_DIR . 'inc/business-directory/enqueue.php';
require_once HSE_CHILD_DIR . 'inc/business-directory/rewrite.php';

/**
 * HSE Magazine Archive Elementor widget.
 * Migrated from an Angie code-snippet (originally database-stored, deployed
 * to disk by the Angie plugin) into theme code, now version-controlled and
 * independent of Angie, which has since been removed.
 */
require_once HSE_CHILD_DIR . 'inc/magazine-archive/register.php';
