<?php
/**!
 * Plugin Name: Simple Flickr Widget
 * Plugin URI: https://wordpress.org/plugins/simple-flickr-widget/
 * Description: A WordPress widget to display your latest Flickr photos.
 * Version: 1.2.0
 * Author: Sayful Islam
 * Author URI: https://sayfulislam.com
 * License: GPL2
 */

if ( ! class_exists( 'Flickr_Photos_Gallery' ) ):

	class Flickr_Photos_Gallery {

		/**
		 * The single instance of the class.
		 */
		protected static $_instance = null;

		/**
		 * Main Flickr_Photos_Gallery Instance.
		 * Ensures only one instance of Flickr_Photos_Gallery is loaded or can be loaded.
		 *
		 * @return Flickr_Photos_Gallery - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Flickr_Photos_Gallery constructor.
		 */
		public function __construct() {
			// define constants
			$this->define_constants();

			// include files
			$this->include_files();

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_footer', array( $this, 'wp_footer' ) );
			add_action( 'wp_head', array( $this, 'inline_style' ), 10 );
		}

		/**
		 * Define constants
		 */
		private function define_constants() {
			define( 'FPG_VERSION', '1.3.0' );
			define( 'FPG_FILE', __FILE__ );
			define( 'FPG_PATH', dirname( FPG_FILE ) );
			define( 'FPG_INCLUDES', FPG_PATH . '/includes' );
			define( 'FPG_TEMPLATES', FPG_PATH . '/templates' );
			define( 'FPG_URL', plugins_url( '', FPG_FILE ) );
			define( 'FPG_ASSETS', FPG_URL . '/assets' );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include files
		 */
		private function include_files() {
			include_once FPG_INCLUDES . '/class-flickr-photos-gallery-shortcode.php';
			include_once FPG_INCLUDES . '/class-flickr-photos-gallery-structured-data.php';
			include_once FPG_INCLUDES . '/simple-flickr-widget.php';
		}

		/**
		 * Enqueue scripts
		 */
		public function enqueue_scripts() {
			if ( ! $this->should_load_scripts() ) {
				return;
			}

			wp_enqueue_style( 'simple-flickr-widget', FPG_ASSETS . '/scss/style.css', array(), FPG_VERSION, 'all' );
			wp_enqueue_script( 'photoswipe', FPG_ASSETS . '/js/photoswipe.min.js', array(), '4.1.2', true );
			wp_enqueue_script( 'photoswipe-ui-default', FPG_ASSETS . '/js/photoswipe-ui-default.min.js', array( 'photoswipe' ), '4.1.2', true );
			wp_enqueue_script( 'simple-flickr-widget', FPG_ASSETS . '/js/scripts.js', array(
				'jquery',
				'photoswipe'
			), FPG_VERSION, true );
		}

		public function inline_style() {
			if ( ! $this->should_load_scripts() ) {
				return;
			}
			$gallery = file_get_contents( FPG_ASSETS . '/css/gallery.css' );
			echo sprintf(
				     '<style type="text/css">%s</style>',
				     wp_strip_all_tags( $gallery )
			     ) . "\n";
		}

		public function wp_footer() {
			if ( ! $this->should_load_scripts() ) {
				return;
			}

			include_once FPG_TEMPLATES . '/pswp.php';
		}

		/**
		 * Check if it should load frontend scripts
		 *
		 * @return mixed
		 */
		private function should_load_scripts() {
			global $post;
			$load_scripts = is_active_widget( false, false, 'flickr_widget', true ) || ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'flickr_photos_gallery' ) );

			return apply_filters( 'fpg_load_scripts', $load_scripts );
		}
	}

endif;

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
Flickr_Photos_Gallery::instance();
