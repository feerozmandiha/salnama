<?php
/**
 * @package Salnama_Blocks
 * رندر سمت کاربر برای بلوک Hero Slider (با Lazy Loading)
 */

$animation_speed   = $attributes['animationSpeed'] ?? 6000;
$transition_effect = $attributes['transitionEffect'] ?? 'fade';
$overlay_color     = $attributes['overlayColor'] ?? '#000000';
$overlay_opacity   = $attributes['overlayOpacity'] ?? 0.65;

$dynamic_styles = sprintf(
  '--overlay-color: %s; --overlay-opacity: %s;',
  esc_attr( $overlay_color ),
  esc_attr( $overlay_opacity )
);

$wrapper_attributes = get_block_wrapper_attributes( array(
  'style'                 => $dynamic_styles,
  'data-animation-speed'  => esc_attr( $animation_speed ),
  'data-transition-effect'=> esc_attr( $transition_effect ),
  'class'                 => 'salnama-hero-block',
) );

$slide_count   = 0;
$slides_html   = '';
$contents_html = '';

if ( ! empty( $block->inner_blocks ) ) {
  foreach ( $block->inner_blocks as $inner_block ) {
    if ( $inner_block->name === 'salnama-blocks/hero-slide-item' ) {
      $slide_count++;
      $media_id  = $inner_block->attributes['mediaId'] ?? 0;
      $media_url = $inner_block->attributes['mediaUrl'] ?? '';
      $media_alt = $inner_block->attributes['mediaAlt'] ?? '';

      $is_active_class = ( $slide_count === 1 ) ? 'active' : '';

      if ( $media_id ) {
        $image_src = wp_get_attachment_image_src( $media_id, 'full' );
        if ( $image_src ) {
          $media_url = $image_src[0];
        }
      }

      // استفاده از <img> با Lazy Loading
      $slides_html .= sprintf(
        '<div class="salnama-slider-item %1$s">
           <img src="%2$s" alt="%3$s" loading="lazy" decoding="async" />
         </div>',
        esc_attr( $is_active_class ),
        esc_url( $media_url ),
        esc_attr( $media_alt )
      );

      $contents_html .= sprintf(
        '<div class="salnama-hero-content %1$s">%2$s</div>',
        esc_attr( $is_active_class ),
        $inner_block->render()
      );
    }
  }
}
?>
<div <?php echo $wrapper_attributes; ?>>
  <div class="salnama-hero-slider-container <?php echo 'is-effect-' . esc_attr( $transition_effect ); ?>">
    <div class="salnama-hero-overlay"></div>
    <?php echo $slides_html; ?>
    <?php echo $contents_html; ?>

    <!-- دات‌های ناوبری -->
    <div class="salnama-slider-dots">
      <?php for ( $i = 0; $i < $slide_count; $i++ ) : ?>
        <span class="salnama-slider-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
      <?php endfor; ?>
    </div>
  </div>
</div>
