<?php
/**
 * Seeds dummy Tier/Social/Review/Catalog data (see
 * inc/business-directory/meta-box.php) across the 8 demo businesses, so
 * all three tier states have something real to look at -- the single
 * business page only renders these fields when the post's current tier
 * actually allows them (Free: none of this; Standard: social links;
 * Premium: social links + review video/rating + catalog URL).
 *
 * 3 businesses go Premium, 3 Standard, 2 stay Free, so every gating state
 * is represented.
 *
 * Usage (run once per environment):
 *   wp eval-file wp-content/themes/astra/migrations/2026-08-26-business-tier-fields-seed.php --allow-root
 *
 * Idempotent: safe to re-run.
 */

if ( ! defined( 'WP_CLI' ) ) {
	die( "Run via WP-CLI: wp eval-file " . __FILE__ . "\n" );
}

$data = [
	47972 => [ // Apex Safety Solutions -- Premium
		'business_tier'    => 'premium',
		'social_facebook'  => 'https://facebook.com/apexsafetysolutions',
		'social_youtube'   => 'https://youtube.com/@apexsafetysolutions',
		'social_instagram' => 'https://instagram.com/apexsafetysolutions',
		'social_linkedin'  => 'https://linkedin.com/company/apex-safety-solutions',
		'social_x'         => 'https://x.com/apexsafety',
		'review_rating'    => '92',
		'catalog_url'      => 'https://www.apexsafetysolutions.example/catalog',
	],
	47973 => [ // Guardian Compliance Consultants -- Premium
		'business_tier'    => 'premium',
		'social_facebook'  => 'https://facebook.com/guardiancompliance',
		'social_youtube'   => 'https://youtube.com/@guardiancompliance',
		'social_instagram' => 'https://instagram.com/guardiancompliance',
		'social_linkedin'  => 'https://linkedin.com/company/guardian-compliance-consultants',
		'social_x'         => 'https://x.com/guardiancomply',
		'review_rating'    => '88',
		'catalog_url'      => 'https://www.guardiancomplianceconsultants.example/catalog',
	],
	47977 => [ // CloudSafety Software -- Premium
		'business_tier'    => 'premium',
		'social_facebook'  => 'https://facebook.com/cloudsafetysoftware',
		'social_youtube'   => 'https://youtube.com/@cloudsafetysoftware',
		'social_instagram' => 'https://instagram.com/cloudsafetysoftware',
		'social_linkedin'  => 'https://linkedin.com/company/cloudsafety-software',
		'social_x'         => 'https://x.com/cloudsafety',
		'review_rating'    => '95',
		'catalog_url'      => 'https://www.cloudsafetysoftware.example/catalog',
	],

	47974 => [ // SafeWork Training Academy -- Standard
		'business_tier'    => 'standard',
		'social_facebook'  => 'https://facebook.com/safeworktrainingacademy',
		'social_youtube'   => 'https://youtube.com/@safeworktrainingacademy',
		'social_instagram' => 'https://instagram.com/safeworktraining',
		'social_linkedin'  => 'https://linkedin.com/company/safework-training-academy',
		'social_x'         => 'https://x.com/safeworktrain',
	],
	47975 => [ // ProTech PPE Supplies -- Standard
		'business_tier'    => 'standard',
		'social_facebook'  => 'https://facebook.com/protechppesupplies',
		'social_youtube'   => 'https://youtube.com/@protechppesupplies',
		'social_instagram' => 'https://instagram.com/protechppe',
		'social_linkedin'  => 'https://linkedin.com/company/protech-ppe-supplies',
		'social_x'         => 'https://x.com/protechppe',
	],
	47979 => [ // National Safety Federation -- Standard
		'business_tier'    => 'standard',
		'social_facebook'  => 'https://facebook.com/nationalsafetyfederation',
		'social_youtube'   => 'https://youtube.com/@nationalsafetyfederation',
		'social_instagram' => 'https://instagram.com/nationalsafetyfed',
		'social_linkedin'  => 'https://linkedin.com/company/national-safety-federation',
		'social_x'         => 'https://x.com/nationalsafety',
	],

	47976 => [ // Elite HSE Recruitment -- Free
		'business_tier' => 'free',
	],
	47978 => [ // Independent HSE Advisors -- Free
		'business_tier' => 'free',
	],
];

$updated = 0;

foreach ( $data as $post_id => $fields ) {
	if ( ! get_post( $post_id ) ) {
		WP_CLI::warning( "Post $post_id not found, skipping." );
		continue;
	}

	$changed = false;
	foreach ( $fields as $key => $value ) {
		if ( get_post_meta( $post_id, $key, true ) !== $value ) {
			update_post_meta( $post_id, $key, $value );
			$changed = true;
		}
	}

	if ( $changed ) {
		$updated++;
		WP_CLI::log( "Updated tier fields for post $post_id." );
	}
}

WP_CLI::success( $updated ? "Updated $updated business post(s)." : 'All business posts already have these fields set, nothing to do.' );
