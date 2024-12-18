<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function register_custom_widgets($widgets_manager)
{
    // include file
    require_once TEMPLATE_PATH . 'header.php';

    // Register widgets
    $widgets_manager->register(new \Header_Widget());
}
add_action('elementor/widgets/register', 'register_custom_widgets');

function register_custom_widget_category($elements_manager)
{
    $elements_manager->add_category(
        'custom_widgets_theme',
        [
            'title' => __('Custom Widgets (theme)', 'child_theme'),
            'icon' => 'eicon-code',
            'priority' => 0,
        ]
    );
}
add_action('elementor/elements/categories_registered', 'register_custom_widget_category');
