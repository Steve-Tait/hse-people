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

				$logo        = get_field( 'logo' );
				$cover       = get_field( 'cover_image' );
				$phone       = get_field( 'business_phone_number' );
				$fax         = get_field( 'business_fax' );
				$email       = get_field( 'business_contact_email' );
				$website     = get_field( 'business_website_address' );
				$address     = get_field( 'business_address' );
				$zip         = get_field( 'zip_code' );
				$gallery     = get_field( 'business_gallery' );
				$review_url  = get_field( 'video' );
				$demo_url    = get_field( 'demonstration_video' );
				$genres      = get_the_terms( get_the_ID(), 'business_genre' );
				$tags        = get_the_terms( get_the_ID(), 'business_tag' );
				$badges      = get_the_terms( get_the_ID(), 'business_badge' );
				$genres      = is_array( $genres ) ? $genres : [];
				$tags        = is_array( $tags ) ? $tags : [];
				$badges      = is_array( $badges ) ? $badges : [];
				?>

				<article <?php post_class( 'business-single' ); ?>>

					<?php if ( $cover ) : ?>
						<div class="business-single__cover" style="background-image:url('<?php echo esc_url( $cover['url'] ); ?>');"></div>
					<?php endif; ?>

					<div class="business-single__header">
						<?php if ( $logo ) : ?>
							<img class="business-single__logo" src="<?php echo esc_url( $logo['sizes']['medium'] ?? $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['alt'] ?? get_the_title() ); ?>">
						<?php endif; ?>

						<h1 class="business-single__title"><?php the_title(); ?></h1>

						<?php if ( ! empty( $badges ) ) : ?>
							<p class="business-single__badges">
								<?php foreach ( $badges as $badge ) : ?>
									<span class="business-card__badge"><?php echo esc_html( $badge->name ); ?></span>
								<?php endforeach; ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="business-single__body">

						<div class="business-single__main">

							<div class="business-single__description">
								<?php the_content(); ?>
							</div>

							<?php if ( ! empty( $gallery ) ) : ?>
								<div class="business-single__gallery">
									<h2>Gallery</h2>
									<div class="business-single__gallery-grid">
										<?php foreach ( $gallery as $image ) : ?>
											<img src="<?php echo esc_url( $image['sizes']['medium'] ?? $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>">
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>

							<?php // ACF's get_field() already returns rendered oEmbed <iframe> HTML for
							// this field type -- wp_kses_post() would strip the iframe, so this
							// is output as-is, same as WordPress core does for its own oEmbeds. ?>
							<?php if ( $review_url ) : ?>
								<div class="business-single__video">
									<h2>Review Video</h2>
									<?php echo $review_url; ?>
								</div>
							<?php endif; ?>

							<?php if ( $demo_url ) : ?>
								<div class="business-single__video">
									<h2>Demonstration Video</h2>
									<?php echo $demo_url; ?>
								</div>
							<?php endif; ?>

						</div>

						<aside class="business-single__sidebar">
							<h2>Contact Details</h2>
							<ul class="business-single__contact">
								<?php if ( $phone ) : ?><li><strong>Phone:</strong> <?php echo esc_html( $phone ); ?></li><?php endif; ?>
								<?php if ( $fax ) : ?><li><strong>Fax:</strong> <?php echo esc_html( $fax ); ?></li><?php endif; ?>
								<?php if ( $email ) : ?><li><strong>Email:</strong> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
								<?php if ( $website ) : ?><li><strong>Website:</strong> <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $website ); ?></a></li><?php endif; ?>
								<?php if ( $address ) : ?><li><strong>Address:</strong> <?php echo esc_html( $address ); ?><?php echo $zip ? ', ' . esc_html( $zip ) : ''; ?></li><?php endif; ?>
							</ul>

							<?php if ( ! empty( $genres ) ) : ?>
								<h2>Category</h2>
								<p class="business-single__terms">
									<?php
									$genre_links = array_map( function ( $genre ) {
										$url = home_url( '/business-directory/category/' . $genre->slug . '/' );
										return '<a href="' . esc_url( $url ) . '">' . esc_html( $genre->name ) . '</a>';
									}, $genres );
									echo wp_kses_post( implode( ', ', $genre_links ) );
									?>
								</p>
							<?php endif; ?>

							<?php if ( ! empty( $tags ) ) : ?>
								<h2>Tags</h2>
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
