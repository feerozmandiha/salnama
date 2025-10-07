<?php
$animation_class = '';
$style_attr = '';

// اضافه کردن کلاس انیمیشن اگر تنظیم شده
if (!empty($attributes['salnamaAnimation'])) {
    $animation_class = 'salnama-animated-block animated ' . esc_attr($attributes['salnamaAnimation']);
    
    // اضافه کردن استایل‌های inline برای تأخیر و مدت
    $style_values = [];
    if (!empty($attributes['salnamaAnimationDelay'])) {
        $style_values[] = 'animation-delay: ' . esc_attr($attributes['salnamaAnimationDelay']) . 'ms';
        $style_values[] = '-webkit-animation-delay: ' . esc_attr($attributes['salnamaAnimationDelay']) . 'ms';
    }
    if (!empty($attributes['salnamaAnimationDuration'])) {
        $style_values[] = 'animation-duration: ' . esc_attr($attributes['salnamaAnimationDuration']) . 'ms';
        $style_values[] = '-webkit-animation-duration: ' . esc_attr($attributes['salnamaAnimationDuration']) . 'ms';
    }
    
    if (!empty($style_values)) {
        $style_attr = ' style="' . implode('; ', $style_values) . '"';
    }
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'salnama-accordion ' . $animation_class,
    'data-style' => $attributes['accordionStyle'] ?? 'vertical',
    'data-multiple' => $attributes['multipleOpen'] ? 'true' : 'false',
    'data-duration' => $attributes['animationDuration'] ?? 300
]);

// اضافه کردن style attribute اگر وجود دارد
if ($style_attr) {
    $wrapper_attributes = str_replace('>', $style_attr . '>', $wrapper_attributes);
}
?>

<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</div>