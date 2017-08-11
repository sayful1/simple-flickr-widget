<?php
echo '<div class="' . $list_class . '">';
do_action( 'simple_flickr_widget_before_loop', $photos );

foreach ( $photos as $photo ) {


	$content_url = esc_url( $photo->url_o );
	$size        = sprintf( '%sx%s', $photo->width_o, $photo->height_o );
	$thumbnail   = esc_url( $this->get_flickr_photo_url( $photo, 'n' ) );
	$title       = esc_attr( $photo->title );

	echo sprintf( '<figure class="%s">', $item_class );
	echo sprintf( '<a href="%1$s" data-size="%2$s">', $content_url, $size );
	echo sprintf( '<img src="%1$s" alt="%2$s">', $thumbnail, $title );
	echo '</a>';
	echo '</figure>';

	do_action( 'simple_flickr_widget_loop', $photo, $content_url, $thumbnail, $title );
}

do_action( 'simple_flickr_widget_after_loop', $photos );
echo '</div>';
?>

