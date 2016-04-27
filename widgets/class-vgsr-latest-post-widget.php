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
				'description' => __( 'Display the latest post', 'vgsr-widgets' ),
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
	 * @uses VGSR_Latest_Post_Widget::the_archive_link()
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

		<?php echo $args['before_title']; ?>
			<a href="<?php the_permalink(); ?>" title="<?php printf( __( 'Posted on %s', 'vgsr-widgets' ), esc_html( get_the_date() ) ); ?>">
				<?php the_title(); ?>
			</a>
		<?php echo $args['after_title']; ?>

		<div class="widget-content">
			<?php if ( $instance['post_thumbnail'] && has_post_thumbnail() ) : ?>
				<a href="<?php the_permalink(); ?>" class="post-thumbnail">
					<?php the_post_thumbnail(); ?>
				</a>
			<?php endif; ?>

			<?php $this->the_content( $instance ); ?>
		</div>

		<?php endwhile;

		// Restore post global
		wp_reset_postdata();

		// Display the blog link
		$this->the_archive_link( $instance );

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
				if ( 'post_format' === $taxonomy && $term_id == 'standard' ) {
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
		if ( $instance['the_gallery'] && $gallery = get_post_gallery( 0, false ) ) {

			// Get the gallery attachment ids
			$att_ids = explode( ',', $gallery['ids'] );

			// Randomize the images
			if ( $instance['gallery_random'] ) {
				shuffle( $att_ids );
			}

			// Add image link filter to point to the post
			add_filter( 'attachment_link', 'get_the_permalink', 10, 0 );

			// Parse shortcode and keep first x images
			echo do_shortcode( sprintf( '[gallery ids=%s columns=%d]',
				implode( ',', array_slice( $att_ids, 0, $instance['gallery_count'] ) ),
				$instance['gallery_columns']
			) );

			// Remove image link filter
			remove_filter( 'attachment_link', 'get_the_permalink', 10 );

		// Default post
		} else {

			// Show full post
			if ( $instance['the_content'] ) {
				the_content();

			// Show excerpt
			} else {

				// Add read-more filter
				add_filter( 'get_the_excerpt', array( $this, 'excerpt_read_more' ) );

				the_excerpt();

				// Remove read-more filter
				remove_filter( 'get_the_excerpt', array( $this, 'excerpt_read_more' ) );
			}
		}
	}

	/**
	 * Append a Read More link to the post excerpt
	 *
	 * @since 1.0.0
	 *
	 * @see get_the_excerpt()
	 *
	 * @uses the_title()
	 * @uses apply_filters() Calls 'the_content_more_link'
	 * @uses get_permalink()
	 *
	 * @param string $excerpt Post excerpt
	 * @param WP_Post $post The post. Since WP 4.5
	 * @return string Post excerpt
	 */
	public function excerpt_read_more( $excerpt, $post = null ) {

		/* translators: %s: Name of current post */
		$link_text = sprintf( __( 'Continue reading %s <span class="meta-nav">&rarr;</span>', 'vgsr-widgets' ), the_title( '<span class="screen-reader-text">"', '"</span>', false ) );
		$excerpt .= apply_filters( 'the_content_more_link', sprintf( ' <a href="%s" class="more-link">%s</a>', esc_url( get_permalink() ), $link_text ), $link_text );

		return $excerpt;
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
	private function the_archive_link( $instance ) {

		// Bail when the blog link is not requested
		if ( ! $instance['archive_link'] )
			return;

		// Define local variable(s)
		$archive_link_text = $archive_link_url = false;

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

			$plural            = isset( $strings[ $format ] ) ? $strings[ $format ] : __( 'Posts', 'vgsr-widgets' );
			$archive_link_text = sprintf( _x( 'Show all %s &rarr;', 'Read more post formats link', 'vgsr-widgets' ), strtolower( $plural ) );
			$archive_link_url  = get_post_format_link( $format );

		// Handle categories
		} else if ( $instance['taxonomy'] ) {

			// Find term and define link details
			$taxonomy = key( $instance['taxonomy'] );
			$term     = get_term_by( 'id', $instance['taxonomy'][ $taxonomy ], $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$archive_link_text = sprintf( _x( 'Show all %s &rarr;', 'Read more term posts link', 'vgsr-widgets' ), $term->name );
				$archive_link_url  = get_term_link( $term );
			}
		}

		// Define defaults
		if ( ! $archive_link_text )
			$archive_link_text = __( 'Show all &rarr;', 'vgsr-widgets' );
		if ( ! $archive_link_url )
			$archive_link_url = get_post_type_archive_link( $instance['post_type'] );

		// Output blog link
		printf( '<p class="blog-link"><a href="%s">%s</a></p>', esc_url( $archive_link_url ), esc_html( $archive_link_text ) );
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

		// Enqueue form script
		wp_enqueue_script( 'vgsr-widgets-admin' );

		?>

		<div class="post-query">
			<p>
				<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php esc_html_e( 'Post Type', 'vgsr-widgets' ); ?></label>
				<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" class="post_type widefat">
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
				if ( $terms ) :

					// Post Formats
					if ( 'post_format' == $taxonomy ) {
						$standard = new stdClass;
						$standard->term_id = 'standard';
						$standard->name = _x( 'Standard', 'Post format' ); // WP's definition

						// Prepend Standard format
						$terms = array_merge( array( $standard ), $terms );
					}

					$tax_style = in_array( $instance['post_type'], $tax_post_types ) ? '' : ' style="display:none;"'; ?>

				<?php // TODO: use js to toggle the associated post type taxonomies ?>
				<p class="<?php echo implode( ' ', array_map( function( $v ) { return "post-type_{$v}"; }, $tax_post_types ) ); ?>" <?php echo $tax_style; ?>>
					<label for="<?php echo $this->get_field_id( "{$taxonomy}_terms" ); ?>"><?php echo $args->labels->name; ?></label>
					<select id="<?php echo $this->get_field_id( "{$taxonomy}_terms" ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ) . "[$taxonomy]"; ?>" class="widefat">
						<option value=""><?php echo esc_html( _x( 'All', 'Select taxonomy term', 'vgsr-widgets' ) ); ?></option>
						<?php foreach ( $terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $term->term_id, $instance['taxonomy'][ $taxonomy ] ); ?>><?php echo $term->name; ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<?php endif;
			}

			?>

			<p>
				<label for="<?php echo $this->get_field_id( 'offset' ); ?>"><?php esc_html_e( 'Skip Post(s)', 'vgsr-widgets' ); ?></label>
				<input type="number" id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>" value="<?php echo esc_attr( $instance['offset'] ); ?>" min="0" step="1" />
			</p>
		</div>

		<?php /* Content parameters */ ?>

		<div class="post-content">
			<h4><?php esc_html_e( 'Content', 'vgsr-widgets' ); ?></h4>
			<p>
				<label for="<?php echo $this->get_field_id( 'post_thumbnail' ); ?>">
					<input id="<?php echo $this->get_field_id( 'post_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'post_thumbnail' ); ?>" type="checkbox" <?php checked( $instance['post_thumbnail'] ); ?>/>
					<?php esc_html_e( 'Display the featured image', 'vgsr-widgets' ); ?>
				</label>

				<br/>
				<label for="<?php echo $this->get_field_id( 'the_content' ); ?>">
					<input id="<?php echo $this->get_field_id( 'the_content' ); ?>" name="<?php echo $this->get_field_name( 'the_content' ); ?>" type="checkbox" <?php checked( $instance['the_content'] ); ?>/>
					<?php esc_html_e( 'Display the full post content (instead of excerpt)', 'vgsr-widgets' ); ?>
				</label>

				<br/>
				<label for="<?php echo $this->get_field_id( 'the_gallery' ); ?>">
					<input id="<?php echo $this->get_field_id( 'the_gallery' ); ?>" name="<?php echo $this->get_field_name( 'the_gallery' ); ?>" type="checkbox" <?php checked( $instance['the_gallery'] ); ?> class="post_gallery" />
					<?php esc_html_e( 'Display a gallery preview instead of the content', 'vgsr-widgets' ); ?>
				</label>

				<?php
					$archive_post_types   = wp_list_pluck( wp_list_filter( $post_types, array( 'has_archive' => true ) ), 'name' );
					$archive_post_types[] = 'post';
					$archive_style = in_array( $instance['post_type'], $archive_post_types ) ? '' : ' style="display:none;"';
				?>

				<br/>
				<label class="<?php echo implode( ' ', array_map( function( $v ) { return "post-type_{$v}"; }, $archive_post_types ) ); ?>" for="<?php echo $this->get_field_id( 'archive_link' ); ?>" <?php echo $archive_style; ?>>
					<input id="<?php echo $this->get_field_id( 'archive_link' ); ?>" name="<?php echo $this->get_field_name( 'archive_link' ); ?>" type="checkbox" <?php checked( $instance['archive_link'] ); ?>/>
					<?php esc_html_e( 'Display the archive link', 'vgsr-widgets' ); ?>
				</label>
			</p>
		</div>

		<?php /* Gallery parameters */ ?>

		<div class="post-gallery" style="<?php if ( ! $instance['the_gallery'] ) echo 'display:none;'; ?>">
			<h4><?php esc_html_e( 'Gallery', 'vgsr-widgets' ); ?></h4>
			<p><?php

				// Input: image count
				$image_count_input  = sprintf( '<select id="%s" name="%s">', $this->get_field_id( 'gallery_count' ), $this->get_field_name( 'gallery_count' ) );
				$image_count_input .= '<option value="0">' . __( 'All images', 'vgsr-widgets' ) . '</option>';
				for ( $i = 1; $i < 11; $i++ ) {
					$image_count_input .= sprintf( '<option value="%s">%s</option>', $i, sprintf( _n( '%d image', '%d images', $i, 'vgsr-widgets' ), $i ) );
				}
				$image_count_input .= '</select>';

				// Input: image order
				$image_order_input  = sprintf( '<select id="%s" name="%s">', $this->get_field_id( 'gallery_random' ), $this->get_field_name( 'gallery_random' ) );
				$image_order_input .= '<option value="0">' . _x( 'as-is', 'Image order', 'vgsr-widgets' ) . '</option>';
				$image_order_input .= '<option value="1">' . _x( 'randomly', 'Image order', 'vgsr-widgets' ) . '</option>';
				$image_order_input .= '</select>';

				// Input: column count
				$column_count_input  = sprintf( '<select id="%s" name="%s">', $this->get_field_id( 'gallery_columns' ), $this->get_field_name( 'gallery_columns' ) );
				for ( $i = 1; $i < 5; $i++ ) {
					$column_count_input .= sprintf( '<option value="%s">%s</option>', $i, sprintf( _n( '%d column', '%d columns', $i, 'vgsr-widgets' ), $i ) );
				}
				$column_count_input .= '</select>';

				/* translators: 1. image count 2. order/random 3. column count */
				printf( 'For galleries, display %1$s %2$s in %3$s.', $image_count_input, $image_order_input, $column_count_input );

			?></p>
		</div>

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

			// Skip archive link for post types without archives
			if ( 'archive_link' == $key ) {
				$archive_post_types   = get_post_types( array( 'has_archive' => true ), 'name' );
				$archive_post_types[] = 'post';

				if ( ! in_array( $instance['post_type'], $archive_post_types ) ) {
					$new_instance[ $key ] = false;
				}
			}

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
			'posts_per_page'  => 1,
			'post_type'       => 'post',
			'post_status'     => 'publish',
			'orderby'         => 'date',
			'order'           => 'DESC',
			'offset'          => 0,

			// Query dummy
			'taxonomy'        => array(),

			// Content
			'post_thumbnail'  => false,
			'the_content'     => false,
			'the_gallery'     => false,
			'archive_link'    => false,

			// Gallery
			'gallery_random'  => true,
			'gallery_count'   => 4,
			'gallery_columns' => 2,
		) );
	}
}
