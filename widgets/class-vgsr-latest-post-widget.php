<?php

/**
 * The VGSR Latest Post Widget
 * 
 * @package VGSR Widgets
 * @subpackage Widgets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * The VGSR Latest Post Widget Class
 * 
 * @since 1.0.0
 */
class VGSR_Latest_Post_Widget extends WP_Widget {

	/**
	 * Construct the widget
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			'vgsr-latest-post',
			__( 'Latest Post', 'vgsr-widgets' ),
			array(
				'description' => __( 'Display the latest post.', 'vgsr-widgets' ),
				'classname'   => 'vgsr-latest-post'
			)
		);
	}

	/**
	 * Output the widget's display contents
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Latest_Post_Widget::parse_defaults()
	 * @uses VGSR_Latest_Post_Widget::setup_query()
	 * @uses has_post_thumbnail()
	 * @uses the_permalink()
	 * @uses the_post_thumbnail()
	 * @uses the_title()
	 * @uses VGSR_Latest_Post_Widget::the_content()
	 * @uses VGSR_Latest_Post_Widget::the_blog_link()
	 * @uses wp_reset_postdata()
	 * @param array $args Sidebar markup arguments
	 * @param array $instance Widget's instance settings
	 */
	public function widget( $args, $instance ) {
		$instance = $this->parse_defaults( $instance );

		// Setup query. Bail when no posts where found
		$query = $this->setup_query( $instance );
		if ( ! $query->have_posts() )
			return;

		// Start widget
		echo $args['before_widget'];

		// Walk all found posts and define the post's markup
		while ( $query->have_posts() ) : $query->the_post(); ?>

		<div class="latest-post">
			<?php if ( has_post_thumbnail() ) : ?>
				<a href="<?php the_permalink(); ?>" class="post-thumbnail">
					<?php the_post_thumbnail( $instance['thumbnail_size'] ); ?>
				</a>
			<?php endif; ?>

			<?php echo $args['before_title']; ?>
				<a href="<?php the_permalink(); ?>" title="<?php printf( __( 'Posted on %s', 'vgsr-widgets' ), esc_html( get_the_date() ) ); ?>">
					<?php the_title(); ?>
				</a>
			<?php echo $args['after_title']; ?>

			<div class="widget-content">
				<?php $this->the_content( $instance ); ?>
			</div>
		</div>

		<?php endwhile;

		// Restore post global
		wp_reset_postdata();

		// Display the blog link
		$this->the_blog_link( $instance );

		// Close widget
		echo $args['after_widget'];
	}

	/**
	 * Setup and return the post query object
	 *
	 * @since 1.0.0
	 * 
	 * @uses get_term_by()
	 *
	 * @param array $args Query arguments
	 * @return WP_Query
	 */
	public function setup_query( $args ) {

		// Handle taxonomies
		if ( $args['taxonomy'] ) {
			$tax_query = array();

			// Walk query taxonomies
			foreach ( $args['taxonomy'] as $taxonomy => $term_id ) {

				// Bail when selected 'All'
				if ( empty( $term_id ) )
					continue;

				$tq = array(
					'taxonomy' => $taxonomy,
					'field'    => 'id',
					'terms'    => array( $term_id )
				);

				/**
				 * Post Formats: for the standard post format, include all non-format
				 * posts, to query posts that were created without post-format support.
				 */
				if ( 'post_format' === $taxonomy 
					&& ( $term = get_term_by( 'slug', 'post-format-standard', $taxonomy ) ) 
					&& $term->term_id === $term_id 
				) {
					$tq = array( $tq );
					$tq['relation'] = 'OR'; 
					$tq[] = array(
						'taxonomy' => $taxonomy,
						'operator' => 'NOT EXISTS'
					);
				}

				$tax_query[] = $tq;
			}

			// Define taxonomy query arg
			if ( ! empty( $tax_query ) ) {
				$tax_query['relation'] = 'AND';
				$args['tax_query'] = $tax_query;
			}
		}
		
		// Return the post query
		return new WP_Query( $args );
	}

