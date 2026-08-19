<?php
/**
 * Fix: sitewide search only ever returned `business` results.
 *
 * Root cause: SearchWP's "default" engine had zero indexed attributes
 * configured for the `post` source (and `page` only indexed its title) --
 * so regular blog posts had nothing indexed to match against at all,
 * regardless of query, while `business` had full title+content+taxonomy
 * indexing. This is unrelated to the Elementor "Search Archive" template
 * dangling-skin bug fixed in 2026-08-14-business-directory-feature.php --
 * that one made results render as empty; this one is why the underlying
 * query itself only ever found business posts to begin with. Both needed
 * fixing for search to work end to end.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-19-searchwp-search-scope-fix.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$engines = get_option( 'searchwp_engines' );

if ( ! is_array( $engines ) || ! isset( $engines['default']['sources'] ) ) {
	WP_CLI::error( 'Unexpected searchwp_engines structure -- check SearchWP is installed/configured before running this.' );
}

$sources = &$engines['default']['sources'];
$changed = false;

if ( empty( $sources['post.post']['attributes'] ) ) {
	$sources['post.post']['attributes'] = [ 'title' => 300, 'content' => 150 ];
	$changed = true;
	WP_CLI::log( 'post.post had no indexed attributes -- added title + content.' );
} else {
	WP_CLI::log( 'post.post already has indexed attributes, leaving as-is.' );
}

if ( empty( $sources['post.page']['attributes']['content'] ) ) {
	$sources['post.page']['attributes']['content'] = 150;
	$changed = true;
	WP_CLI::log( 'post.page had no content attribute -- added it (title was already indexed).' );
} else {
	WP_CLI::log( 'post.page already indexes content, leaving as-is.' );
}

if ( $changed ) {
	update_option( 'searchwp_engines', $engines );
	WP_CLI::log( 'Saved updated engine config. Rebuilding the index...' );
	WP_CLI::runcommand( 'searchwp index --rebuild' );
} else {
	WP_CLI::log( 'No changes needed, skipping reindex.' );
}

WP_CLI::success( 'Done.' );
