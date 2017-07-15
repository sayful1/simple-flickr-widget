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

if ( ! class_exists('Flickr_Photos_Gallery') ):

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

	public function __construct()
	{
		// define constants
		$this->define_constants();

		// include files
		$this->include_files();

		add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
		add_action('wp_footer', array( $this, 'wp_footer' ));
		
		add_shortcode('flickr_photos_gallery', array( $this, 'flickr_gallery' ) );
	}

	private function define_constants()
	{
		$this->define( 'FPG_VERSION', '1.3.0' );
		$this->define( 'FPG_FILE', __FILE__ );
        $this->define( 'FPG_PATH', dirname( FPG_FILE ) );
        $this->define( 'FPG_INCLUDES', FPG_PATH . '/includes' );
        $this->define( 'FPG_TEMPLATES', FPG_PATH . '/templates' );
        $this->define( 'FPG_URL', plugins_url( '', FPG_FILE ) );
        $this->define( 'FPG_ASSETS', FPG_URL . '/assets' );
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

	private function include_files() {
		include_once FPG_INCLUDES . '/simple-flickr-widget.php';
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts(){
		if ( ! $this->should_load_scripts() ) return;

	    wp_enqueue_style( 'client-testimonials', FPG_ASSETS . '/css/style.css', array(), FPG_VERSION, 'all' );
	    wp_enqueue_script( 'photoswipe', FPG_ASSETS . '/js/photoswipe.min.js', array(), '4.1.2', true );
	    wp_enqueue_script( 'photoswipe-ui-default', FPG_ASSETS . '/js/photoswipe-ui-default.min.js', array( 'photoswipe' ), '4.1.2', true );
	    wp_enqueue_script( 'client-testimonials', FPG_ASSETS . '/js/scripts.js', array( 'jquery', 'photoswipe' ), FPG_VERSION, true );
	}

	public function wp_footer()
	{
		if ( ! $this->should_load_scripts() ) return;

		include_once FPG_TEMPLATES . '/pswp.php';
	}

	/**
	 * Check if it should load frontend scripts
	 *
	 * @return mixed|void
	 */
	private function should_load_scripts() {
		global $post;
		$load_scripts = is_active_widget( false, false, 'flickr_widget', true ) || ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'flickr_photos_gallery' ) );

		return apply_filters( 'fpg_load_scripts', $load_scripts );
	}


	/**
	 * A shortcode for rendering the client testimonials slide.
	 *
	 * @param  array   $atts  		Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function flickr_gallery( $atts, $content = null )
	{
		$defaults = array(
            'api_key' 			=> '',
            'user_id' 			=> '',
            'per_page' 			=> 20,
            'mobile' 			=> 1,
            'tablet' 			=> 2,
            'desktop' 			=> 3,
            'widescreen' 		=> 4,
            'fullhd' 			=> 6,
	    );
	    $atts = wp_parse_args( $atts, $defaults );
		extract( shortcode_atts( $defaults, $atts ) );

		if ( empty( $api_key ) ) {
			return 'API Key not set.';
		}

		if ( empty( $user_id ) ) {
			return 'User ID not set.';
		}

		ob_start();
	    require FPG_TEMPLATES . '/flickr_photos_gallery.php';
	    $html = ob_get_contents();
	    ob_end_clean();
	    return apply_filters( 'flickr_photos_gallery', $html );
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
