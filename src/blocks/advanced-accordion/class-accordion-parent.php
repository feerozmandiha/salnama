<?php
class Salnama_Accordion_Parent {
    public function __construct() {
        add_action('init', [$this, 'register_block']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
    }

    public function register_block() {
        register_block_type(
            SALNAMA_PLUGIN_DIR . 'build/blocks/advanced-accordion/',
            [
                'render_callback' => [$this, 'render_block'],
            ]
        );
    }

    public function enqueue_frontend_scripts() {
        if (has_block('salnama-blocks/advanced-accordion')) {
            wp_enqueue_script(
                'salnama-accordion-frontend',
                SALNAMA_PLUGIN_URL . 'build/blocks/advanced-accordion/frontend.js',
                [],
                '1.0.0',
                true
            );
        }
    }

    public function render_block($attributes, $content) {
        $wrapper_attributes = get_block_wrapper_attributes([
            'class' => 'salnama-accordion',
            'data-style' => $attributes['accordionStyle'] ?? 'vertical',
            'data-multiple' => $attributes['multipleOpen'] ? 'true' : 'false',
            'data-duration' => $attributes['animationDuration'] ?? 300
        ]);

        return sprintf(
            '<div %s>%s</div>',
            $wrapper_attributes,
            $content
        );
    }
}

new Salnama_Accordion_Parent();