<?php

/**
 * VGSR Subpages Shortcode
 * 
 * @package VGSR Widgets
 * @subpackage Shortcodes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Shortcode_Subpages' ) ) :
/**
 * The VGSR Shortcode Subpages Class
 *
 * @since 1.0.0
 */
class VGSR_Shortcode_Subpages extends VGSR_Shortcode {

	/**
	 * Holds the subpage word count
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $word_count = 55;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Shortcode_Subpages::setup_actions()
	 */
	public function __construct() {
		parent::__construct( 'vgsr-subpages', array(

			// Preferred pages. Comma-separated ID list
			'pages'     => array(
				'label'   => esc_html__( 'Pages', 'vgsr-widgets' ),
				'default' => false,
			),

			// Post type name
			'post_type' => array(
				'label'   => esc_html__( 'Post Type', 'vgsr-widgets' ),
				'default' => 'page',
			),

			// Word count
			'words' => array(
				'label'   => esc_html__( 'Word Count', 'vgsr-widgets' ),
				'type'    => 'number',
				'default' => 55,
			),
		), array(
			'label'         => esc_html__( 'VGSR Subpages', 'vgsr-widgets' ),
			'listItemImage' => 'dashicons-screenoptions',
		) );
	}

	/**
	 * Shortcode callback
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_parse_id_list()
	 * @uses WP_Query()
	 * @uses the_title()
	 * @uses get_the_permalink()
	 * @uses the_content()
	 *
	 * @param array $atts Shortcode instance attributes
	 */
	public function shortcode( $atts = array() ) {

		// Define local variable
		$query_args = array( 'posts_per_page' => -1, 'post_status' => 'publish' );

		// Define query args
		if ( $atts['pages'] ) {
			$query_args['post__in']  = wp_parse_id_list( $atts['pages'] );
			$query_args['post_type'] = 'any';
		} else {
			$query_args['post_parent'] = get_the_ID();
			$query_args['post_type']   = $atts['post_type'];
		}

		// Bail when the query is invalid
		if ( ! $query = new WP_Query( $query_args ) )
			return;

		// Bail when no posts were found
		if ( ! $query->have_posts() )
			return;

		// Construct content filters
		add_filter( 'the_content', 'strip_shortcodes',                 5 );
		add_filter( 'the_content', array( $this, 'limit_word_count' ), 5 );
		add_filter( 'the_content', 'balanceTags',                      6 );

		// Set class globals
		$this->word_count = absint( $atts['words'] );

		// Use teasers with <!--more--> tags
		global $more; $more = 0;

		// Start output buffer
		ob_start(); ?>

		<div class="vgsr-subpages">
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>

			<div <?php post_class( $query->current_post % 2 ? 'even' : 'odd' ); ?>>
				<header class="subpage-header">
					<h3 class="subpage-title"><?php the_title( sprintf( '<a href="%s">', get_the_permalink() ), '</a>' ); ?></h3>
				</header>

				<div class="subpage-content">
					<?php /* translators: %s: Name of current post */ ?>
					<?php the_content( sprintf(
						__( 'Continue reading %s <span class="meta-nav">&rarr;</span>', 'vgsr-widgets' ),
						the_title( '<span class="screen-reader-text">"', '"</span>', false )
					) ); ?>
				</div>
			</div>

			<?php endwhile; ?>
		</div>

		<?php

		// End output buffer
		$html = ob_get_clean();

		// Reset class globals
		$this->word_count = 55;

		// Deconstruct content filters
		remove_filter( 'the_content', 'balanceTags',                      6 );
		remove_filter( 'the_content', array( $this, 'limit_word_count' ), 5 );
		remove_filter( 'the_content', 'strip_shortcodes',                 5 );

		// Reset `$post` and other globals (like `$more`)
		wp_reset_postdata();

		// Enqueue styles
		wp_enqueue_style( 'vgsr-subpages', vgsr_widgets()->plugin_url . 'assets/css/vgsr-shortcode-subpages.css' );

		return $html;
	}

	/**
	 * Limit the word count of a text
	 *
	 * Use only in The Loop!
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_trim_words()
	 * @uses get_permalink()
	 * @uses the_title()
	 *
	 * @param string $content
	 * @return string
	 */
	public function limit_word_count( $content ) {
		$post = get_post();

		// Only limit words when there is no teaser
		if ( ! preg_match( '/<!--more(.*?)?-->/', $post->post_content ) ) {
			$content  = wp_trim_words( $content, $this->word_count, '' );
			$content  = trim( $content, ',' );

			// Append Read More link
			$content .= sprintf( '&hellip; <a href="%s" class="more-link">%s</a>',
				get_permalink(),
				sprintf(
					/* translators: %s: Name of current post */
					__( 'Continue reading %s <span class="meta-nav">&rarr;</span>', 'vgsr-widgets' ),
					the_title( '<span class="screen-reader-text">"', '"</span>', false )
				)
			);
		}

		return $content;
	}
}

endif; // class_exists
