<?php
if ( ! defined( 'ABSPATH' ) ) {
	return '';
}

/**
 * بازگرداندن render callback برای hero-slide-item
 *
 * فایل باید یک callable بازگرداند تا register_block_type_from_metadata آن را به عنوان render_callback استفاده کند.
 */
return function( $attributes, $content, $block ) {
	$media_id  = isset( $attributes['mediaId'] ) ? intval( $attributes['mediaId'] ) : 0;
	$media_url = isset( $attributes['mediaUrl'] ) ? $attributes['mediaUrl'] : '';
	$media_alt = isset( $attributes['mediaAlt'] ) ? $attributes['mediaAlt'] : '';

	if ( $media_id ) {
		$image_src = wp_get_attachment_image_src( $media_id, 'full' );
		if ( ! empty( $image_src[0] ) ) {
			$media_url = $image_src[0];
		}
	}

	$style_attr = '';
	if ( $media_url ) {
		$style_attr = sprintf( 'background-image: url(%s);', esc_url( $media_url ) );
	}

	$wrapper = get_block_wrapper_attributes( array(
		'class' => 'salnama-slider-item',
		'style' => $style_attr,
	) );

	$inner = $content ?: '';

	$html  = '<div ' . $wrapper . '>';
	$html .= '<div class="salnama-slide-inner-content">' . $inner . '</div>';
	$html .= '</div>';

	return $html;
};
