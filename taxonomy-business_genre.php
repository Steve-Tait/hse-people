<?php
/**
 * business_genre taxonomy archive (category-filtered supplier listing).
 *
 * Same wrapper structure as Astra's own archive.php, with the loop swapped
 * for the shared supplier card partial.
 */

get_header(); ?>

<?php if ( astra_page_layout() === 'left-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

	<div id="primary" <?php astra_primary_class(); ?>>
		<?php astra_primary_content_top(); ?>
		<?php astra_archive_header(); ?>

		<main id="main" class="site-main">
			<?php if ( have_posts() ) : ?>
				<div class="business-directory-grid">
					<?php while ( have_posts() ) : the_post();
						get_template_part( 'template-parts/business/card' );
					endwhile; ?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No suppliers found in this category.', 'astra' ); ?></p>
			<?php endif; ?>
		</main>

		<?php astra_pagination(); ?>
		<?php astra_primary_content_bottom(); ?>
	</div><!-- #primary -->

<?php if ( astra_page_layout() === 'right-sidebar' ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<?php get_footer();
