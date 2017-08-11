<?php
if ( ! class_exists( 'Flickr_Photos_Gallery_Structured_Data' ) ):

	class Flickr_Photos_Gallery_Structured_Data {

		/**
		 * The single instance of the class.
		 */
		protected static $_instance = null;
		private $_image_data = array();

		/**
		 * Main Flickr_Photos_Gallery_Structured_Data Instance.
		 * Ensures only one instance of Flickr_Photos_Gallery_Structured_Data is loaded or can be loaded.
		 *
		 * @return Flickr_Photos_Gallery_Structured_Data - Main instance.
		 */
		public static function init() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Flickr_Photos_Gallery_Structured_Data constructor.
		 */
		public function __construct() {
			add_action( 'simple_flickr_widget_loop', array( $this, 'generate_image_data' ), 10, 4 );
			// Output structured data.
			add_action( 'wp_footer', array( $this, 'output_structured_data' ), 10 );
		}

		/**
		 * Output structured data to to site footer
		 */
		public function output_structured_data() {
			$gallery_data = $this->get_structured_image_data();
			if ( $gallery_data ) {
				echo '<script type="application/ld+json">' . wp_json_encode( $gallery_data ) . '</script>' . "\n";
			}
		}

		/**
		 * Sets data.
		 *
		 * @param  array $data Structured data.
		 *
		 * @return bool
		 */
		public function set_data( $data ) {
			if ( ! isset( $data['@type'] ) || ! preg_match( '|^[a-zA-Z]{1,20}$|', $data['@type'] ) ) {
				return false;
			}

			if ( $data['@type'] == 'ImageObject' ) {
				if ( ! $this->maybe_image_added( $data['contentUrl'] ) ) {
					$this->_image_data[] = $data;
				}
			}

			return true;
		}

		/**
		 * Check if image is already added to list
		 *
		 * @param  string $image_id
		 *
		 * @return boolean
		 */
		private function maybe_image_added( $image_id = null ) {
			$image_data = $this->get_image_data();
			if ( count( $image_data ) > 0 ) {
				$image_data = array_map( function ( $data ) {
					return $data['contentUrl'];
				}, $image_data );

				return in_array( $image_id, $image_data );
			}

			return false;
		}

		/**
		 * Get image data
		 *
		 * @return array
		 */
		public function get_image_data() {
			return $this->_image_data;
		}

		/**
		 * Structures and returns image data.
		 * @return array
		 */
		public function get_structured_image_data() {
			$data = array(
				'@context'        => 'http://schema.org/',
				"@type"           => "ImageGallery",
				"associatedMedia" => $this->get_image_data()
			);

			return $this->get_image_data() ? $data : array();
		}


		/**
		 * Generates Image structured data.
		 *
		 * @param $photo
		 * @param $content_url
		 * @param $thumbnail
		 * @param $title
		 */
		public function generate_image_data( $photo, $content_url, $thumbnail, $title ) {
			$markup['@type']      = 'ImageObject';
			$markup['contentUrl'] = $content_url;
			$markup['thumbnail']  = $thumbnail;
			$markup['name']       = $title;

			$this->set_data( apply_filters( 'simple_flickr_widget_structured_data', $markup, $photo ) );
		}
	}

endif;

Flickr_Photos_Gallery_Structured_Data::init();
