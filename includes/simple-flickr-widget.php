<?php

class Simple_Flickr_Widget extends WP_Widget {

	private $widget_id;

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		$this->widget_id = 'flickr_widget';
		$widget_name     = __( 'Simple Flickr Widget', 'simple-flickr-widget' );
		$widget_options  = array(
			'classname'   => 'simple_flicker_widget',
			'description' => __( 'Display your latest Flickr photos.', 'simple-flickr-widget' ),
		);

		parent::__construct( $this->widget_id, $widget_name, $widget_options );

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}

	/**
	 * Echoes the widget content.
	 *
	 * @param array $args Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {

		$content = wp_cache_get( $this->widget_id );

		if ( false === $content ) {

			$title      = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : null;
			$flickr_id  = isset( $instance['flickr_id'] ) ? esc_attr( $instance['flickr_id'] ) : null;
			$number     = isset( $instance['number'] ) ? absint( $instance['number'] ) : 20;
			$row_number = isset( $instance['row_number'] ) ? absint( $instance['row_number'] ) : 3;

			$api_key        = isset( $instance['api_key'] ) ? esc_attr( $instance['api_key'] ) : '';
			$gutters        = isset( $instance['gutters'] ) ? esc_attr( $instance['gutters'] ) : '5px';
			$mobile         = isset( $instance['mobile'] ) ? absint( $instance['mobile'] ) : 1;
			$tablet         = isset( $instance['tablet'] ) ? absint( $instance['tablet'] ) : 2;
			$desktop        = isset( $instance['desktop'] ) ? absint( $instance['desktop'] ) : 3;
			$widescreen     = isset( $instance['widescreen'] ) ? absint( $instance['widescreen'] ) : 4;
			$fullhd         = isset( $instance['fullhd'] ) ? absint( $instance['fullhd'] ) : 6;
			$is_responsive  = isset( $instance['is_responsive'] ) ? esc_attr( $instance['is_responsive'] ) : 'no';
			$g_img_size     = isset( $instance['gallery_img_size'] ) ? esc_attr( $instance['gallery_img_size'] ) : 'q';
			$modal_img_size = isset( $instance['modal_img_size'] ) ? esc_attr( $instance['modal_img_size'] ) : 'b';

			$instance = array(
				'title'            => isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '',
				'flickr_id'        => isset( $instance['flickr_id'] ) ? esc_attr( $instance['flickr_id'] ) : '',
				'api_key'          => isset( $instance['api_key'] ) ? esc_attr( $instance['api_key'] ) : '',
				'columns'          => isset( $instance['row_number'] ) ? absint( $instance['row_number'] ) : 3,
				'total_images'     => isset( $instance['number'] ) ? absint( $instance['number'] ) : 20,
				'gutters'          => isset( $instance['gutters'] ) ? esc_attr( $instance['gutters'] ) : '5px',
				'gallery_img_size' => isset( $instance['gallery_img_size'] ) ? esc_attr( $instance['gallery_img_size'] ) : 'q',
				'modal_img_size'   => isset( $instance['modal_img_size'] ) ? esc_attr( $instance['modal_img_size'] ) : 'b',
				'is_responsive'    => isset( $instance['is_responsive'] ) ? esc_attr( $instance['is_responsive'] ) : 'no',
			);

			ob_start();

			echo $args['before_widget'];
			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . $instance['title'] . $args['after_title'];
			}

			if ( empty( $api_key ) ) {
				if ( current_user_can( 'manage_options' ) ) {
					printf(
						'<div style="background-color: #ffdddd;border-left: 0.375rem solid #f44336; margin-bottom: 1rem;
    margin-top: 1rem;padding: 0.01rem 1rem;"><p><strong>%s</strong><br>%s</p></div> ',
						esc_html__( 'Admin Only Notice!', 'simple-flickr-widget' ),
						esc_html__( 'From version 2.0.0, Simple Flickr Widget requires Flickr API key. Update your widget settings with api key.', 'simple-flickr-widget' )
					);
				}
				if ( $gutters ) {
					$gutter = $this->get_item_gutter( $gutters );
					echo '<style>';
					echo '#' . $args['widget_id'] . ' .columns{margin: -' . $gutter . '}';
					echo '#' . $args['widget_id'] . ' .column{padding: ' . $gutter . '}';
					echo '</style>';
				}
				echo $this->feed_html( $instance );
			}

			echo $args['after_widget'];
			$content = ob_get_clean();
			wp_cache_set( $this->widget_id, $content );
		}

		echo $content;
	}

	/**
	 * Get gallery item gutter
	 *
	 * @param $gutters
	 *
	 * @return string
	 */
	private function get_item_gutter( $gutters ) {
		$_gutters = floatval( $gutters ) / 2;
		if ( false !== strpos( $gutters, 'rem' ) ) {
			$suf = 'rem';
		} elseif ( false !== strpos( $gutters, 'em' ) ) {
			$suf = 'em';
		} elseif ( false !== strpos( $gutters, 'px' ) ) {
			$suf = 'px';
		} else {
			$suf = false;
		}
		$gutter = $_gutters . $suf;

		return $gutter;
	}

	private function feed_html( $atts ) {

		$photos     = $this->flickr_public_feed( $atts['flickr_id'], $atts['total_images'] );
		$list_class = 'flickr_photos_gallery columns is-multiline is-mobile';
		$item_class = sprintf( 'column is-%s', $atts['columns'] );

		$img_size = $atts['gallery_img_size'];
		if ( $img_size == '-' ) {
			$g_imgsize = '.';
		} else {
			$g_imgsize = '_' . $img_size . '.';
		}

		$modal_img_size = $atts['modal_img_size'];
		if ( $modal_img_size == '-' ) {
			$m_imgsize = '.';
		} else {
			$m_imgsize = '_' . $modal_img_size . '.';
		}

		$html = '';
		if ( $photos ) {
			$html .= '<div class="' . $list_class . '">';

			do_action( 'simple_flickr_widget_before_loop', $photos );

			foreach ( $photos as $photo ) {
				$img_src   = $photo['src'];
				$_img_src  = str_replace( '_s.', $g_imgsize, $img_src );
				$m_img_src = str_replace( '_s.', $m_imgsize, $img_src );

				$size   = $this->getjpegsize( $m_img_src );
				$width  = isset( $size[0] ) ? $size[0] : 0;
				$height = isset( $size[1] ) ? $size[1] : 0;

				$content_url = esc_url( $m_img_src );
				$thumbnail   = esc_url( $_img_src );
				$title       = esc_attr( $photo['alt'] );

				$html .= '<figure class="' . $item_class . '">';
				$html .= sprintf( '<a target="_blank" data-size="%4$s" href="%1$s"><img src="%2$s" alt="%3$s"></a>',
					$content_url,
					$thumbnail,
					$title,
					sprintf( '%sx%s', $width, $height )
				);
				$html .= '</figure>';

				do_action( 'simple_flickr_widget_loop', $photo, $content_url, $thumbnail, $title );
			}

			do_action( 'simple_flickr_widget_after_loop', $photos );

			$html .= '</div>';
		}

		return $html;
	}

	private function flickr_public_feed( $user_id, $per_page = 20 ) {
		include_once( ABSPATH . WPINC . '/feed.php' );

		$base_url = 'http://api.flickr.com/services/feeds/photos_public.gne';
		$url      = add_query_arg( array(
			'ids'    => $user_id,
			'lang'   => 'en-us',
			'format' => 'rss_200',
		), $base_url );

		$rss = fetch_feed( $url );

		if ( is_wp_error( $rss ) ) {
			return false;
		}

		// Figure out how many total items there are.
		$max_items = $rss->get_item_quantity( $per_page );

		// Build an array of all the items,
		// starting with element 0 (first element).
		$items = $rss->get_items( 0, $max_items );

		$data = array();

		$i = 0;
		foreach ( $items as $item ) {
			$image_group = $item->get_item_tags( 'http://search.yahoo.com/mrss/', 'thumbnail' );
			$image_attrs = $image_group[0]['attribs'];
			foreach ( $image_attrs as $image ) {

				$_img_src = $image['url'];
				$_img_src = str_replace( 'http://', 'https://', $_img_src );

				$data[ $i ]['alt']       = esc_attr( $item->get_title() );
				$data[ $i ]['src']       = esc_url( $_img_src );
				$data[ $i ]['permalink'] = esc_url( $item->get_permalink() );
			}

			$i ++;
		}

		return $data;
	}

	private function getjpegsize( $img_loc ) {
		$handle = fopen( $img_loc, "rb" ) or die( "Invalid file stream." );
		$new_block = null;
		if ( ! feof( $handle ) ) {
			$new_block = fread( $handle, 32 );
			$i         = 0;
			if ( $new_block[ $i ] == "\xFF" && $new_block[ $i + 1 ] == "\xD8" && $new_block[ $i + 2 ] == "\xFF" && $new_block[ $i + 3 ] == "\xE0" ) {
				$i += 4;
				if ( $new_block[ $i + 2 ] == "\x4A" && $new_block[ $i + 3 ] == "\x46" && $new_block[ $i + 4 ] == "\x49" && $new_block[ $i + 5 ] == "\x46" && $new_block[ $i + 6 ] == "\x00" ) {
					// Read block size and skip ahead to begin cycling through blocks in search of SOF marker
					$block_size = unpack( "H*", $new_block[ $i ] . $new_block[ $i + 1 ] );
					$block_size = hexdec( $block_size[1] );
					while ( ! feof( $handle ) ) {
						$i         += $block_size;
						$new_block .= fread( $handle, $block_size );
						if ( $new_block[ $i ] == "\xFF" ) {
							// New block detected, check for SOF marker
							$sof_marker = array(
								"\xC0",
								"\xC1",
								"\xC2",
								"\xC3",
								"\xC5",
								"\xC6",
								"\xC7",
								"\xC8",
								"\xC9",
								"\xCA",
								"\xCB",
								"\xCD",
								"\xCE",
								"\xCF"
							);
							if ( in_array( $new_block[ $i + 1 ], $sof_marker ) ) {
								// SOF marker detected. Width and height information is contained in bytes 4-7 after this byte.
								$size_data = $new_block[ $i + 2 ] . $new_block[ $i + 3 ] . $new_block[ $i + 4 ] . $new_block[ $i + 5 ] . $new_block[ $i + 6 ] . $new_block[ $i + 7 ] . $new_block[ $i + 8 ];
								$unpacked  = unpack( "H*", $size_data );
								$unpacked  = $unpacked[1];
								$height    = hexdec( $unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9] );
								$width     = hexdec( $unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13] );

								return array( $width, $height );
							} else {
								// Skip block marker and read block size
								$i          += 2;
								$block_size = unpack( "H*", $new_block[ $i ] . $new_block[ $i + 1 ] );
								$block_size = hexdec( $block_size[1] );
							}
						} else {
							return false;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * This function should check that `$new_instance` is set correctly. The newly-calculated
	 * value of `$instance` should be returned. If false is returned, the instance won't be
	 * saved/updated.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$old_instance['title']            = sanitize_text_field( $new_instance['title'] );
		$old_instance['flickr_id']        = sanitize_text_field( $new_instance['flickr_id'] );
		$old_instance['number']           = absint( $new_instance['number'] );
		$old_instance['row_number']       = absint( $new_instance['row_number'] );
		$old_instance['gallery_img_size'] = sanitize_text_field( $new_instance['gallery_img_size'] );
		$old_instance['modal_img_size']   = sanitize_text_field( $new_instance['modal_img_size'] );

		$this->flush_widget_cache();

		return $old_instance;
	}

	/**
	 * Flush widget cache
	 */
	public function flush_widget_cache() {
		wp_cache_delete( $this->widget_id );
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return string Default return is 'noform'.
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'            => __( 'Flickr Photos', 'simple-flickr-widget' ),
			'flickr_id'        => '',
			'number'           => 9,
			'row_number'       => 3,
			// New attributes
			'api_key'          => '',
			'mobile'           => 1,
			'tablet'           => 2,
			'desktop'          => 3,
			'widescreen'       => 4,
			'fullhd'           => 6,
			'is_responsive'    => 'no',
			'gallery_img_size' => 'q',
			'modal_img_size'   => 'o',
		);
		$instance = wp_parse_args( $instance, $defaults );

		$image_sizes = $this->_photo_sizes();

		?>
        <div id="simpleFlickrWidgetAdmin">
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>">
					<?php esc_html_e( 'Title:', 'simple-flickr-widget' ); ?>
                </label>
                <input
                        type="text"
                        class="widefat"
                        id="<?php echo $this->get_field_id( 'title' ); ?>"
                        name="<?php echo $this->get_field_name( 'title' ); ?>"
                        value="<?php echo $instance['title']; ?>">
            </p>

            <p>
                <label for="<?php echo $this->get_field_id( 'flickr_id' ); ?>">
					<?php esc_html_e( 'Flickr User ID:', 'simple-flickr-widget' ); ?>
                </label>
                <input
                        type="text"
                        class="widefat"
                        id="<?php echo $this->get_field_id( 'flickr_id' ); ?>"
                        name="<?php echo $this->get_field_name( 'flickr_id' ); ?>"
                        value="<?php echo $instance['flickr_id']; ?>">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'api_key' ); ?>">
					<?php esc_html_e( 'Flickr API Key:', 'simple-flickr-widget' ); ?>
                </label>
                <input
                        type="text"
                        class="widefat"
                        id="<?php echo $this->get_field_id( 'api_key' ); ?>"
                        name="<?php echo $this->get_field_name( 'api_key' ); ?>"
                        value="<?php echo $instance['api_key']; ?>">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'number' ); ?>">
					<?php esc_html_e( 'Total Number of photos to show:', 'simple-flickr-widget' ); ?>
                </label>
                <input
                        type="number"
                        class="widefat"
                        id="<?php echo $this->get_field_id( 'number' ); ?>"
                        name="<?php echo $this->get_field_name( 'number' ); ?>"
                        value="<?php echo $instance['number']; ?>">
                <span>
				    <?php esc_html_e( 'Set how many photos you want to show.', 'simple-flickr-widget' ); ?>
			    </span>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'gallery_img_size' ); ?>">
					<?php esc_html_e( 'Gallery Image Size:', 'simple-flickr-widget' ); ?>
                </label>
                <select
                        class="widefat"
                        id="<?php echo $this->get_field_id( 'gallery_img_size' ); ?>"
                        name="<?php echo $this->get_field_name( 'gallery_img_size' ); ?>"
                >
					<?php
					foreach ( $image_sizes as $size => $label ) {
						$selected = $instance['gallery_img_size'] == $size ? 'selected' : '';
						echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $size, $label, $selected );
					}
					?>
                </select>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'modal_img_size' ); ?>">
					<?php esc_html_e( 'Modal/Popup Image Size:', 'simple-flickr-widget' ); ?>
                </label>
                <select
                        class="widefat"
                        id="<?php echo $this->get_field_id( 'modal_img_size' ); ?>"
                        name="<?php echo $this->get_field_name( 'modal_img_size' ); ?>"
                >
					<?php
					foreach ( $image_sizes as $size => $label ) {
						$selected = $instance['modal_img_size'] == $size ? 'selected' : '';
						echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $size, $label, $selected );
					}
					?>
                </select>
            </p>
            <p class="column_is_responsive">
                <label for="<?php echo $this->get_field_id( 'is_responsive' ); ?>">
                    <input type="hidden" name="<?php echo $this->get_field_name( 'is_responsive' ); ?>" value="no">
                    <input
                            type="checkbox"
                            class="is_responsive_checked"
                            id="<?php echo $this->get_field_id( 'is_responsive' ); ?>"
                            name="<?php echo $this->get_field_name( 'is_responsive' ); ?>
                            <?php echo $instance['is_responsive'] == 'yes' ? 'checked' : ''; ?>
                            value=" yes">
					<?php esc_html_e( 'Use responsive gallery:', 'simple-flickr-widget' ); ?>
                </label>
            </p>
            <div class="no_responsive_column">
                <p>
                    <label for="<?php echo $this->get_field_id( 'row_number' ); ?>">
						<?php esc_html_e( 'Photos per column:', 'simple-flickr-widget' ); ?>
                    </label>
                    <select
                            class="widefat"
                            id="<?php echo $this->get_field_id( 'row_number' ); ?>"
                            name="<?php echo $this->get_field_name( 'row_number' ); ?>"
                    >
						<?php
						foreach ( $this->_columns() as $number => $title ) {
							$selected = $instance['row_number'] == $number ? 'selected' : '';
							echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $number, $title, $selected );
						}
						?>
                    </select>
                </p>
            </div>
            <div class="responsive_columns">
                <p>
                    <label for="<?php echo $this->get_field_id( 'mobile' ); ?>">
						<?php esc_html_e( 'Photos per column on mobile', 'simple-flickr-widget' ); ?>
                    </label>
                    <select
                            class="widefat"
                            id="<?php echo $this->get_field_id( 'mobile' ); ?>"
                            name="<?php echo $this->get_field_name( 'mobile' ); ?>"
                    >
						<?php
						foreach ( $this->_columns() as $number => $title ) {
							$selected = $instance['mobile'] == $number ? 'selected' : '';
							echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $number, $title, $selected );
						}
						?>
                    </select>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'tablet' ); ?>">
						<?php esc_html_e( 'Photos per column on tablet', 'simple-flickr-widget' ); ?>
                    </label>
                    <select
                            class="widefat"
                            id="<?php echo $this->get_field_id( 'tablet' ); ?>"
                            name="<?php echo $this->get_field_name( 'tablet' ); ?>"
                    >
						<?php
						foreach ( $this->_columns() as $number => $title ) {
							$selected = $instance['tablet'] == $number ? 'selected' : '';
							echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $number, $title, $selected );
						}
						?>
                    </select>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'desktop' ); ?>">
						<?php esc_html_e( 'Photos per column on desktop', 'simple-flickr-widget' ); ?>
                    </label>
                    <select
                            class="widefat"
                            id="<?php echo $this->get_field_id( 'desktop' ); ?>"
                            name="<?php echo $this->get_field_name( 'desktop' ); ?>"
                    >
						<?php
						foreach ( $this->_columns() as $number => $title ) {
							$selected = $instance['desktop'] == $number ? 'selected' : '';
							echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $number, $title, $selected );
						}
						?>
                    </select>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'widescreen' ); ?>">
						<?php esc_html_e( 'Photos per column on widescreen', 'simple-flickr-widget' ); ?>
                    </label>
                    <select
                            class="widefat"
                            id="<?php echo $this->get_field_id( 'widescreen' ); ?>"
                            name="<?php echo $this->get_field_name( 'widescreen' ); ?>"
                    >
						<?php
						foreach ( $this->_columns() as $number => $title ) {
							$selected = $instance['widescreen'] == $number ? 'selected' : '';
							echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $number, $title, $selected );
						}
						?>
                    </select>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'fullhd' ); ?>">
						<?php esc_html_e( 'Photos per column on fullhd', 'simple-flickr-widget' ); ?>
                    </label>
                    <select
                            class="widefat"
                            id="<?php echo $this->get_field_id( 'fullhd' ); ?>"
                            name="<?php echo $this->get_field_name( 'fullhd' ); ?>"
                    >
						<?php
						foreach ( $this->_columns() as $number => $title ) {
							$selected = $instance['fullhd'] == $number ? 'selected' : '';
							echo sprintf( '<option value="%1$s" %3$s>%2$s</option>', $number, $title, $selected );
						}
						?>
                    </select>
                </p>
            </div>
        </div>
		<?php
	}

	/**
	 * Get flickr photo sizes
	 *
	 * @param bool $key_only
	 *
	 * @return array
	 */
	private function _photo_sizes( $key_only = false ) {
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

	private function _columns( $key_only = false ) {
		$columns = array(
			'1' => esc_html__( 'One Photo', 'simple-flickr-widget' ),
			'2' => esc_html__( 'Two Photos', 'simple-flickr-widget' ),
			'3' => esc_html__( 'Three Photos', 'simple-flickr-widget' ),
			'4' => esc_html__( 'Four Photos', 'simple-flickr-widget' ),
			'6' => esc_html__( 'Six Photos', 'simple-flickr-widget' ),
		);

		if ( $key_only ) {
			return array_keys( $columns );
		}

		return $columns;
	}

	/**
	 * Convert column to grid
	 *
	 * @param int $number
	 *
	 * @return int
	 */
	private function _column_to_grid( $number = 3 ) {
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

		return 3;
	}
}

add_action( 'widgets_init', function () {
	register_widget( "Simple_Flickr_Widget" );
} );
