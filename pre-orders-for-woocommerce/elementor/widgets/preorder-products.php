<?php
/**

 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 1.0.0
 */
class bp_preorder_products extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * Retrieve oEmbed widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'bp_preorder_products';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve oEmbed widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Pre-order Products', 'pre-orders-for-woocommerce' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve oEmbed widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-products-archive';
	}
	public function get_keywords() {
		return ['preorder', 'pre-order', 'pre order', 'product', 'preorder products'];
	}
	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the oEmbed widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return ['woocommerce-elements'];
	}

	/**
	 * Register oEmbed widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Blog Posts', 'pre-orders-for-woocommerce' ),

			]
		);
		$this->add_control(
			'columns',
			[
				'label'       => __( 'Columns', 'pre-orders-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'description' => __( 'Set how many columns are show in each row', 'pre-orders-for-woocommerce' ),

				'default'     => 3,
				'options'     => [
					'3' => esc_html__( '3', 'pre-orders-for-woocommerce' ),
					'4' => esc_html__( '4', 'pre-orders-for-woocommerce' ),

				],
			]
		);
		$this->add_control(
			'ppp',
			[
				'label'       => __( 'Show Posts', 'pre-orders-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'description' => __( 'Set how many posts will show in widget', 'pre-orders-for-woocommerce' ),

				'default'     => 3,
			]
		);
		$this->add_control(
			'sort',
			[
				'label'       => __( 'Sort', 'pre-orders-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'DESC',
				'description' => __( 'Designates the ascending or descending order of the ‘orderby‘ parameter. Defaults to ‘DESC’.', 'pre-orders-for-woocommerce' ),
				'options'     => [
					'ASC'  => __( 'ASC', 'pre-orders-for-woocommerce' ),
					'DESC' => __( 'DESC', 'pre-orders-for-woocommerce' ),

				],
			]
		);
		$this->add_control(
			'order',
			[
				'label'       => __( 'Order', 'pre-orders-for-woocommerce' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'title',
				'description' => __( 'Sort retrieved posts by parameter. Defaults to Order by (title)’.', 'pre-orders-for-woocommerce' ),
				'options'     => [
					'none'          => __( 'No order', 'pre-orders-for-woocommerce' ),
					'ID'            => __( 'Order by ID.', 'pre-orders-for-woocommerce' ),
					'author'        => __( 'Order by author.', 'pre-orders-for-woocommerce' ),
					'title'         => __( 'Order by title.', 'pre-orders-for-woocommerce' ),
					'name'          => __( 'Order by name (post slug).', 'pre-orders-for-woocommerce' ),
					'date'          => __( 'Order by date.', 'pre-orders-for-woocommerce' ),
					'rand'          => __( 'Random order.', 'pre-orders-for-woocommerce' ),
					'comment_count' => __( 'Order by number of comments.', 'pre-orders-for-woocommerce' ),

				],
			]
		);
		$this->add_control(
			'pagination',
			[
				'label'        => __( 'Show Pagination', 'pre-orders-for-woocommerce' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'pre-orders-for-woocommerce' ),
				'label_off'    => __( 'No', 'pre-orders-for-woocommerce' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->end_controls_section();
	}
	/**
	 * @param $wp_query
	 * @param $wrap_class
	 * @return null
	 */
	public static function pagination( $wp_query, $wrap_class = 'woocommerce-pagination' ) {

		/** Stop execution if there's only 1 page */
		if ( $wp_query->max_num_pages <= 1 ) {
			return;
		}

		$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
		$max   = intval( $wp_query->max_num_pages );

		/** Add current page to the array */
		if ( $paged >= 1 ) {
			$links[] = $paged;
		}

		/** Add the pages around the current page to the array */
		if ( $paged >= 3 ) {
			$links[] = $paged - 1;
			$links[] = $paged - 2;
		}

		if (  ( $paged + 2 ) <= $max ) {
			$links[] = $paged + 2;
			$links[] = $paged + 1;
		}
		echo '<nav class="' . $wrap_class . '">
		<ul class="page-numbers">' . "\n";

		/** Link to first page, plus ellipses if necessary */
		if ( !in_array( 1, $links ) ) {
			$class = 1 == $paged ? ' class="current"' : '';

			printf( '<li%s><a class="page-numbers" href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( 1 ) ), '1' );

			if ( !in_array( 2, $links ) ) {
				echo '<li class="page-item"><a class="page-numbers" href="#1">…</a></li>';
			}
		}

		/** Link to current page, plus 2 pages in either direction if necessary */
		sort( $links );
		foreach ( (array) $links as $link ) {
			$class = $paged == $link ? ' class="active page-item"' : '';
			printf( '<li%s><a class="page-numbers" href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $link ) ), $link );
		}

		/** Link to last page, plus ellipses if necessary */
		if ( !in_array( $max, $links ) ) {
			if ( !in_array( $max - 1, $links ) ) {
				echo '<li class="page-item"><a class="page-numbers" href="#1">…</a></li>' . "\n";
			}

			$class = $paged == $max ? ' class="active page-item"' : '';
			printf( '<li%s><a class="page-numbers" href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $max ) ), $max );
		}

		/** Next Post Link */
		if ( get_next_posts_link() ) {
			printf( '<li class="next-btn page-item">%s</li>' . "\n", get_next_posts_link( '<i class="fas fa-caret-right"></i>' ) );
		}

		echo '</ul>
		</nav>' . "\n";
	}

	/**
	 * Render oEmbed widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {

		global $woocommerce_loop;
		$settings   = $this->get_settings_for_display();
		$pagination = ( $settings['pagination'] == 'yes' ) ? true : '';
		$page       = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

		$woocommerce_loop['columns'] = $settings['columns'];

		// The WP_Query
		//todo: variable preorder not showing
		$preorderQuery = new \WP_Query( array(
			'post_type'      => 'product',
			'posts_per_page' => $settings['ppp'],
			'order'          => $settings['sort'],
			'orderby'        => $settings['order'],
			'paged'          => $page,
			'meta_query'     => array(

				array(
					'key'     => '_is_pre_order',
					'value'   => 'yes',
					'compare' => '=',
				),
				array(
					'key'     => '_pre_order_date',
					'value'   => date( 'y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		) );

		if ( $preorderQuery->have_posts() ) {
			echo '<div class="' . apply_filters( 'preorder_product_loop_wrapper', 'woocommerce' ) . '">';
			woocommerce_product_loop_start();
			while ( $preorderQuery->have_posts() ) {
				$preorderQuery->the_post();
				wc_get_template_part( 'content', 'product' );
			}
			woocommerce_product_loop_end();
			if ( $pagination ):
				//TODO: add style for pagination
				self::pagination( $preorderQuery );

			endif;
			echo '</div>';

		} else {
			// no posts found
			echo "<p class='no-preorder-text'>" . __( 'No pre-order products found.', 'pre-orders-for-woocommerce' ) . "</p>";
		}

		/* Restore original Post Data */
		wp_reset_query();

	}
}
