<?php
/**
 * HSE Magazine Archive Elementor widget.
 *
 * Migrated from an Angie code-snippet (post ID 47779 on the original site)
 * into theme code, so it's version-controlled instead of split across a
 * database record and a separately-deployed on-disk copy. The widget slug
 * (get_name()) is kept identical to the original so existing page content
 * referencing it keeps working unchanged.
 *
 * Adds real pagination: "Number of Issues" is now a per-page count, with
 * page links generated via paginate_links() and a `mag_page` query var.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HSE_Magazine_Archive_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'magazine_archive_e4c52962';
	}

	public function get_title() {
		return esc_html__( 'HSE Magazine Archive', 'astra' );
	}

	public function get_icon() {
		return 'eicon-archive-posts';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_style_depends() {
		return [ 'magazine-archive-style' ];
	}

	public function get_script_depends() {
		return [ 'magazine-archive-script' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Query Settings', 'astra' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$categories = get_categories( [ 'hide_empty' => false ] );
		$cat_options = [];
		foreach ( $categories as $cat ) {
			$cat_options[ $cat->slug ] = $cat->name;
		}

		$this->add_control(
			'category_slug',
			[
				'label' => esc_html__( 'Select Category', 'astra' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'e-magazines',
				'options' => $cat_options,
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label' => esc_html__( 'Issues Per Page', 'astra' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 12,
				'min' => 1,
				'max' => 100,
			]
		);

		$this->add_control(
			'show_search',
			[
				'label' => esc_html__( 'Show Search Bar', 'astra' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'astra' ),
				'label_off' => esc_html__( 'Hide', 'astra' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_grid',
			[
				'label' => esc_html__( 'Grid Layout', 'astra' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'columns',
			[
				'label' => esc_html__( 'Columns', 'astra' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '3',
				'options' => [
					'2' => esc_html__( '2 Columns', 'astra' ),
					'3' => esc_html__( '3 Columns', 'astra' ),
					'4' => esc_html__( '4 Columns', 'astra' ),
				],
				'selectors' => [
					'{{WRAPPER}} .magazine-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_control(
			'card_bg',
			[
				'label' => esc_html__( 'Card Background', 'astra' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#FFFFFF',
				'selectors' => [
					'{{WRAPPER}} .magazine-card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Title Color', 'astra' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#3A3A3A',
				'selectors' => [
					'{{WRAPPER}} .magazine-card-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_color',
			[
				'label' => esc_html__( 'Button Color', 'astra' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#1368AA',
				'selectors' => [
					'{{WRAPPER}} .magazine-card-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$cat_slug = ! empty( $settings['category_slug'] ) ? $settings['category_slug'] : 'e-magazines';
		$per_page = ! empty( $settings['posts_per_page'] ) ? intval( $settings['posts_per_page'] ) : 12;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination cursor, no state change
		$current_page = isset( $_GET['mag_page'] ) ? max( 1, absint( wp_unslash( $_GET['mag_page'] ) ) ) : 1;

		$args = [
			'post_type' => 'post',
			'posts_per_page' => $per_page,
			'paged' => $current_page,
			'category_name' => $cat_slug,
			'post_status' => 'publish',
		];

		$query = new \WP_Query( $args );

		echo '<div class="magazine-grid-wrapper" data-widget-id="' . esc_attr( $this->get_id() ) . '">';

		if ( 'yes' === $settings['show_search'] ) {
			?>
			<div class="magazine-search-container">
				<input type="text" class="magazine-search-input" placeholder="<?php esc_attr_e( 'Search magazine editions...', 'astra' ); ?>">
				<span class="magazine-search-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				</span>
			</div>
			<?php
		}

		if ( $query->have_posts() ) :
			echo '<div class="magazine-grid">';
			while ( $query->have_posts() ) :
				$query->the_post();
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
			endwhile;
			echo '</div>';
			echo '<p class="magazine-no-results" style="display:none; text-align:center; padding: 40px; color:#7A7A7A;">' . esc_html__( 'No matching editions found.', 'astra' ) . '</p>';

			if ( $query->max_num_pages > 1 ) {
				echo '<nav class="magazine-pagination" aria-label="' . esc_attr__( 'Magazine issues pagination', 'astra' ) . '">';
				echo wp_kses_post( paginate_links( [
					'base' => esc_url_raw( add_query_arg( 'mag_page', '%#%' ) ),
					'format' => '',
					'current' => $current_page,
					'total' => (int) $query->max_num_pages,
					'prev_text' => esc_html__( '« Previous', 'astra' ),
					'next_text' => esc_html__( 'Next »', 'astra' ),
				] ) );
				echo '</nav>';
			}

			wp_reset_postdata();
		else :
			echo '<p class="magazine-no-posts">' . esc_html__( 'No magazine editions found in this category.', 'astra' ) . '</p>';
		endif;

		echo '</div>';
	}

	protected function content_template() {
		?>
		<div class="magazine-grid-wrapper">
			<# if ( settings.show_search === 'yes' ) { #>
			<div class="magazine-search-container">
				<input type="text" class="magazine-search-input" placeholder="Search magazine editions..." disabled>
			</div>
			<# } #>
			<div class="magazine-grid">
				<# for ( var i = 0; i < 3; i++ ) { #>
				<div class="magazine-card">
					<div class="magazine-card-image-wrap">
						<img class="magazine-card-image" style="opacity: 0.5;">
					</div>
					<div class="magazine-card-content">
						<h3 class="magazine-card-title">Sample Magazine Edition</h3>
						<p class="magazine-card-date">January 1, 2025</p>
						<span class="magazine-card-btn" style="display:inline-block; text-align:center;">Read Edition</span>
					</div>
				</div>
				<# } #>
			</div>
		</div>
		<?php
	}
}
