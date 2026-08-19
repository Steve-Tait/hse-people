<?php
/**
 * Fix: logging out redirects to http://codeskytest.co.uk/hsepeople instead
 * of the homepage.
 *
 * Root cause: MemberPress's `logout_redirect_url` setting is hardcoded to
 * the original site-building agency's (CodeSky) staging domain from the
 * 2020 build -- never updated when the site went live. The equally-stale
 * `login_redirect_url` (-> http://codeskytest.co.uk/hsepeople/dashboard)
 * has the same problem; MemberPress's real "Account" page exists and
 * works (confirmed via mepr_options['account_page_id']), so "dashboard"
 * was almost certainly meant to point there.
 *
 * Deliberately computed via home_url()/get_permalink() at runtime rather
 * than hardcoding this environment's URL, so the same script produces the
 * correct value on whichever environment it's run on (local, staging,
 * production).
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-19-memberpress-redirect-fix.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$opts = get_option( 'mepr_options' );

if ( ! is_array( $opts ) ) {
	WP_CLI::error( 'mepr_options not found or not an array -- is MemberPress installed?' );
}

$changed = false;

if ( strpos( (string) ( $opts['logout_redirect_url'] ?? '' ), 'codeskytest.co.uk' ) !== false ) {
	$opts['logout_redirect_url'] = home_url( '/' );
	$changed = true;
	WP_CLI::log( 'Fixed logout_redirect_url -> ' . $opts['logout_redirect_url'] );
} else {
	WP_CLI::log( 'logout_redirect_url does not reference the old staging domain, leaving as-is: ' . ( $opts['logout_redirect_url'] ?? '(empty)' ) );
}

if ( strpos( (string) ( $opts['login_redirect_url'] ?? '' ), 'codeskytest.co.uk' ) !== false ) {
	$account_page_id = $opts['account_page_id'] ?? 0;
	$account_url = $account_page_id ? get_permalink( $account_page_id ) : false;

	if ( $account_url ) {
		$opts['login_redirect_url'] = $account_url;
		$changed = true;
		WP_CLI::log( 'Fixed login_redirect_url -> ' . $account_url );
	} else {
		WP_CLI::warning( 'login_redirect_url references the old staging domain but no valid account_page_id was found -- leaving it as-is, needs manual review.' );
	}
} else {
	WP_CLI::log( 'login_redirect_url does not reference the old staging domain, leaving as-is: ' . ( $opts['login_redirect_url'] ?? '(empty)' ) );
}

if ( $changed ) {
	update_option( 'mepr_options', $opts );
	WP_CLI::success( 'Saved.' );
} else {
	WP_CLI::success( 'No changes needed.' );
}
