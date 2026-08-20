<?php
/**
 * Fix: header appears "doubled up" on mobile.
 *
 * The Default Header template (26245) has two responsive pairs of
 * sections: a desktop-only header row (sections 08fa3fe + 2ab4573,
 * hidden below 1025px) and a mobile/tablet header row (sections 3eb7b86
 * + 54d9ba8, hidden at/above 1025px via .elementor-hidden-desktop) --
 * each pair correctly shows only at its intended widths.
 *
 * However, section 54d9ba8 (the mobile/tablet row, containing its own
 * logo + nav menu) had Elementor's "Sticky" effect enabled with no
 * device restriction, while its desktop siblings (08fa3fe, 2ab4573)
 * were correctly restricted to `sticky_on: ["desktop"]`. Elementor Pro's
 * sticky module defaults `sticky_on` to ALL active devices when unset --
 * so this section's sticky behaviour was activating on mobile too,
 * something it was clearly never meant to have given its siblings were
 * deliberately desktop-only. That's what caused the header to visually
 * double up specifically on mobile/tablet.
 *
 * Fix: remove the sticky setting entirely from 54d9ba8 -- this section
 * is only ever visible on mobile/tablet in the first place, so unlike
 * its desktop siblings, it never needed sticky behaviour to begin with.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-20-mobile-header-sticky-fix.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$post_id = 26245;
if ( ! get_post( $post_id ) ) {
	WP_CLI::error( "Default Header template ($post_id) not found." );
}

$data = json_decode( get_post_meta( $post_id, '_elementor_data', true ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( "Could not decode _elementor_data for $post_id" );
}

$fixed = false;
$fix = function ( &$els ) use ( &$fix, &$fixed ) {
	foreach ( $els as &$el ) {
		if ( $el['id'] === '54d9ba8' && isset( $el['settings']['sticky'] ) ) {
			unset( $el['settings']['sticky'] );
			$fixed = true;
		}
		if ( ! empty( $el['elements'] ) ) {
			$fix( $el['elements'] );
		}
	}
};
$fix( $data );

if ( ! $fixed ) {
	WP_CLI::success( 'Section 54d9ba8 already has no sticky setting, nothing to do.' );
} else {
	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );

	\Elementor\Plugin::instance()->files_manager->clear_cache();
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'elementor_atomic_cache_validity__%'" );
	delete_post_meta( $post_id, '_elementor_css' );
	delete_post_meta( $post_id, '_elementor_element_cache_unique_id' );

	WP_CLI::success( 'Disabled sticky on the mobile/tablet header section.' );
}
