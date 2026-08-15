<?php
/**
 * Business directory ACF field group, registered locally in code (ACF's
 * recommended pattern for version control) instead of via the database.
 *
 * Field keys are preserved exactly as they were when this group lived in
 * the database, so existing postmeta values on business posts keep
 * resolving correctly without any data migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', function () {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( [
		'key'      => 'group_business_directory',
		'title'    => 'Business Directory',
		'fields'   => [
			[
				'key'   => 'field_5f1e782fba3a6',
				'label' => 'Short Business Description',
				'name'  => 'short_business_description',
				'type'  => 'text',
			],
			[
				'key'   => 'field_5f1e783fba3a7',
				'label' => 'Business Website Address',
				'name'  => 'business_website_address',
				'type'  => 'text',
			],
			[
				'key'   => 'field_5f1e7907ba3a8',
				'label' => 'Business Phone Number',
				'name'  => 'business_phone_number',
				'type'  => 'text',
			],
			[
				'key'   => 'field_5f1e7947ba3a9',
				'label' => 'Business Fax',
				'name'  => 'business_fax',
				'type'  => 'text',
			],
			[
				'key'   => 'field_5f1e7950ba3aa',
				'label' => 'Business Contact Email',
				'name'  => 'business_contact_email',
				'type'  => 'email',
			],
			[
				'key'          => 'field_5f1e796aba3ab',
				'label'        => 'Business Tags',
				'name'         => 'business_tags',
				'type'         => 'taxonomy',
				'taxonomy'     => 'category',
				'field_type'   => 'checkbox',
				'add_term'     => 1,
				'save_terms'   => 0,
				'load_terms'   => 0,
				'return_format' => 'id',
				'multiple'     => 0,
				'allow_null'   => 0,
			],
			[
				'key'   => 'field_5f1e79e5ba3ac',
				'label' => 'Business Address',
				'name'  => 'business_address',
				'type'  => 'text',
			],
			[
				'key'   => 'field_5f1e79ffba3ad',
				'label' => 'ZIP Code',
				'name'  => 'zip_code',
				'type'  => 'text',
			],
			[
				'key'           => 'field_5f1e7a66ba3ae',
				'label'         => 'Business Gallery',
				'name'          => 'business_gallery',
				'type'          => 'gallery',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'uploadedTo',
				'mime_types'    => 'jpeg,jpg,gif,png',
			],
			[
				'key'         => 'field_5f1ebb4c95cc1',
				'label'       => 'Review Video',
				'name'        => 'video',
				'type'        => 'oembed',
				'instructions' => 'Youtube or Vimeo Link',
			],
			[
				'key'           => 'field_7d33c72367b4',
				'label'         => 'Logo',
				'name'          => 'logo',
				'type'          => 'image',
				'instructions'  => 'Supplier logo (square works best)',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
			],
			[
				'key'           => 'field_4b6768ef1b45',
				'label'         => 'Cover Image',
				'name'          => 'cover_image',
				'type'          => 'image',
				'instructions'  => 'Wide banner image shown at the top of the supplier page',
				'return_format' => 'array',
				'preview_size'  => 'large',
				'library'       => 'all',
			],
			[
				'key'         => 'field_b0a5e9148813',
				'label'       => 'Demonstration Video',
				'name'        => 'demonstration_video',
				'type'        => 'oembed',
				'instructions' => 'Youtube or Vimeo Link',
			],
		],
		'location' => [
			[
				[
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'business',
				],
			],
		],
	] );

} );
