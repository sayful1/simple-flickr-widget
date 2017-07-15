<?php

if ( ! function_exists( '__flickr_photos' ) ) {
    function __flickr_photos( $_photo, $_size )
    {
        $server = $_photo->server;
        $farm   = $_photo->farm;
        $id     = $_photo->id;
        $secret = $_photo->secret;
        return sprintf( 'https://farm%1$s.staticflickr.com/%2$s/%3$s_%4$s_%5$s.jpg', $farm, $server, $id, $secret, $_size );
    }
}

$base_url  = 'https://api.flickr.com/services/rest/?method=flickr.people.getPhotos';

$url = add_query_arg( array(
    'api_key'           => $api_key,
    'user_id'           => $user_id,
    'per_page'          => $per_page,
    'extras'            => 'original_format,url_sq, url_t, url_s, url_q, url_m, url_n, url_z, url_c, url_l,url_o',
    'format'            => 'json',
    'nojsoncallback'    => 1,
), $base_url );

$response   = wp_remote_get( $url );
$_body      = wp_remote_retrieve_body( $response );
$content    = json_decode( $_body );
$_photos    = $content->photos->photo;
?>
<div class="flickr_photos_gallery" itemscope itemtype="http://schema.org/ImageGallery">
    
    <?php foreach ( $_photos as $_photo ): ?>
    <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">
        <a
            href="<?php echo esc_url( $_photo->url_o ); ?>"
            itemprop="contentUrl"
            data-size="<?php echo sprintf('%sx%s', $_photo->width_o, $_photo->height_o); ?>"
        >
            <img src="<?php echo esc_url( __flickr_photos( $_photo, 'n' ) ); ?>" itemprop="thumbnail" alt="<?php echo esc_attr( $_photo->title ); ?>" />
        </a>
    </figure>
    <?php endforeach; ?>
    
</div>
