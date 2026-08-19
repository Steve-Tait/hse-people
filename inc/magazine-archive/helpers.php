<?php
/**
 * Shared rendering helper so the widget's normal output and the AJAX
 * search results stay visually identical.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hse_magazine_render_card() {
	$thumbnail_url = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
	if ( ! $thumbnail_url ) {
		$thumbnail_url = get_template_directory_uri() . '/inc/magazine-archive/assets/magazine-placeholder.png';
	}
	?>
	<div class="magazine-card" data-title="<?php echo esc_attr( strtolower( get_the_title() ) ); ?>">
		<div class="magazine-card-image-wrap">
			<img class="magazine-card-image" src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
		</div>
		<div class="magazine-card-content">
			<h3 class="magazine-card-title"><?php the_title(); ?></h3>
			<p class="magazine-card-date"><?php echo esc_html( get_the_date() ); ?></p>
			<a href="<?php the_permalink(); ?>" class="magazine-card-btn"><?php esc_html_e( 'Read Edition', 'astra' ); ?></a>
		</div>
	</div>
	<?php
}
