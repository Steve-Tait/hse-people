<?php
/**
 * "Featured" business flag.
 *
 * Used to be the `featured` term in the business_badge (now
 * business_accreditation) taxonomy. It's a checkbox on the business post
 * now instead, backed by WordPress's own sticky-post storage
 * (stick_post() / unstick_post() / is_sticky(), the `sticky_posts`
 * option) rather than a new custom meta key -- that's exactly what the
 * sticky mechanism already models ("pin this to the top"), and it's
 * post-type-agnostic at the storage/API level even though core's
 * automatic query-reordering and the classic-editor "Stick this post"
 * checkbox are both hardcoded to the 'post' post type only. Both of
 * those gaps are filled in below: the checkbox lives in the Publish box
 * (post_submitbox_misc_actions, the same extension point core itself
 * uses to add the native sticky checkbox for posts), and featured-first
 * ordering is applied explicitly via posts_orderby.
 *
 * Storing this in the shared `sticky_posts` option is safe alongside
 * WordPress's native blog-post stickiness: the option is just a flat
 * array of post IDs with no per-post-type meaning baked in. Featuring a
 * business doesn't surface it on the blog's sticky-post front page
 * (that query is scoped to post_type "post"), and a sticky blog post
 * doesn't get pulled into the business sort below (business_sticky_ids()
 * filters to business posts only).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IDs from the shared `sticky_posts` option that are actually business
 * posts -- the option itself doesn't distinguish post type.
 */
function hse_business_sticky_ids() {
	$sticky = get_option( 'sticky_posts' );
	if ( empty( $sticky ) || ! is_array( $sticky ) ) {
		return [];
	}

	return array_values( array_filter( $sticky, function ( $id ) {
		return 'business' === get_post_type( $id );
	} ) );
}

add_action( 'post_submitbox_misc_actions', function ( $post ) {
	if ( 'business' !== $post->post_type ) {
		return;
	}
	?>
	<div class="misc-pub-section hse-business-featured">
		<label for="hse_business_featured">
			<input type="checkbox" id="hse_business_featured" name="hse_business_featured" value="1" <?php checked( is_sticky( $post->ID ) ); ?> />
			<?php esc_html_e( 'Featured', 'astra' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Featured businesses rank at the top of search results and listings.', 'astra' ); ?></p>
	</div>
	<?php
} );

add_action( 'save_post_business', function ( $post_id ) {
	// Reuses the Business Details meta box's nonce -- both it and the
	// Publish box render inside the same <form>, so this arrives in the
	// same $_POST without needing a second nonce field just for this.
	if ( ! isset( $_POST['hse_business_details_nonce'] ) || ! wp_verify_nonce( $_POST['hse_business_details_nonce'], 'hse_business_details_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! empty( $_POST['hse_business_featured'] ) ) {
		stick_post( $post_id );
	} else {
		unstick_post( $post_id );
	}
} );

/**
 * Featured-first ordering for the directory grid/category archive and
 * sitewide search. Applied at the SQL level (not by re-sorting the
 * fetched array) so it holds up correctly across pagination -- a
 * featured business is guaranteed to land on page 1 regardless of how
 * many results there are, rather than only being reordered within
 * whatever page it happened to fall on.
 *
 * WP_Query's own automatic sticky-post handling only ever applies to
 * post_type "post", so a business post being sticky has no effect on
 * ordering unless this filter does it explicitly.
 */
add_filter( 'posts_orderby', function ( $orderby, $query ) {
	$post_type = $query->get( 'post_type' );
	$is_business_query = ( 'business' === $post_type )
		|| ( is_array( $post_type ) && in_array( 'business', $post_type, true ) )
		|| $query->is_tax( 'business_genre' )
		|| $query->is_tax( 'business_accreditation' )
		|| $query->is_tax( 'location' );

	$is_search_query = $query->is_search() && $query->is_main_query();

	if ( ! $is_business_query && ! $is_search_query ) {
		return $orderby;
	}

	$featured_ids = hse_business_sticky_ids();
	if ( empty( $featured_ids ) ) {
		return $orderby;
	}

	global $wpdb;
	$id_list = implode( ',', array_map( 'absint', $featured_ids ) );
	$case = "CASE WHEN {$wpdb->posts}.ID IN ($id_list) THEN 0 ELSE 1 END";

	return $orderby ? "$case, $orderby" : $case;
}, 10, 2 );
