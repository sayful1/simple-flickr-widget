<?php

class Simple_Flickr_Widget extends WP_Widget {

	private $widget_id;

	/**
	 * Register widget with WordPress.
	 */
	public function __construct()
	{
		$this->widget_id 	= 'flickr_widget';
		$widget_name 		= __( 'Simple Flickr Widget', 'simple-flickr-widget' );
		$widget_options 	= array(
			'classname' 	=> 'simple_flicker_widget',
			'description' 	=> __( 'Display your latest Flickr photos.', 'simple-flickr-widget' ),
		);

		parent::__construct( $this->widget_id, $widget_name, $widget_options );

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}

	public function flush_widget_cache() {
		wp_cache_delete( $this->widget_id );
	}

	function widget( $args, $instance ) {

		$cache = wp_cache_get( $this->widget_id );

		if ( $cache ){
			echo $cache;
			return;
		}

		ob_start();

		extract( $args );
		
		$title 		= isset($instance['title']) ? esc_attr( $instance['title'] ) : null;
		$flickr_id 	= isset($instance['flickr_id']) ? esc_attr($instance['flickr_id']) : null;
		$number 	= isset($instance['number']) ? absint($instance['number']) : 9;
		$row_number = isset($instance['row_number']) ? absint($instance['row_number']) : 3;
		$g_img_size = isset($instance['gallery_img_size']) ? esc_attr($instance['gallery_img_size']) : 'q';

		if ( $g_img_size == '-') {
			$g_imgsize = '.';
		}else {
			$g_imgsize = '_' . $g_img_size . '.';
		}
		
		include_once(ABSPATH . WPINC . '/feed.php');

		$rss = fetch_feed('http://api.flickr.com/services/feeds/photos_public.gne?ids='.$flickr_id.'&lang=en-us&format=rss_200');

		add_filter( 'wp_feed_cache_transient_lifetime', function(){
			return 1800;
		});

		if( is_wp_error( $rss ) ){
			return;
		}

		// Figure out how many total items there are, but limit it to 5. 
	    $max_items = $rss->get_item_quantity( $number );
	    // Build an array of all the items, starting with element 0 (first element).
		$items = $rss->get_items( 0, $max_items );

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;
		?>
		<div class="simple_flicker_widget-row">
		<?php
			if ( isset( $items ) ) {
				foreach( $items as $item ) {
					// thumbnail, 
					$image_group = $item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
					$image_attrs = $image_group[0]['attribs'];
					foreach( $image_attrs as $image ) {

						$_img_src 		= $image['url'];
						$_img_src 		= str_replace('http://', 'https://', $_img_src );
						$_img_src 		= str_replace('_s.', $g_imgsize, $_img_src );
						$_img_width 	= intval( $image['width'] );
						$_img_height 	= intval( $image['height'] );

						echo sprintf('<div class="simple_flicker_widget-col col%1$s"><a target="_blank" href="%2$s"><img src="%3$s" width="%4$s" height="%5$s" alt="%6$s"></a></div>'
							,$row_number
							,$item->get_permalink()
							,$_img_src
							,$_img_width
							,$_img_height
							,$item->get_title()
						);
					}
				}
			}
		?>
		</div>
		<?php
		echo $after_widget;
		$content = ob_get_clean();
		wp_cache_set( $this->widget_id, $content );
		echo $content;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title'] 		= sanitize_text_field( $new_instance['title'] );
		$instance['flickr_id'] 	= sanitize_text_field( $new_instance['flickr_id'] );
		$instance['number'] 	= absint( $new_instance['number'] );
		$instance['row_number'] = absint( $new_instance['row_number'] );
		$instance['gallery_img_size'] = sanitize_text_field( $new_instance['gallery_img_size'] );

		$this->flush_widget_cache();

		return $instance;
	}

	function form( $instance ){
		$defaults = array(
			'title'        		=> __( 'Flickr Photos', 'simple-flickr-widget' ),
			'flickr_id'    		=> '',
			'number' 			=> 9,
			'row_number' 		=> 3,
			'gallery_img_size' 	=> 'q',
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
			<label for="<?php echo $this->get_field_id('title'); ?>">
				<?php esc_html_e( 'Title:', 'simple-flickr-widget' ); ?>
			</label>
			<input
				type="text"
				class="widefat"
				id="<?php echo $this->get_field_id('title'); ?>"
				name="<?php echo $this->get_field_name('title'); ?>"
				value="<?php echo $instance['title']; ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('flickr_id'); ?>">
				<?php esc_html_e( 'Your Flickr User ID:', 'simple-flickr-widget' ); ?>
			</label>
			<input
				type="text"
				class="widefat"
				id="<?php echo $this->get_field_id('flickr_id'); ?>"
				name="<?php echo $this->get_field_name('flickr_id'); ?>"
				value="<?php echo $instance['flickr_id']; ?>">
			<span class="description">
				<?php echo sprintf( __( 'Head over to %s to find your Flickr user ID.', 'simple-flickr-widget' ), '<a href="//idgettr.com" target="_blank" rel="nofollow">idgettr</a>' ); ?>
			</span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>">
				<?php esc_html_e( 'Total Number of photos to show:', 'simple-flickr-widget' ); ?>
			</label>
			<input
				type="number"
				class="widefat"
				id="<?php echo $this->get_field_id('number'); ?>"
				name="<?php echo $this->get_field_name('number'); ?>"
				value="<?php echo $instance['number']; ?>">
			<span class="description">
				<?php echo __( 'Set how many photos you want to show. Flickr seems to limit its feeds to 20. So you can use maximum 20 photos.', 'simple-flickr-widget' ); ?>
			</span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('row_number'); ?>">
				<?php esc_html_e( 'Number of photos to show per column:', 'simple-flickr-widget' ); ?>
			</label>
			<input
				type="number"
				class="widefat"
				id="<?php echo $this->get_field_id('row_number'); ?>"
				name="<?php echo $this->get_field_name('row_number'); ?>"
				value="<?php echo $instance['row_number']; ?>">
			<span class="description">
				<?php echo __( 'Set how many photos you want to show in a row. You can use minimum 1 photo and maximum 6 photos.', 'simple-flickr-widget' ); ?>
			</span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'gallery_img_size' ); ?>">
				Gallery Image Size
			</label>
			<select
				class="widefat"
				id="<?php echo $this->get_field_id( 'gallery_img_size' ); ?>"
				name="<?php echo $this->get_field_name( 'gallery_img_size' ); ?>"
			>
				<?php
					foreach ( $image_sizes as $size => $label ){
						$selected = $instance['gallery_img_size'] == $size ? 'selected' : '';
						echo sprintf('<option value="%1$s" %3$s>%2$s</option>', $size, $label, $selected );
					}
				?>
			</select>
		</p>
		<?php
	}
}

add_action( 'widgets_init', function(){ register_widget( "Simple_Flickr_Widget" );});