	/**
	 * Output the widget content of the displayed post
	 *
	 * @since 1.0.0
	 *
	 * @uses get_post_gallery()
	 * @uses do_shortcode()
	 * @uses the_content()
	 * @uses the_excerpt()
	 * @uses the_title()
	 * @uses apply_filters() Calls 'the_content_more_link'
	 * @uses get_permalink()
	 * @param array $instance Widget's instance settings
	 */
	private function the_content( $instance ) {

		// Gallery post
		if ( $gallery = get_post_gallery( 0, false ) ) {

			// Get the gallery attachment ids
			$att_ids = explode( ',', $gallery['ids'] );

			// Randomize the images
			if ( $instance['gallery_random'] ) {
				shuffle( $att_ids );
			}

			// Parse shortcode and keep first x images
			echo do_shortcode( sprintf( '[gallery ids=%s columns=%d]', 
				implode( ',', array_slice( $att_ids, 0, $instance['gallery_amount'] ) ),
				$instance['gallery_columns']
			) );

		// Default post
		} else {

			// Show full post
			if ( $instance['full_content'] ) {
				the_content();

			// Show excerpt
			} else {
				the_excerpt();

				// Define read more link
				/* translators: %s: Name of current post */
				$more_link_text = sprintf( __( 'Continue reading %s <span class="meta-nav">&rarr;</span>', 'vgsr-widgets' ), the_title( '<span class="screen-reader-text">"', '"</span>', false ) );
				echo apply_filters( 'the_content_more_link', sprintf( '<a href="%s" class="more-link">%s</a>', esc_url( get_permalink() ), $more_link_text ), $more_link_text );
			}
		}
	}

	/**
	 * Output the widget's blog link
	 *
	 * @since 1.0.0
	 *
	 * @uses get_post_format_string()
	 * @uses get_post_format_link()
	 * @uses get_term_by()
	 * @uses get_term_link()
	 * @uses get_permalink()
	 * @param array $instance Widget's instance settings
	 */
	private function the_blog_link( $instance ) {

		// Bail when the blog link is not requested
		if ( ! $instance['show_blog_link'] )
			return;

		// Define local variable(s)
		$blog_link_text = $blog_link_url = false;

		// Handle post formats
		if ( isset( $instance['taxonomy']['post_format'] ) ) {
			$format = $instance['taxonomy']['post_format'];

			/** 
			 * Define plural forms of post formats.
			 * 
			 * @see get_post_format_strings() for single forms only.
			 */
			$strings = array(
				'standard' => _x( 'Standards', 'Post format', 'vgsr-widgets' ), // Special case. any value that evals to false will be considered standard
				'aside'    => _x( 'Asides',    'Post format', 'vgsr-widgets' ),
				'chat'     => _x( 'Chats',     'Post format', 'vgsr-widgets' ),
				'gallery'  => _x( 'Galleries', 'Post format', 'vgsr-widgets' ),
				'link'     => _x( 'Links',     'Post format', 'vgsr-widgets' ),
				'image'    => _x( 'Images',    'Post format', 'vgsr-widgets' ),
				'quote'    => _x( 'Quotes',    'Post format', 'vgsr-widgets' ),
				'status'   => _x( 'Statuses',  'Post format', 'vgsr-widgets' ),
				'video'    => _x( 'Videos',    'Post format', 'vgsr-widgets' ),
				'audio'    => _x( 'Audios',    'Post format', 'vgsr-widgets' ),
			);

			$plural         = isset( $strings[ $format ] ) ? $strings[ $format ] : __( 'Posts', 'vgsr-widgets' );
			$blog_link_text = sprintf( _x( 'Show all %s &rarr;', 'Read more post formats link', 'vgsr-widgets' ), strtolower( $plural ) );
			$blog_link_url  = get_post_format_link( $format );

		// Handle categories
		} else if ( $instance['taxonomy'] ) {

			// Find term and define link details
			$taxonomy = key( $instance['taxonomy'] );
			$term     = get_term_by( 'id', $instance['taxonomy'][ $taxonomy ], $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$blog_link_text = sprintf( _x( 'Show all %s posts &rarr;', 'Read more term posts link', 'vgsr-widgets' ), $term->name );
				$blog_link_url  = get_term_link( $term );
			}
		}

		// Define defaults
		if ( ! $blog_link_text )
			$blog_link_text = __( 'Show all posts &rarr;', 'vgsr-widgets' );
		if ( ! $blog_link_url )
			$blog_link_url  = 'page' == get_option( 'show_on_front' ) ? get_permalink( get_option( 'page_for_posts' ) ) : bloginfo( 'url' );

		// Output blog link
		printf( '<p class="blog-link"><a href="%s">%s</a></p>', esc_url( $blog_link_url ), esc_html( $blog_link_text ) );
	}

