<?php
/**
 * Supplier card partial. Used inside the loop on both the taxonomy archive
 * and the directory page. Expects the loop to already be positioned on the
 * current post (i.e. call inside `the_post()`).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="business-card">

	<a class="business-card__link" href="<?php the_permalink(); ?>">

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="business-card__logo">
				<?php
				// 'medium' (proportional, no hard crop) -- not 'thumbnail',
				// which WordPress hard-crops to an exact square by default
				// (Settings > Media), cutting off the edges of any logo that
				// isn't already square. object-fit: contain on the img below
				// then shows the whole logo, letterboxed as needed within
				// the fixed-height box.
				the_post_thumbnail( 'medium' );
				?>
			</div>
		<?php endif; ?>

		<h3 class="business-card__title"><?php the_title(); ?></h3>

		<?php if ( is_sticky( get_the_ID() ) ) : ?>
			<p class="business-card__accreditations">
				<?php hse_business_render_featured_badge( get_the_ID() ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $short_desc = get_post_meta( get_the_ID(), 'short_business_description', true ) ) : ?>
			<p class="business-card__excerpt"><?php echo esc_html( $short_desc ); ?></p>
		<?php endif; ?>

	</a>

</div>
