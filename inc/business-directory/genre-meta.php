<?php
/**
 * Term meta fields for `business_genre`: banner slider images and a
 * single "results grid" promo image, both shown on that category's
 * locked directory page (page-templates/page-business-directory.php,
 * reached via /business-directory/category/{slug}/).
 *
 * Reuses the gallery-picker and adds a single-image-picker on top of the
 * same wp.media JS/CSS already built for the Business Details post meta
 * box (inc/business-directory/meta-box.php) -- both are purely
 * class-based with no post-specific assumptions, so they work unmodified
 * here on the term-edit screen.
 *
 * Only shown on the *edit* screen (not "Add New Term"), since a gallery/
 * image picker needs a term to already exist to attach term meta to.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'business_genre_edit_form_fields', function ( $term ) {
	$banner_ids = get_term_meta( $term->term_id, 'banner_images', true );
	$banner_ids = is_array( $banner_ids ) ? array_map( 'absint', $banner_ids ) : [];

	$promo_id = absint( get_term_meta( $term->term_id, 'grid_promo_image', true ) );

	wp_nonce_field( 'hse_genre_meta_save', 'hse_genre_meta_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label><?php esc_html_e( 'Category Banner Images', 'astra' ); ?></label></th>
		<td>
			<div class="hse-business-gallery">
				<ul class="hse-business-gallery__items">
					<?php foreach ( $banner_ids as $id ) :
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
				<input type="hidden" name="banner_images" class="hse-business-gallery__input" value="<?php echo esc_attr( implode( ',', $banner_ids ) ); ?>" />
				<p>
					<button type="button" class="button hse-business-gallery__add"><?php esc_html_e( 'Add Images', 'astra' ); ?></button>
				</p>
			</div>
			<p class="description"><?php esc_html_e( 'Wide, short images shown as a slider above the heading on this category\'s page. Exact dimensions to be finalised later.', 'astra' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label><?php esc_html_e( 'Results Grid Promo Image', 'astra' ); ?></label></th>
		<td>
			<div class="hse-single-image-picker">
				<div class="hse-single-image-picker__preview">
					<?php if ( $promo_id ) : echo wp_get_attachment_image( $promo_id, 'medium' ); endif; ?>
				</div>
				<input type="hidden" name="grid_promo_image" class="hse-single-image-picker__input" value="<?php echo esc_attr( $promo_id ); ?>" />
				<p>
					<button type="button" class="button hse-single-image-picker__select"><?php esc_html_e( 'Select Image', 'astra' ); ?></button>
					<button type="button" class="button hse-single-image-picker__remove" <?php echo $promo_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove Image', 'astra' ); ?></button>
				</p>
			</div>
			<p class="description"><?php esc_html_e( 'Shown as a sticky image alongside the results grid on this category\'s page -- no result tiles appear in that column when this is set.', 'astra' ); ?></p>
		</td>
	</tr>
	<?php
} );

add_action( 'edited_business_genre', function ( $term_id ) {
	if ( ! isset( $_POST['hse_genre_meta_nonce'] ) || ! wp_verify_nonce( $_POST['hse_genre_meta_nonce'], 'hse_genre_meta_save' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	if ( isset( $_POST['banner_images'] ) ) {
		$ids = array_filter( array_map( 'absint', explode( ',', wp_unslash( $_POST['banner_images'] ) ) ) );
		update_term_meta( $term_id, 'banner_images', array_values( $ids ) );
	}

	if ( isset( $_POST['grid_promo_image'] ) ) {
		$id = absint( wp_unslash( $_POST['grid_promo_image'] ) );
		if ( $id ) {
			update_term_meta( $term_id, 'grid_promo_image', $id );
		} else {
			delete_term_meta( $term_id, 'grid_promo_image' );
		}
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, [ 'term.php', 'edit-tags.php' ], true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'business_genre' !== $screen->taxonomy ) {
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
