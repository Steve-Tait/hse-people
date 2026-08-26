<?php
/**
 * Business Details meta box: replaces the ACF field group that used to
 * live here. ACF is a free-plugin install with no PRO license on this
 * site, and this was its only field group -- with no repeaters,
 * conditional logic, or other ACF-specific feature actually in use, a
 * plain meta box removes a whole plugin dependency.
 *
 * Field values are stored as plain post meta under the same keys ACF
 * used for its own simple field types (text/email/oembed store the raw
 * value directly, unprefixed) -- so existing business post data keeps
 * working unchanged. The old `logo`/`cover_image`/`business_tags`/
 * `business_fax` ACF fields were already removed, and `business_gallery`
 * never held real data (ACF's Gallery field type requires PRO, so it
 * never actually rendered anything to save).
 *
 * Fields are gated by the `business_tier` radio (Free/Standard/Premium):
 * social links need Standard or Premium, the Review group and Catalog
 * URL need Premium. This is an *editing UI* restriction only (see
 * assets/business-meta-box-admin.js) -- a lower-tier post that already
 * has values in a higher-tier field (e.g. after a downgrade) keeps that
 * data; nothing here clears it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All fields shown in the meta box except `business_gallery`, which needs
 * its own multi-image picker UI (see hse_business_gallery_field()).
 *
 * `group` clusters fields into one <tbody> (and, for 'social'/'review',
 * a heading row); `show_for_tiers` gates that tbody's visibility to the
 * listed business_tier values, toggled by the admin JS. Fields with no
 * `show_for_tiers` are always shown.
 */
function hse_business_meta_fields() {
	return [
		'business_tier' => [
			'label'   => 'Tier',
			'type'    => 'radio',
			'options' => [
				'free'     => 'Free',
				'standard' => 'Standard',
				'premium'  => 'Premium',
			],
			'default' => 'free',
		],

		'short_business_description' => [
			'label' => 'Short Business Description',
			'type'  => 'text',
		],
		'business_website_address'   => [
			'label' => 'Business Website Address',
			'type'  => 'url',
		],
		'business_phone_number'      => [
			'label' => 'Business Phone Number',
			'type'  => 'text',
		],
		'business_contact_email'     => [
			'label' => 'Business Contact Email',
			'type'  => 'email',
		],
		'business_address'           => [
			'label' => 'Business Address',
			'type'  => 'text',
		],
		'zip_code'                   => [
			'label' => 'ZIP Code',
			'type'  => 'text',
		],
		'demonstration_video'        => [
			'label'       => 'Demonstration Video',
			'type'        => 'url',
			'description' => 'YouTube or Vimeo link.',
		],

		'social_facebook'  => [
			'label'          => 'Facebook',
			'type'           => 'url',
			'group'          => 'social',
			'show_for_tiers' => [ 'standard', 'premium' ],
		],
		'social_youtube'   => [
			'label'          => 'YouTube',
			'type'           => 'url',
			'group'          => 'social',
			'show_for_tiers' => [ 'standard', 'premium' ],
		],
		'social_instagram' => [
			'label'          => 'Instagram',
			'type'           => 'url',
			'group'          => 'social',
			'show_for_tiers' => [ 'standard', 'premium' ],
		],
		'social_linkedin'  => [
			'label'          => 'LinkedIn',
			'type'           => 'url',
			'group'          => 'social',
			'show_for_tiers' => [ 'standard', 'premium' ],
		],
		'social_x'         => [
			'label'          => 'X (Twitter)',
			'type'           => 'url',
			'group'          => 'social',
			'show_for_tiers' => [ 'standard', 'premium' ],
		],

		'video'         => [
			'label'          => 'Review Video',
			'type'           => 'url',
			'description'    => 'YouTube or Vimeo link.',
			'group'          => 'review',
			'show_for_tiers' => [ 'premium' ],
		],
		'review_rating' => [
			'label'          => 'Review Rating',
			'type'           => 'number',
			'description'    => 'Out of 100.',
			'min'            => 0,
			'max'            => 100,
			'group'          => 'review',
			'show_for_tiers' => [ 'premium' ],
		],

		'catalog_url'   => [
			'label'          => 'Catalog URL',
			'type'           => 'url',
			'group'          => 'catalog',
			'show_for_tiers' => [ 'premium' ],
			'live_preview'   => true,
		],
	];
}

add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'hse_business_details',
		__( 'Business Details', 'astra' ),
		'hse_business_details_meta_box_render',
		'business',
		'normal',
		'high'
	);
} );

function hse_business_details_meta_box_render( $post ) {
	wp_nonce_field( 'hse_business_details_save', 'hse_business_details_nonce' );

	$group_labels = [
		'social' => __( 'Social Media Links', 'astra' ),
		'review' => __( 'Review', 'astra' ),
	];

	$groups = [];
	foreach ( hse_business_meta_fields() as $name => $field ) {
		$groups[ $field['group'] ?? '_default' ][ $name ] = $field;
	}
	?>
	<table class="form-table hse-business-fields">
		<?php foreach ( $groups as $group_key => $group_fields ) :
			$show_for_tiers = reset( $group_fields )['show_for_tiers'] ?? [];
			?>
			<tbody<?php echo $show_for_tiers ? ' class="hse-business-fields__group" data-show-for-tiers="' . esc_attr( implode( ',', $show_for_tiers ) ) . '"' : ''; ?>>
				<?php if ( isset( $group_labels[ $group_key ] ) ) : ?>
					<tr class="hse-business-fields__group-heading">
						<th colspan="2"><?php echo esc_html( $group_labels[ $group_key ] ); ?></th>
					</tr>
				<?php endif; ?>

				<?php foreach ( $group_fields as $name => $field ) :
					hse_business_render_field_row( $post, $name, $field );
				endforeach; ?>
			</tbody>
		<?php endforeach; ?>

		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Business Gallery', 'astra' ); ?></th>
				<td><?php hse_business_gallery_field( $post ); ?></td>
			</tr>
		</tbody>
	</table>
	<?php
}

