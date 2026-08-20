<?php
/**
 * Business Details meta box: replaces the ACF field group that used to
 * live here. ACF is a free-plugin install with no PRO license on this
 * site, and this was its only field group -- with no repeaters,
 * conditional logic, or other ACF-specific feature actually in use, a
 * plain meta box removes a whole plugin dependency for what's really
 * just eight scalar fields plus an image gallery.
 *
 * Field values are stored as plain post meta under the same keys ACF
 * used for its own simple field types (text/email/oembed store the raw
 * value directly, unprefixed) -- so existing business post data keeps
 * working unchanged. The old `logo`/`cover_image`/`business_tags` ACF
 * fields were already removed/replaced before this file existed, and
 * `business_gallery` never held real data (ACF's Gallery field type
 * requires PRO, so it never actually rendered anything to save).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple scalar fields shown in the meta box. Excludes `business_gallery`,
 * which needs its own multi-image picker UI (see hse_business_gallery_field()).
 */
function hse_business_meta_fields() {
	return [
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
		'business_fax'               => [
			'label' => 'Business Fax',
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
		'video'                      => [
			'label'       => 'Review Video',
			'type'        => 'url',
			'description' => 'YouTube or Vimeo link.',
		],
		'demonstration_video'        => [
			'label'       => 'Demonstration Video',
			'type'        => 'url',
			'description' => 'YouTube or Vimeo link.',
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
	?>
	<table class="form-table">
		<tbody>
			<?php foreach ( hse_business_meta_fields() as $name => $field ) :
				$value = get_post_meta( $post->ID, $name, true );
				?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
					</th>
					<td>
						<input
							type="<?php echo esc_attr( $field['type'] ); ?>"
							id="<?php echo esc_attr( $name ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo 'url' === $field['type'] ? esc_url( $value ) : esc_attr( $value ); ?>"
							class="widefat"
						/>
						<?php if ( ! empty( $field['description'] ) ) : ?>
							<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Business Gallery', 'astra' ); ?></th>
				<td><?php hse_business_gallery_field( $post ); ?></td>
			</tr>
		</tbody>
	</table>
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
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $name ] );

		if ( 'email' === $field['type'] ) {
			$value = sanitize_email( $raw );
		} elseif ( 'url' === $field['type'] ) {
			$value = esc_url_raw( $raw );
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
