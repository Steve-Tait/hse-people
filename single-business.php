<?php
/**
 * Single Supplier (business CPT) template.
 *
 * Plain PHP/ACF, no Elementor. Reuses Astra's standard wrapper (header,
 * sidebar conditionals, primary content hooks, footer) so the page inherits
 * the site's normal header/footer/width, with a custom loop in between.
 */

get_header(); ?>

<?php if ( astra_page_layout() === 'left-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

	<div id="primary" <?php astra_primary_class(); ?>>
		<?php astra_primary_content_top(); ?>

		<main id="main" class="site-main">
			<?php while ( have_posts() ) : the_post();

				$post_id     = get_the_ID();
				$phone       = get_post_meta( $post_id, 'business_phone_number', true );
				$fax         = get_post_meta( $post_id, 'business_fax', true );
				$email       = get_post_meta( $post_id, 'business_contact_email', true );
				$website     = get_post_meta( $post_id, 'business_website_address', true );
				$address     = get_post_meta( $post_id, 'business_address', true );
				$zip         = get_post_meta( $post_id, 'zip_code', true );
				$gallery_ids = get_post_meta( $post_id, 'business_gallery', true );
				$gallery_ids = is_array( $gallery_ids ) ? $gallery_ids : [];
				$review_url  = get_post_meta( $post_id, 'video', true );
				$demo_url    = get_post_meta( $post_id, 'demonstration_video', true );
				$genres      = get_the_terms( get_the_ID(), 'business_genre' );
				$tags        = get_the_terms( get_the_ID(), 'business_tag' );
				$badges      = get_the_terms( get_the_ID(), 'business_badge' );
				$genres      = is_array( $genres ) ? $genres : [];
				$tags        = is_array( $tags ) ? $tags : [];
				$badges      = is_array( $badges ) ? $badges : [];
				?>

				<article <?php post_class( 'business-single' ); ?>>

					<div class="business-single__header">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium', [ 'class' => 'business-single__logo' ] ); ?>
						<?php endif; ?>

						<h1 class="business-single__title"><?php the_title(); ?></h1>

						<?php if ( ! empty( $badges ) ) : ?>
							<p class="business-single__badges">
								<?php hse_business_render_badges( $badges ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="business-single__body">

						<div class="business-single__main">

							<div class="business-single__description">
								<?php the_content(); ?>
							</div>

							<?php if ( ! empty( $gallery_ids ) ) : ?>
								<div class="business-single__gallery">
									<h3 class="business-single__section-title">Gallery</h3>
									<div class="business-single__gallery-grid">
										<?php foreach ( $gallery_ids as $image_id ) :
											echo wp_get_attachment_image( $image_id, 'medium' );
										endforeach; ?>
									</div>
								</div>
							<?php endif; ?>

							<?php // wp_oembed_get() returns the provider's own <iframe> markup --
							// wp_kses_post() would strip the iframe, so this is output as-is,
							// same as WordPress core does for its own oEmbeds. ?>
							<?php if ( $review_url && $review_embed = wp_oembed_get( $review_url ) ) : ?>
								<div class="business-single__video">
									<h3 class="business-single__section-title">Review Video</h3>
									<?php echo $review_embed; ?>
								</div>
							<?php endif; ?>

							<?php if ( $demo_url && $demo_embed = wp_oembed_get( $demo_url ) ) : ?>
								<div class="business-single__video">
									<h3 class="business-single__section-title">Demonstration Video</h3>
									<?php echo $demo_embed; ?>
								</div>
							<?php endif; ?>

						</div>

						<aside class="business-single__sidebar">
							<?php if ( $phone || $fax || $email || $website || $address ) : ?>
								<h3 class="business-single__section-title">Contact Details</h3>
								<ul class="business-single__contact">
									<?php if ( $phone ) : ?><li><?php echo hse_business_icon( 'phone' ); ?><span><?php echo esc_html( $phone ); ?></span></li><?php endif; ?>
									<?php if ( $fax ) : ?><li><strong>Fax:</strong> <?php echo esc_html( $fax ); ?></li><?php endif; ?>
									<?php if ( $email ) : ?><li><?php echo hse_business_icon( 'email' ); ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
									<?php if ( $website ) : ?><li><?php echo hse_business_icon( 'website' ); ?><a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $website ); ?></a></li><?php endif; ?>
									<?php if ( $address ) : ?><li><?php echo hse_business_icon( 'address' ); ?><span><?php echo esc_html( $address ); ?><?php echo $zip ? ', ' . esc_html( $zip ) : ''; ?></span></li><?php endif; ?>
								</ul>
							<?php endif; ?>

							<?php if ( ! empty( $genres ) ) : ?>
								<h3 class="business-single__section-title">Category</h3>
								<div class="business-single__term-pills">
									<?php foreach ( $genres as $genre ) :
										$url = home_url( '/business-directory/category/' . $genre->slug . '/' );
										?>
										<a class="business-single__term-pill" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $genre->name ); ?></a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $tags ) ) : ?>
								<h3 class="business-single__section-title">Tags</h3>
								<p class="business-single__terms">
									<?php echo esc_html( implode( ', ', wp_list_pluck( $tags, 'name' ) ) ); ?>
								</p>
							<?php endif; ?>
						</aside>

					</div>

				</article>

			<?php endwhile; ?>
		</main>

		<?php astra_primary_content_bottom(); ?>
	</div><!-- #primary -->

<?php if ( astra_page_layout() === 'right-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<?php get_footer();
