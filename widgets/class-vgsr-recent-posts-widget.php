<?php

/**
 * VGSR Recent Posts Widget Class
 * 
 * @package VGSR Widgets
 * @subpackage Widgets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * The VGSR Recent Posts Widget Class
 *
 * Extend/use WP's native Recent Posts widget's logic.
 * 
 * @since 1.0.0
 *
 * @see WP_Widget_Recent_Posts
 */
class VGSR_Recent_Posts_Widget extends WP_Widget_Recent_Posts {

	/**
	 * Construct the widget
	 *
	 * Skip right to the grandparent constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'widget_recent_entries vgsr_recent_posts',
			'description' => __( 'Your site&#8217;s most recent Posts with post thumbnails.', 'vgsr-widgets' ),
			'customize_selective_refresh' => true,
		);
		WP_Widget::__construct( 'vgsr-recent-posts', __( 'Recent Posts 2', 'vgsr-widgets' ), $widget_ops );
		$this->alt_option_name = 'widget_recent_entries';

		// Enqueue style
		wp_enqueue_style( 'vgsr-recent-posts-widget', vgsr_widgets()->assets_url . 'css/vgsr-recent-posts-widget.css' );
	}

	/**
	 * Output the widget's display contents
	 *
	 * Added:
	 * - li post class
	 * - post thumbnail
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Sidebar markup arguments
	 * @param array $instance Widget's instance settings
	 */
	public function widget( $args, $instance ) {
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Posts' );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		/**
		 * Filter the arguments for the Recent Posts widget.
		 *
		 * @since 3.4.0
		 *
		 * @see WP_Query::get_posts()
		 *
		 * @param array $args An array of arguments used to retrieve the recent posts.
		 */
		$r = new WP_Query( apply_filters( 'widget_posts_args', array(
			'posts_per_page'      => $number,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true
		) ) );

		if ($r->have_posts()) :
		?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . sprintf( '<a href="%s">%s</a>', esc_url( get_post_type_archive_link( 'post' ) ), $title ) . $args['after_title'];
		} ?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li <?php post_class(); ?>>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="post-thumbnail">
					<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( array( 75, 75 ) ); ?></a>
				</div>
			<?php endif; ?>

				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget']; ?>
		<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;
	}
}
