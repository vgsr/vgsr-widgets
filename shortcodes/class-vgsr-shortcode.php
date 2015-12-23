<?php

/**
 * VGSR Shortcode Base Class
 * 
 * @package VGSR Widgets
 * @subpackage Shortcodes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Shortcode' ) ) :
/**
 * The VGSR Shortcode Base Class
 *
 * @since 1.0.0
 */
abstract class VGSR_Shortcode {

	/**
	 * Holds the unique shortcode name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $name = '';

	/**
	 * Holds the available shortcode attributes
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $atts = array();

	/**
	 * Holds the arguments for the Shortcode UI
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $args = array();

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 *
	 * @uses shortcode_exists()
	 * @uses VGSR_Shortcode::setup_actions()
	 */
	public function __construct( $name, $atts = array(), $ui_args = array() ) {

		// Bail when the shortcode already exists or is not callable
		if ( shortcode_exists( $name ) )
			return;

		// Shortcode details
		$this->name = $name;

		// Parse attribute data
		foreach ( (array) $atts as $k => $att ) {
			$this->atts[ $k ] = wp_parse_args( $att, array(
				'label'       => ucfirst( $k ),
				'description' => '',
				'type'        => 'text',
				'default'     => false,
				'placeholder' => '',
				'required'    => false,
				'options'     => false
			) );
		}

		// Shortcode UI args
		$this->args = wp_parse_args( $ui_args, array(
			'label'         => __( 'Shortcode', 'vgsr-widgets' ),
			'listItemImage' => 'dashicons-sticky',
		) );
	}

	/**
	 * Register shortcode logic
	 *
	 * @since 1.0.0
	 *
	 * @uses add_shortcode()
	 * @uses VGSR_Shortcode::add_shortcode_ui()
	 */
	public function register() {

		// Add the shortcode
		add_shortcode( $this->name, array( $this, '_shortcode' ) );

		// Support Shortcode UI
		$this->add_shortcode_ui();
	}

	/**
	 * Sanitize attributes, then run the shortcode callback
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Shortcode::normalize_atts()
	 * @uses shortcode_atts()
	 * @uses VGSR_Shortcode::shortcode()
	 * @param array $atts Shortcode instance attributes
	 */
	public function _shortcode( $atts ) {

		// First sanitize and default the attributes
		$atts = $this->normalize_atts( $atts );
		$atts = shortcode_atts( wp_list_pluck( $this->atts, 'default' ), $atts, $this->name );

		// Then run the real shortcode logic
		return $this->shortcode( $atts );
	}

	/**
	 * The shortcode callback
	 *
	 * Overwrite this method in a child class.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes
	 */
	abstract public function shortcode( $atts = array() );

	/**
	 * Return a normalized array of attributes
	 *
	 * Valueless shortcode attributes by default are collected as values without
	 * an attribute (name). This needs a correction, so that they are interpreted
	 * as actual attribute names with a 'true' value.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode instace attributes
	 * @return array Normalized attributes
	 */
	private function normalize_atts( $atts ) {

		// Bail when there are no attributes
		if ( empty( $atts ) )
			return array();

		$_atts = array();
		foreach ( $atts as $k => $v ) {

			// An unknown numeric attribute = attribute without value
			if ( is_numeric( $k ) && ! array_key_exists( $k, $this->atts ) ) {
				$_atts[ strtolower( $v ) ] = true;
			} else {
				$_atts[ $k ] = $v;
			}
		}

		return $_atts;
	}

	/**
	 * Setup support for Shortcode UI
	 *
	 * @since 1.0.0
	 *
	 * @uses shortcode_ui_register_for_shortcode()
	 */
	private function add_shortcode_ui() {

		// Bail when Shortcode UI is not available
		if ( ! class_exists( 'Shortcode_UI' ) )
			return;

		// Define UI args
		$args         = $this->args;
		$args['atts'] = $this->atts;

		// Register for UI
		shortcode_ui_register_for_shortcode( $this->name, $args );

		// Shortcode has a stylesheet
		$stylesheet = 'assets/css/' . strtolower( str_replace( '_', '-', get_class( $this ) ) ) . '.css';
		if ( file_exists( vgsr_widgets()->plugin_dir . $stylesheet ) ) {

			// Enqueue editor stylesheet
			add_filter( 'editor_stylesheets', function( $stylesheets ) use ( $stylesheet ) {
				$stylesheets[] = vgsr_widgets()->plugin_url . $stylesheet;
				return $stylesheets;
			} );
		}
	}
}

endif; // class_exists
