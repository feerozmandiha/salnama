<?php
$item_id = $attributes['itemId'] ?: 'accordion-item-' . uniqid();
$is_open = $attributes['isOpen'] ?? false;

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'salnama-accordion-item' . ($is_open ? ' is-open' : ''),
    'id' => $item_id
]);

// فقط محتوای inner blocks را بگیریم
$inner_content = '';

// روش صحیح برای گرفتن محتوای inner blocks
if (!empty($block->parsed_block['innerBlocks'])) {
    foreach ($block->parsed_block['innerBlocks'] as $inner_block) {
        $inner_content .= render_block($inner_block);
    }
}

// اگر روش بالا کار نکرد، از $content استفاده کنیم اما فیلتر شده
if (empty($inner_content) && !empty($content)) {
    $inner_content = trim($content);
}
?>

<div <?php echo $wrapper_attributes; ?>>
    <button class="salnama-accordion-header" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>" aria-controls="<?php echo $item_id; ?>-content">
        <span class="salnama-accordion-title"><?php echo esc_html($attributes['title']); ?></span>
        <span class="salnama-accordion-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 12L2 6l1.4-1.4L8 9.2l4.6-4.6L14 6z"/>
            </svg>
        </span>
    </button>
    <div class="salnama-accordion-content" id="<?php echo $item_id; ?>-content" <?php echo $is_open ? '' : 'hidden'; ?>>
        <?php echo $inner_content ?: '<p>محتوای آیتم</p>'; ?>
    </div>
</div>