<?php
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'salnama-accordion',
    'data-style' => $attributes['accordionStyle'] ?? 'vertical',
    'data-multiple' => $attributes['multipleOpen'] ? 'true' : 'false',
    'data-duration' => $attributes['animationDuration'] ?? 300
]);
?>

<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</div>