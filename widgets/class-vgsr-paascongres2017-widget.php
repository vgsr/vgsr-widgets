<?php

/**
 * The VGSR Paascongres 2017 Widget Class
 * 
 * @package VGSR Widgets
 * @subpackage Paascongres 2017
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * The VGSR Paascongres 2017 Widget Class
 * 
 * @since 0.4.0
 */
class VGSR_Paascongres2017_Widget extends WP_Widget {

	/**
	 * Construct the widget
	 *
	 * @since 0.4.0
	 */
	public function __construct() {
		parent::__construct(
			'vgsr-paascongres2017',
			__( 'VGSR Paascongres 2017', 'vgsr-widgets' ),
			array(
				'description' => __( 'Paascongres 2017 logo, linked.', 'vgsr-widgets' ),
				'classname'   =>  'vgsr-paascongres2017'
			)
		);
	}

	/**
	 * Output the widget's display contents
	 *
	 * @since 0.4.0
	 *
	 * @param array $args Sidebar markup arguments
	 * @param array $instance Widget's instance settings
	 */
	public function widget( $args, $instance ) {

		// Wrap widget
		echo $args['before_widget'];

		// Parse defaults
		$instance = $this->parse_defaults( $instance );

		// Define the image link url
		if ( $instance['site_id'] && $site = get_site( $instance['site_id'] ) ) {
			$url = $site->siteurl;
		} else {
			$url = 'http://www.paascongres.nl';
		}

		// Display the logo. Use data-uri for image
		printf( '<a href="%s" target="_blank" style="display: inline-block; padding: 25px 0; width: 100%%; background: #fff; border: 0; box-shadow: none; vertical-align: bottom; text-align: center;" title="%s">%s</a>', esc_url( $url ), esc_attr__( 'Paascongres 2017', 'vgsr-widgets' ), '<img alt="Logo Paascongres 2017" src="' . $this->image_data_uri() . '" />' );

		/**
		 * @see WP_Widget_Text
		 */
		echo wpautop( apply_filters( 'widget_text', $instance['text'], $instance, $this ) );

		// End wrap widget
		echo $args['after_widget'];
	}

	/**
	 * Output the widget's form contents
	 *
	 * @since 0.4.0
	 *
	 * @param array $instance Widget's instance settings
	 */
	public function form( $instance ) {

		// Parse defaults
		$instance = $this->parse_defaults( $instance ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'site_id' ); ?>"><?php esc_html_e( 'Link destination', 'vgsr-widgets' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'site_id' ); ?>" name="<?php echo $this->get_field_name( 'site_id' ); ?>">
				<option value="">&mdash; www.paascongres.nl &mdash;</option>

				<?php foreach ( get_sites( array( 'network_id' => get_network()->id ) ) as $site ) : ?>

				<option value="<?php echo $site->id; ?>" <?php selected( $site->id, $instance['site_id'] ); ?>><?php echo $site->blogname; ?></option>

				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'text' ); ?>"><?php esc_html_e( 'Content', 'vgsr-widgets' ); ?>:</label>
			<textarea class="widefat" rows="3" cols="20" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo esc_textarea( $instance['text'] ); ?></textarea>
		</p>

		<?php
	}

	/**
	 * Update the widget's settings
	 *
	 * @since 0.4.0
	 * 
	 * @param array $new_instance New settings
	 * @param array $old_instance Old settings
	 * @return array Updated settings
	 */
	public function update( $new_instance, $old_instance ) {

		// Parse settings
		$new_instance['site_id'] = get_site( $new_instance['site_id'] ) ? $new_instance['site_id'] : 0;
		$new_instance['text']    = ! empty( $new_instance['text'] ) ? $new_instance['text'] : '';

		return $new_instance;
	}

	/**
	 * Parse the widget's default instance parameters
	 *
	 * @since 0.4.1
	 *
	 * @param array $instance Instance data
	 * @return array Parsed instance data
	 */
	public function parse_defaults( $instance = array() ) {
		return wp_parse_args( $instance, array(
			'site_id' => false,
			'text'    => '',
		) );
	}

	/**
	 * Return the widget's image in data-uri format
	 *
	 * @since 0.4.1
	 *
	 * @return string Image data uri
	 */
	public function image_data_uri() {
		return "data:image/png;base64,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";		
	}
}
