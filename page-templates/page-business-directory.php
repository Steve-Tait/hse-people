<?php
/**
 * Template Name: Business Directory
 *
 * Supplier directory: search + category + badge filters (FacetWP) over a
 * card grid of `business` posts. FacetWP integration follows its documented
 * classic-template pattern: wrap the loop in a `.facetwp-template` element
 * and pass `'facetwp' => true` in the WP_Query args.
 */

get_header(); ?>

<?php if ( astra_page_layout() === 'left-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

	<div id="primary" <?php astra_primary_class(); ?>>
		<?php astra_primary_content_top(); ?>

		<main id="main" class="site-main">

			<?php while ( have_posts() ) : the_post(); ?>
				<div class="business-directory__intro">
					<?php the_title( '<h1>', '</h1>' ); ?>
					<?php the_content(); ?>
				</div>
			<?php endwhile; ?>

			<div class="business-directory__filters">
				<div class="business-directory__search">
					<?php echo do_shortcode( '[facetwp facet="business_search"]' ); ?>
				</div>
				<div class="business-directory__facet">
					<h3>Category</h3>
					<?php echo do_shortcode( '[facetwp facet="business_genre"]' ); ?>
				</div>
				<div class="business-directory__facet">
					<h3>Badges</h3>
					<?php echo do_shortcode( '[facetwp facet="business_badge"]' ); ?>
				</div>
			</div>

			<?php
			$directory_query = new WP_Query( [
				'post_type'      => 'business',
				'posts_per_page' => 12,
				'facetwp'        => true,
			] );
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

		</main>

		<?php astra_primary_content_bottom(); ?>
	</div><!-- #primary -->

<?php if ( astra_page_layout() === 'right-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<?php get_footer();
