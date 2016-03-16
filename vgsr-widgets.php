<?php

/**
 * The VGSR Widgets Plugin
 * 
 * @package VGSR Widgets
 * @subpackage Main
 */

/**
 * Plugin Name:       VGSR Widgets
 * Description:       A variety of custom widgets for vgsr.nl
 * Plugin URI:        https://github.com/vgsr/vgsr-widgets/
 * Version:           1.0.0
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       vgsr-widgets
 * Domain Path:       /languages/
 * GitHub Plugin URI: vgsr/vgsr-widgets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Widgets' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class VGSR_Widgets {

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Widgets::setup_globals()
	 * @uses VGSR_Widgets::setup_actions()
	 * @return The single VGSR_Widgets
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new VGSR_Widgets;
			$instance->setup_globals();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version        = '1.0.0';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file           = __FILE__;
		$this->basename       = plugin_basename( $this->file );
		$this->plugin_dir     = plugin_dir_path( $this->file );
		$this->plugin_url     = plugin_dir_url ( $this->file );

		// Widgets
		$this->widgets_dir    = trailingslashit( $this->plugin_dir . 'widgets' );
		$this->widgets_url    = trailingslashit( $this->plugin_url . 'widgets' );

		// Shortcodes
		$this->shortcodes_dir = trailingslashit( $this->plugin_dir . 'shortcodes' );
		$this->shortcodes_url = trailingslashit( $this->plugin_url . 'shortcodes' );

		// Languages
		$this->lang_dir       = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->extend         = new stdClass();
		$this->domain         = 'vgsr-widgets';
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Register widgets
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		// Register shortcodes
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/vgsr-widgets/' . $mofile;

		// Look in global /wp-content/languages/vgsr-widgets folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/vgsr-widgets/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/** Widgets *********************************************************/

	/**
	 * Return our widget class names
	 *
	 * @since 1.0.0
	 * 
	 * @return array Widget class names
	 */
	public function widgets() {
		return array(
			'VGSR_Ketel1_Widget',
			'VGSR_Latest_Post_Widget',
		);
	}

	/**
	 * Register our widgets
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Widgets::widgets()
	 * @uses register_widget()
	 */
	public function register_widgets() {

		// Walk our widgets
		foreach ( $this->widgets() as $class_name ) {

			// Define widget file location
			$file = $this->widgets_dir . 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

			// Load widget file
			if ( file_exists( $file ) ) {
				require_once( $file );
			}

			// Register widget
			if ( class_exists( $class_name ) ) {
				register_widget( $class_name );
			}
		}
	}

	/** Shortcodes ******************************************************/

	/**
	 * Return our shortcode class names
	 *
	 * @since 1.0.0
	 *
	 * @return array Widget class names
	 */
	public function shortcodes() {
		return array(
			'VGSR_Shortcode_Subpages'
		);
	}

	/**
	 * Register our shortcodes
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Widgets::shortcodes()
	 * @uses register_widget()
	 */
	public function register_shortcodes() {

		// Require base class
		require_once( $this->shortcodes_dir . 'class-vgsr-shortcode.php' );

		// Walk our shortcodes
		foreach ( $this->shortcodes() as $class_name ) {

			// Define widget file location
			$file = $this->shortcodes_dir . 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

			// Load widget file
			if ( file_exists( $file ) ) {
				require_once( $file );
			}

			// Register widget
			if ( class_exists( $class_name ) ) {
				$shortcode = new $class_name;
				$shortcode->register();
			}
		}
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return VGSR_Widgets
 */
function vgsr_widgets() {
	return VGSR_Widgets::instance();
}

// Initiate
vgsr_widgets();

endif; // class_exists
