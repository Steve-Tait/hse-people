<?php
/**
 * Supplier card partial. Used inside the loop on both the taxonomy archive
 * and the directory page. Expects the loop to already be positioned on the
 * current post (i.e. call inside `the_post()`).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo   = get_field( 'logo' );
$badges = get_the_terms( get_the_ID(), 'business_badge' );
$badges = is_array( $badges ) ? $badges : [];
?>

<div class="business-card">

	<a class="business-card__link" href="<?php the_permalink(); ?>">

		<?php if ( $logo ) : ?>
			<div class="business-card__logo">
				<img src="<?php echo esc_url( $logo['sizes']['thumbnail'] ?? $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['alt'] ?? get_the_title() ); ?>">
			</div>
		<?php endif; ?>

		<h3 class="business-card__title"><?php the_title(); ?></h3>

		<?php if ( ! empty( $badges ) ) : ?>
			<p class="business-card__badges">
				<?php hse_business_render_badges( $badges ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $short_desc = get_field( 'short_business_description' ) ) : ?>
			<p class="business-card__excerpt"><?php echo esc_html( $short_desc ); ?></p>
		<?php endif; ?>

	</a>

</div>