function hse_business_render_field_row( $post, $name, $field ) {
	$value = get_post_meta( $post->ID, $name, true );
	if ( '' === $value && isset( $field['default'] ) ) {
		$value = $field['default'];
	}
	?>
	<tr>
		<th scope="row">
			<?php if ( 'radio' === $field['type'] ) : ?>
				<?php echo esc_html( $field['label'] ); ?>
			<?php else : ?>
				<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
			<?php endif; ?>
		</th>
		<td>
			<?php if ( 'radio' === $field['type'] ) : ?>
				<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
					<label class="hse-business-radio-option">
						<input
							type="radio"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( $option_value ); ?>"
							<?php checked( $value, $option_value ); ?>
						/>
						<?php echo esc_html( $option_label ); ?>
					</label>
				<?php endforeach; ?>
			<?php elseif ( 'number' === $field['type'] ) : ?>
				<input
					type="number"
					id="<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
					<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
					class="small-text"
				/>
			<?php else : ?>
				<input
					type="<?php echo esc_attr( $field['type'] ); ?>"
					id="<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo 'url' === $field['type'] ? esc_url( $value ) : esc_attr( $value ); ?>"
					class="widefat<?php echo ! empty( $field['live_preview'] ) ? ' hse-business-live-preview-input' : ''; ?>"
					<?php echo ! empty( $field['live_preview'] ) ? 'data-preview-target="' . esc_attr( $name ) . '-preview"' : ''; ?>
				/>
				<?php if ( ! empty( $field['live_preview'] ) ) : ?>
					<p>
						<a
							href="<?php echo esc_url( $value ); ?>"
							target="_blank"
							rel="noopener"
							id="<?php echo esc_attr( $name ); ?>-preview"
							class="hse-business-live-preview-link"
							<?php echo $value ? '' : 'style="display:none;"'; ?>
						><?php esc_html_e( 'Open in a new tab', 'astra' ); ?> &#8599;</a>
					</p>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function hse_business_gallery_field( $post ) {
	$ids = get_post_meta( $post->ID, 'business_gallery', true );
	$ids = is_array( $ids ) ? array_map( 'absint', $ids ) : [];
	?>
	<div class="hse-business-gallery">
		<ul class="hse-business-gallery__items">
			<?php foreach ( $ids as $id ) :
				$thumb = wp_get_attachment_image( $id, 'thumbnail' );
				if ( ! $thumb ) {
					continue;
				}
				?>
				<li class="hse-business-gallery__item" data-id="<?php echo esc_attr( $id ); ?>">
					<?php echo $thumb; ?>
					<button type="button" class="hse-business-gallery__remove" aria-label="<?php esc_attr_e( 'Remove image', 'astra' ); ?>">&times;</button>
				</li>
			<?php endforeach; ?>
		</ul>
		<input type="hidden" name="business_gallery" class="hse-business-gallery__input" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
		<p>
			<button type="button" class="button hse-business-gallery__add"><?php esc_html_e( 'Add Images', 'astra' ); ?></button>
		</p>
	</div>
	<?php
}

add_action( 'save_post_business', function ( $post_id ) {
	if ( ! isset( $_POST['hse_business_details_nonce'] ) || ! wp_verify_nonce( $_POST['hse_business_details_nonce'], 'hse_business_details_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( hse_business_meta_fields() as $name => $field ) {

		if ( 'radio' === $field['type'] ) {
			$allowed = array_keys( $field['options'] );
			$raw     = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			$value   = in_array( $raw, $allowed, true ) ? $raw : ( $field['default'] ?? $allowed[0] );
			update_post_meta( $post_id, $name, $value );
			continue;
		}

		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $name ] );

		if ( 'email' === $field['type'] ) {
			$value = sanitize_email( $raw );
		} elseif ( 'url' === $field['type'] ) {
			$value = esc_url_raw( $raw );
		} elseif ( 'number' === $field['type'] ) {
			$value = '' === trim( $raw ) ? '' : (string) max( $field['min'] ?? 0, min( $field['max'] ?? PHP_INT_MAX, absint( $raw ) ) );
		} else {
			$value = sanitize_text_field( $raw );
		}

		update_post_meta( $post_id, $name, $value );
	}

	if ( isset( $_POST['business_gallery'] ) ) {
		$ids = array_filter( array_map( 'absint', explode( ',', wp_unslash( $_POST['business_gallery'] ) ) ) );
		update_post_meta( $post_id, 'business_gallery', array_values( $ids ) );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'business' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style(
		'hse-business-meta-box',
		get_template_directory_uri() . '/inc/business-directory/assets/business-meta-box-admin.css',
		[],
		defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false
	);

	wp_enqueue_script(
		'hse-business-meta-box',
		get_template_directory_uri() . '/inc/business-directory/assets/business-meta-box-admin.js',
		[ 'jquery' ],
		defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : false,
		true
	);
} );
