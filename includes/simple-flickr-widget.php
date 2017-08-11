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

		$cache = wp_cache_get( $this->widget_id );

		if ( $cache ) {
			echo $cache;

			return;
		}

		extract( $args );

		$title      = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : null;
		$flickr_id  = isset( $instance['flickr_id'] ) ? esc_attr( $instance['flickr_id'] ) : null;
		$number     = isset( $instance['number'] ) ? absint( $instance['number'] ) : 20;
		$row_number = isset( $instance['row_number'] ) ? absint( $instance['row_number'] ) : 3;

		$api_key        = isset( $instance['api_key'] ) ? esc_attr( $instance['api_key'] ) : '';
		$mobile         = isset( $instance['mobile'] ) ? absint( $instance['mobile'] ) : 1;
		$tablet         = isset( $instance['tablet'] ) ? absint( $instance['tablet'] ) : 1;
		$desktop        = isset( $instance['desktop'] ) ? absint( $instance['desktop'] ) : 1;
		$widescreen     = isset( $instance['widescreen'] ) ? absint( $instance['widescreen'] ) : 1;
		$fullhd         = isset( $instance['fullhd'] ) ? absint( $instance['fullhd'] ) : 1;
		$is_responsive  = isset( $instance['is_responsive'] ) ? esc_attr( $instance['is_responsive'] ) : 'no';
		$g_img_size     = isset( $instance['gallery_img_size'] ) ? esc_attr( $instance['gallery_img_size'] ) : 'q';
		$modal_img_size = isset( $instance['modal_img_size'] ) ? esc_attr( $instance['modal_img_size'] ) : 'q';

		ob_start();

		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		if ( empty( $api_key ) ) {
			echo $this->feed_html( $flickr_id, $number, $row_number, $g_img_size );
		}

		echo $after_widget;
		$content = ob_get_clean();
		wp_cache_set( $this->widget_id, $content );
		echo $content;
	}

	private function feed_html( $user_id, $per_page, $columns, $img_size ) {
		$items = $this->flickr_public_feed( $user_id, $per_page );
		$class = sprintf( 'fpg-columns m%s', $columns );

		if ( $img_size == '-' ) {
			$g_imgsize = '.';
		} else {
			$g_imgsize = '_' . $img_size . '.';
		}

		$html = '';
		if ( $items ) {
			$html .= '<div class="' . $class . '" itemscope itemtype="http://schema.org/ImageGallery">';
			foreach ( $items as $item ) {
				$_img_src = $item['src'];
				$_img_src = str_replace( '_s.', $g_imgsize, $_img_src );

				$html .= '<figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">';
				$html .= sprintf( '<a target="_blank" href="%1$s"><img src="%2$s" alt="%3$s"></a>'
					, $item['permalink']
					, $_img_src
					, $item['alt']
				);
				$html .= '</figure>';
			}
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

				$_img_src    = $image['url'];
				$_img_src    = str_replace( 'http://', 'https://', $_img_src );
				$_img_width  = intval( $image['width'] );
				$_img_height = intval( $image['height'] );

				$data[ $i ]['alt']       = esc_attr( $item->get_title() );
				$data[ $i ]['src']       = esc_url( $_img_src );
				$data[ $i ]['permalink'] = esc_url( $item->get_permalink() );
			}

			$i ++;
		}

		return $data;
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

		?>
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
				<?php esc_html_e( 'Your Flickr User ID:', 'simple-flickr-widget' ); ?>
            </label>
            <input
                    type="text"
                    class="widefat"
                    id="<?php echo $this->get_field_id( 'flickr_id' ); ?>"
                    name="<?php echo $this->get_field_name( 'flickr_id' ); ?>"
                    value="<?php echo $instance['flickr_id']; ?>">
            <span class="description">
				<?php echo sprintf( __( 'Head over to %s to find your Flickr user ID.', 'simple-flickr-widget' ), '<a href="//idgettr.com" target="_blank" rel="nofollow">idgettr</a>' ); ?>
			</span>
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
            <span class="description">
				<?php echo __( 'Set how many photos you want to show. Flickr seems to limit its feeds to 20. So you can use maximum 20 photos.', 'simple-flickr-widget' ); ?>
			</span>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'row_number' ); ?>">
				<?php esc_html_e( 'Number of photos to show per column:', 'simple-flickr-widget' ); ?>
            </label>
            <input
                    type="number"
                    class="widefat"
                    id="<?php echo $this->get_field_id( 'row_number' ); ?>"
                    name="<?php echo $this->get_field_name( 'row_number' ); ?>"
                    value="<?php echo $instance['row_number']; ?>">
            <span class="description">
				<?php esc_html_e( 'Set how many photos you want to show in a row. You can use minimum 1 photo and maximum 6 photos.', 'simple-flickr-widget' ); ?>
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
		<?php
	}
}

add_action( 'widgets_init', function () {
	register_widget( "Simple_Flickr_Widget" );
} );
