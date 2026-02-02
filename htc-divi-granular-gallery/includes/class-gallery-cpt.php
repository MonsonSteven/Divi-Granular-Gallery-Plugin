<?php
if (!defined('ABSPATH')) exit;

class HTC_Gallery_CPT {

    public static function register() {
        self::register_post_type();
        self::register_taxonomies();
    }

    private static function register_post_type() {
        register_post_type('htc_gallery_item', [
            'labels' => [
                'name'          => 'Gallery Items',
                'singular_name' => 'Gallery Item',
            ],
            'public'       => true,
            'has_archive'  => false,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-format-gallery',
            'supports'     => ['title', 'thumbnail'],
        ]);
    }

    private static function register_taxonomies() {
        // Product Type (hierarchical)
        register_taxonomy('htc_product', ['htc_gallery_item'], [
            'labels' => ['name' => 'Products', 'singular_name' => 'Product'],
            'public'       => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        // Color (tags)
        register_taxonomy('htc_color', ['htc_gallery_item'], [
            'labels' => ['name' => 'Colors', 'singular_name' => 'Color'],
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);

        // Style (tags or hierarchical; choose what you prefer)
        register_taxonomy('htc_style', ['htc_gallery_item'], [
            'labels' => ['name' => 'Styles', 'singular_name' => 'Style'],
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);
    }
}