	/**
	 * Output the widget's form contents
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Latest_Post_Widget::parse_defaults()
	 * @uses get_post_types()
	 * @uses get_terms()
	 * 
	 * @param array $instance Widget's instance settings
	 */
	public function form( $instance ) {
		global $wp_taxonomies;

		$instance   = $this->parse_defaults( $instance );
		$post_types = get_post_types( array( 'public' => true ), 'objects' ); 
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php esc_html_e( 'Post Type', 'vgsr-widgets' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" class="widefat">
				<?php foreach ( $post_types as $post_type ) : ?>
				<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $post_type->name, $instance['post_type'] ); ?>><?php echo $post_type->labels->name; ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<?php

		// Loop all taxonomies for the available post types
		foreach ( $wp_taxonomies as $taxonomy => $args ) {

			// Bail for non-public post type taxonomies
			$tax_post_types = array_intersect( $args->object_type, wp_list_pluck( $post_types, 'name' ) );
			if ( ! $tax_post_types )
				continue;

			// Define default selected tax term
			if ( ! isset( $instance['taxonomy'][ $taxonomy ] ) ) {
				$instance['taxonomy'][ $taxonomy ] = false;
			}

			// Get used taxonomy terms
			$terms = get_terms( $taxonomy, array( 'hide_empty' => true ) );
			if ( $terms ) : ?>

			<?php // TODO: use js to toggle the associated post type taxonomies ?>
			<p class="<?php echo implode( ' ', array_map( function( $v ) { return "post-type_$v"; }, $tax_post_types ) ); ?>">
				<label for="<?php echo $this->get_field_id( "{$taxonomy}_terms" ); ?>"><?php echo $args->labels->name; ?></label>
				<select id="<?php echo $this->get_field_id( "{$taxonomy}_terms" ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ) . "[$taxonomy]"; ?>" class="widefat">
					<option value=""><?php echo esc_html( _x( 'All', 'Select taxonomy term', 'vgsr-widgets' ) ); ?></option>
					<?php foreach ( $terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $term->term_id, $instance['taxonomy'][ $taxonomy ] ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php endif;
		}

		/* Content parameters */

		?>

		<h4><?php esc_html_e( 'Content', 'vgsr-widgets' ); ?></h4>
		<p>
			<label for="<?php echo $this->get_field_id( 'full_content' ); ?>">
				<input id="<?php echo $this->get_field_id( 'full_content' ); ?>" name="<?php echo $this->get_field_name( 'full_content' ); ?>" type="checkbox" <?php checked( $instance['full_content'] ); ?>/>
				<?php esc_html_e( 'Show the full content instead of the post excerpt', 'vgsr-widgets' ); ?>
			</label>

			<br/>
			<label class="post-type_post" for="<?php echo $this->get_field_id( 'show_blog_link' ); ?>">
				<input id="<?php echo $this->get_field_id( 'show_blog_link' ); ?>" name="<?php echo $this->get_field_name( 'show_blog_link' ); ?>" type="checkbox" <?php checked( $instance['show_blog_link'] ); ?>/>
				<?php esc_html_e( 'Show link to the full blog', 'vgsr-widgets' ); ?>
			</label>

			<br/>
			<label for="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>">
				<input id="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail_size' ); ?>" type="checkbox" <?php checked( $instance['thumbnail_size'] ); ?>/>
				<?php esc_html_e( 'The post featured image size', 'vgsr-widgets' ); ?>
			</label>
		</p>

		<?php

		/* Gallery parameters */

		?>

		<h4><?php esc_html_e( 'Gallery', 'vgsr-widgets' ); ?></h4>
		<p class="description"><?php esc_html_e( 'The following settings apply to posts that contain galleries.', 'vgsr-widgets' ); ?></p>
		<p>
			<label for="<?php echo $this->get_field_id( 'gallery_amount' ); ?>">
				<?php printf( 
					/* translators: input field */
					__( 'Display a maximum of %s images.', 'vgsr-widgets' ),
					'<input id="' . $this->get_field_id( 'gallery_amount' ) . '" name="' . $this->get_field_name( 'gallery_amount' ) . '" type="number" style="width:65px;" step="1" value="' . esc_attr( $instance['gallery_amount'] ) . '" />'
				); ?>
			</label>

			<br/>
			<label for="<?php echo $this->get_field_id( 'gallery_columns' ); ?>">
				<?php printf( 
					/* translators: input field */
					__( 'Display the images within %s columns.', 'vgsr-widgets' ),
					'<input id="' . $this->get_field_id( 'gallery_columns' ) . '" name="' . $this->get_field_name( 'gallery_columns' ) . '" type="number" style="width:65px;" step="1" value="' . esc_attr( $instance['gallery_columns'] ) . '" />'
				); ?>
			</label>

			<br/>
			<label for="<?php echo $this->get_field_id( 'gallery_random' ); ?>">
				<input id="<?php echo $this->get_field_id( 'gallery_random' ); ?>" name="<?php echo $this->get_field_name( 'gallery_random' ); ?>" type="checkbox" <?php checked( $instance['gallery_random'] ); ?>/>
				<?php esc_html_e( 'Show a random selection of images', 'vgsr-widgets' ); ?>
			</label>
		</p>

		<?php
	}

	/**
	 * Update the widget's settings
	 *
	 * @since 1.0.0
	 * 
	 * @uses VGSR_Latest_Post_Widget::parse_defaults()
	 *
	 * @param array $new_instance New settings
	 * @param array $old_instance Old settings
	 * @return array Updated settings
	 */
	public function update( $new_instance, $old_instance ) {
		global $wp_taxonomies;

		// Taxonomy
		foreach ( $new_instance['taxonomy'] as $taxonomy => $term_id ) {

			// Remove unsupported taxonomies for the selected post type
			if ( ! in_array( $new_instance['post_type'], $wp_taxonomies[ $taxonomy ]->object_type ) ) {
				unset( $new_instance['taxonomy'][ $taxonomy ] );
				continue;
			}

			// Parse int
			$new_instance['taxonomy'][ $taxonomy ] = (int) $term_id;
		}

		// Handle empty checkboxes
		foreach ( $this->parse_defaults( array() ) as $key => $value ) {
			if ( ! is_bool( $value ) )
				continue;

			// Checkbox empty? Set to false
			if ( ! isset( $new_instance[ $key ] ) ) {
				$new_instance[ $key ] = false;
			}
		}

		return $new_instance;
	}

	/**
	 * Return the instance with parsed defaults
	 *
	 * @since 1.0.0
	 * 
	 * @param array $instance Widget's instance settings
	 * @return array Parsed settings
	 */
	public function parse_defaults( $instance ) {
		return wp_parse_args( $instance, array(

			// Query args
			// 'p'               => 15533, // With gallery
			// 'p'               => 14541, // With embedded image
			'posts_per_page'  => 1,
			'post_type'       => 'post',
			'post_status'     => 'publish',
			'orderby'         => 'date',
			'order'           => 'DESC',

			// Semi-query args
			'taxonomy'        => array(),

			// Content vars
			'thumbnail_size'  => false,
			'full_content'    => false,
			'show_blog_link'  => true,

			// Gallery vars
			'gallery_random'  => true,
			'gallery_amount'  => 4,
			'gallery_columns' => 2,
		) );
	}
}
