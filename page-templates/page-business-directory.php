<?php
/**
 * Template Name: Business Directory
 *
 * Supplier directory: search + category + badge filters (FacetWP) over a
 * card grid of `business` posts. FacetWP integration follows its documented
 * classic-template pattern: wrap the loop in a `.facetwp-template` element
 * and pass `'facetwp' => true` in the WP_Query args.
 *
 * Also serves /business-directory/category/{slug}/ (see
 * inc/business-directory/rewrite.php) -- the same page, permanently
 * filtered to one business_genre term with the category facet hidden,
 * since it's fixed by the URL. Search still applies within that category.
 */

$locked_genre_slug = get_query_var( 'business_genre' );
$locked_genre_term = $locked_genre_slug ? get_term_by( 'slug', $locked_genre_slug, 'business_genre' ) : false;
// An unrecognised category slug just falls back to the normal, unfiltered view.

get_header(); ?>

<?php if ( astra_page_layout() === 'left-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

	<div id="primary" <?php astra_primary_class(); ?>>
		<?php astra_primary_content_top(); ?>

		<main id="main" class="site-main">
			<div class="business-directory-container">

				<?php while ( have_posts() ) : the_post(); ?>
					<div class="business-directory__intro">
						<?php if ( $locked_genre_term ) : ?>
							<h1><?php echo esc_html( $locked_genre_term->name ); ?> <?php esc_html_e( 'Suppliers', 'astra' ); ?></h1>
							<?php if ( ! empty( $locked_genre_term->description ) ) : ?>
								<div class="business-directory__genre-description">
									<?php echo wp_kses_post( wpautop( $locked_genre_term->description ) ); ?>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<?php the_title( '<h1>', '</h1>' ); ?>
							<?php the_content(); ?>
						<?php endif; ?>
					</div>
				<?php endwhile; ?>

				<?php if ( ! $locked_genre_term ) :
					$genre_terms = get_terms( [
						'taxonomy'   => 'business_genre',
						'hide_empty' => true,
					] );
					?>
					<?php if ( ! empty( $genre_terms ) && ! is_wp_error( $genre_terms ) ) : ?>
						<div class="business-directory__categories">
							<h2><?php esc_html_e( 'Browse by Category', 'astra' ); ?></h2>
							<div class="business-directory-category-grid">
								<?php foreach ( $genre_terms as $genre_term ) : ?>
									<a class="business-directory-category-tile" href="<?php echo esc_url( home_url( '/business-directory/category/' . $genre_term->slug . '/' ) ); ?>">
										<span class="business-directory-category-tile__name"><?php echo esc_html( $genre_term->name ); ?></span>
										<span class="business-directory-category-tile__count">
											<?php
											/* translators: %d: number of suppliers in this category */
											echo esc_html( sprintf( _n( '%d supplier', '%d suppliers', $genre_term->count, 'astra' ), $genre_term->count ) );
											?>
										</span>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<div class="business-directory__filters">
					<div class="business-directory__search">
						<h3><?php esc_html_e( 'Search', 'astra' ); ?></h3>
						<?php echo do_shortcode( '[facetwp facet="business_search"]' ); ?>
					</div>
					<?php if ( ! $locked_genre_term ) : ?>
						<div class="business-directory__facet">
							<h3><?php esc_html_e( 'Category', 'astra' ); ?></h3>
							<?php echo do_shortcode( '[facetwp facet="business_genre"]' ); ?>
						</div>
					<?php endif; ?>
					<div class="business-directory__facet">
						<h3><?php esc_html_e( 'Badges', 'astra' ); ?></h3>
						<?php echo do_shortcode( '[facetwp facet="business_badge"]' ); ?>
					</div>
				</div>

				<?php
				$query_args = [
					'post_type'      => 'business',
					'posts_per_page' => 12,
					'facetwp'        => true,
				];

				if ( $locked_genre_term ) {
					$query_args['tax_query'] = [ [
						'taxonomy' => 'business_genre',
						'field'    => 'term_id',
						'terms'    => $locked_genre_term->term_id,
					] ];
				}

				$directory_query = new WP_Query( $query_args );
				?>

				<div class="facetwp-template">
					<?php if ( $directory_query->have_posts() ) : ?>
						<div class="business-directory-grid">
							<?php while ( $directory_query->have_posts() ) : $directory_query->the_post();
								get_template_part( 'template-parts/business/card' );
							endwhile; ?>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No suppliers match your search.', 'astra' ); ?></p>
					<?php endif; ?>
				</div>

				<?php echo facetwp_display( 'pager' ); ?>

				<?php wp_reset_postdata(); ?>

			</div>
		</main>

		<?php astra_primary_content_bottom(); ?>
	</div><!-- #primary -->

<?php if ( astra_page_layout() === 'right-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<?php get_footer();
