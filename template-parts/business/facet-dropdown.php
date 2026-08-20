<?php
/**
 * Dropdown wrapper for a FacetWP checkboxes facet: a search-input-styled
 * toggle button that opens a scrollable checkbox list with its own Apply
 * and Reset buttons. Checkbox selections only take effect when Apply is
 * pressed -- see assets/business-directory-facet-dropdown.js.
 *
 * Expects $args = [ 'label' => string, 'facet' => FacetWP facet name ].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$label = $args['label'] ?? '';
$facet = $args['facet'] ?? '';

if ( ! $facet ) {
	return;
}

$label_id   = 'facet-dropdown-label-' . $facet;
$placeholder = __( 'Please select...', 'astra' );
?>
<div class="business-directory__facet facet-dropdown" data-facet-dropdown data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
	<label class="business-directory__field-label" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $label ); ?></label>
	<button type="button" class="facet-dropdown__toggle" aria-expanded="false" aria-haspopup="true" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
		<span class="facet-dropdown__value is-placeholder"><?php echo esc_html( $placeholder ); ?></span>
		<svg class="facet-dropdown__chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<polyline points="6 9 12 15 18 9"></polyline>
		</svg>
	</button>
	<div class="facet-dropdown__panel">
		<div class="facet-dropdown__list">
			<?php echo do_shortcode( '[facetwp facet="' . esc_attr( $facet ) . '"]' ); ?>
		</div>
		<div class="facet-dropdown__actions">
			<button type="button" class="facet-dropdown__reset"><?php esc_html_e( 'Reset', 'astra' ); ?></button>
			<button type="button" class="facet-dropdown__apply"><?php esc_html_e( 'Apply', 'astra' ); ?></button>
		</div>
	</div>
</div>
