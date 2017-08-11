<?php
if ( ! class_exists( 'Flickr_Photos_Gallery_Shortcode' ) ):

	class Flickr_Photos_Gallery_Shortcode {

		/**
		 * The single instance of the class.
		 */
		protected static $_instance = null;

		/**
		 * Main Flickr_Photos_Gallery_Shortcode Instance.
		 * Ensures only one instance of Flickr_Photos_Gallery_Shortcode is loaded or can be loaded.
		 *
		 * @return Flickr_Photos_Gallery_Shortcode - Main instance.
		 */
		public static function init() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function __construct() {
			add_shortcode( 'flickr_photos_gallery', array( $this, 'flickr_gallery' ) );
			// Enable shortcodes in text widgets
			add_filter( 'widget_text', 'do_shortcode' );
		}


		/**
		 * A shortcode for rendering the client testimonials slide.
		 *
		 * @param  array $atts Shortcode attributes.
		 * @param  string $content The text content for shortcode. Not used.
		 *
		 * @return string  The shortcode output
		 */
		public function flickr_gallery( $atts, $content = null ) {
			$defaults = array(
				'api_key'          => '',
				'user_id'          => '',
				'per_page'         => 20,
				'mobile'           => 1,
				'tablet'           => 2,
				'desktop'          => 3,
				'widescreen'       => 4,
				'fullhd'           => 6,
				'is_responsive'    => 'no',
				'columns'          => 3,
				'gutters'          => '2rem',
				'gallery_img_size' => 'n',
				'modal_img_size'   => 'o',
			);
			$atts     = wp_parse_args( $atts, $defaults );
			extract( $atts );

			if ( empty( $api_key ) ) {
				return 'API Key not set.';
			}

			if ( empty( $user_id ) ) {
				return 'User ID not set.';
			}

			$content = $this->get_flickr_photos( $atts['api_key'], $atts['user_id'], $atts['per_page'] );
			$photos  = isset( $content->photos->photo ) ? $content->photos->photo : array();

			$list_class = 'flickr_photos_gallery columns is-multiline';
			$item_class = 'column';
			$item_class .= ' is-' . $this->_grid( $atts['mobile'] ) . '-mobile';
			$item_class .= ' is-' . $this->_grid( $atts['tablet'] ) . '-tablet';
			$item_class .= ' is-' . $this->_grid( $atts['desktop'] ) . '-desktop';
			$item_class .= ' is-' . $this->_grid( $atts['widescreen'] ) . '-widescreen';

			if ( $atts['is_responsive'] == 'no' ) {
				$list_class = 'flickr_photos_gallery columns is-multiline is-mobile';
				$item_class = 'column is-' . $this->_grid( $atts['columns'] );
			}

			ob_start();
			require FPG_TEMPLATES . '/flickr_photos_gallery.php';
			$html = ob_get_contents();
			ob_end_clean();

			return apply_filters( 'flickr_photos_gallery', $html );
		}

		/**
		 * Get flickr photo url by photo size from photo object
		 *
		 * @param $photo
		 * @param $size
		 *
		 * @return string
		 */
		private function get_flickr_photo_url( $photo, $size ) {
			return sprintf(
				'https://farm%1$s.staticflickr.com/%2$s/%3$s_%4$s_%5$s.jpg',
				$photo->farm, $photo->server, $photo->id, $photo->secret, $size
			);
		}

		/**
		 * Get flickr photos by API Key and User ID
		 *
		 * @param string $api_key
		 * @param string $user_id
		 * @param int $per_page
		 *
		 * @return object|string
		 */
		private function get_flickr_photos( $api_key, $user_id, $per_page = 20 ) {
			$base_url = 'https://api.flickr.com/services/rest/?method=flickr.people.getPhotos';

			$url = add_query_arg( array(
				'api_key'        => $api_key,
				'user_id'        => $user_id,
				'per_page'       => $per_page,
				'extras'         => 'original_format,url_sq,url_t,url_s,url_q,url_m,url_n, url_z,url_c,url_l,url_o',
				'format'         => 'json',
				'nojsoncallback' => 1,
			), $base_url );

			$response = wp_remote_get( $url );
			if ( is_wp_error( $response ) ) {
				return $response->get_error_message();
			}

			$_body   = wp_remote_retrieve_body( $response );
			$content = json_decode( $_body );

			return $content;
		}

		/**
		 * Get flickr photo sizes
		 *
		 * @param bool $key_only
		 *
		 * @return array
		 */
		private function get_flickr_photo_sizes( $key_only = false ) {
			$image_sizes = array(
				's' => __( 'Small square 75x75', 'simple-flickr-widget' ),
				'q' => __( 'Large square 150x150', 'simple-flickr-widget' ),
				't' => __( 'Thumbnail, 100 on longest side', 'simple-flickr-widget' ),
				'm' => __( 'Small, 240 on longest side', 'simple-flickr-widget' ),
				'n' => __( 'Small, 320 on longest side', 'simple-flickr-widget' ),
				'-' => __( 'Medium, 500 on longest side', 'simple-flickr-widget' ),
				'z' => __( 'Medium 640, 640 on longest side', 'simple-flickr-widget' ),
				'c' => __( 'Medium 800, 800 on longest side', 'simple-flickr-widget' ),
				'b' => __( 'Large, 1024 on longest side', 'simple-flickr-widget' ),
				'h' => __( 'Large 1600, 1600 on longest side', 'simple-flickr-widget' ),
				'k' => __( 'Large 2048, 2048 on longest side', 'simple-flickr-widget' ),
			);

			if ( $key_only ) {
				return array_keys( $image_sizes );
			}

			return $image_sizes;
		}

		/**
		 * Convert column to grid
		 *
		 * @param int $number
		 *
		 * @return int
		 */
		private function _grid( $number = 0 ) {
			$number = intval( $number );

			if ( $number === 1 ) {
				return 12;
			}
			if ( $number === 2 ) {
				return 6;
			}
			if ( $number === 3 ) {
				return 4;
			}
			if ( $number === 4 ) {
				return 3;
			}
			if ( $number === 6 ) {
				return 2;
			}

			return 2;
		}
	}

endif;

Flickr_Photos_Gallery_Shortcode::init();
