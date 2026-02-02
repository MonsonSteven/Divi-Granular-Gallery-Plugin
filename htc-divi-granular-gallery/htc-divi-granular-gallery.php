<?php
/**
 * Plugin Name: HTC Divi Granular Gallery
 * Description: Divi module + CPT + taxonomies for a deeply taggable photo gallery.
 * Version: 0.1.0
 * Author: Steven Monson
 */

if (!defined('ABSPATH')) exit;

define('HTC_GALLERY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HTC_GALLERY_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HTC_GALLERY_PLUGIN_DIR . 'includes/class-gallery-cpt.php';

add_action('init', function () {
    HTC_Gallery_CPT::register();
});

add_action('wp_enqueue_scripts', function () {
    wp_register_style(
        'htc-gallery-css',
        HTC_GALLERY_PLUGIN_URL . 'assets/css/gallery.css',
        [],
        '0.1.0'
    );

    wp_register_script(
        'htc-gallery-js',
        HTC_GALLERY_PLUGIN_URL . 'assets/js/gallery.js',
        ['jquery'],
        '0.1.0',
        true
    );

    wp_localize_script('htc-gallery-js', 'HTCGallery', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('htc_gallery_nonce'),
    ]);
});

/**
 * Register Divi module
 */
add_action('et_builder_ready', function () {
    if (!class_exists('ET_Builder_Module')) return;

    require_once HTC_GALLERY_PLUGIN_DIR . 'includes/modules/class-htc-granular-gallery-module.php';
    new HTC_Granular_Gallery_Module();
});

/**
 * AJAX filtering endpoint (logged-in + public)
 */
add_action('wp_ajax_htc_gallery_filter', 'htc_gallery_filter_ajax');
add_action('wp_ajax_nopriv_htc_gallery_filter', 'htc_gallery_filter_ajax');
add_action('wp_ajax_htc_gallery_terms', 'htc_gallery_terms_ajax');
add_action('wp_ajax_nopriv_htc_gallery_terms', 'htc_gallery_terms_ajax');


function htc_gallery_filter_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htc_gallery_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }

    $page      = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page  = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 24;

    $relation = isset($_POST['tax_relation']) ? sanitize_text_field($_POST['tax_relation']) : 'AND';
    $relation = in_array($relation, ['AND', 'OR'], true) ? $relation : 'AND';

    $tax_query = ['relation' => $relation];

    $map = [
        'product' => 'htc_product',
        'color'   => 'htc_color',
        'style'   => 'htc_style',
    ];

    // Operators per taxonomy: IN (OR) vs AND (must have all selected terms)
    $op_map = [
        'product' => isset($_POST['product_op']) ? sanitize_text_field($_POST['product_op']) : 'IN',
        'color'   => isset($_POST['color_op']) ? sanitize_text_field($_POST['color_op']) : 'IN',
        'style'   => isset($_POST['style_op']) ? sanitize_text_field($_POST['style_op']) : 'IN',
    ];

    foreach ($op_map as $k => $op) {
        $op_map[$k] = in_array($op, ['IN', 'AND'], true) ? $op : 'IN';
    }

    foreach ($map as $key => $taxonomy) {
        if (!empty($_POST[$key]) && is_array($_POST[$key])) {
            $slugs = array_values(array_filter(array_map('sanitize_text_field', $_POST[$key])));
            if (!empty($slugs)) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $slugs,
                    'operator' => $op_map[$key],
                ];
            }
        }
    }


    $args = [
        'post_type'      => 'htc_gallery_item',
        'post_status'    => 'publish',
        'paged'          => $page,
        'posts_per_page' => $per_page,
    ];

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $q = new WP_Query($args);

    ob_start();
    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $img = get_the_post_thumbnail_url(get_the_ID(), 'large');
            if (!$img) continue;
            ?>
            <a class="htc-gallery__item" href="<?php echo esc_url($img); ?>" data-id="<?php echo esc_attr(get_the_ID()); ?>">
                <?php the_post_thumbnail('medium_large', ['class' => 'htc-gallery__thumb']); ?>
            </a>
            <?php
        }
        wp_reset_postdata();
    }
    $html = ob_get_clean();

if (trim($html) === '') {
    $html = '<div class="htc-gallery__empty">No matching photos found.</div>';
}

wp_send_json_success([
    'html'       => $html,
    'foundPosts' => intval($q->found_posts),
    'maxPages'   => intval($q->max_num_pages),
    'page'       => $page,
]);
}

function htc_gallery_terms_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htc_gallery_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }

    $relation = isset($_POST['tax_relation']) ? sanitize_text_field($_POST['tax_relation']) : 'AND';
    $relation = in_array($relation, ['AND', 'OR'], true) ? $relation : 'AND';

    $selected = [
        'product' => (!empty($_POST['product']) && is_array($_POST['product'])) ? array_values(array_filter(array_map('sanitize_text_field', $_POST['product']))) : [],
        'color'   => (!empty($_POST['color']) && is_array($_POST['color'])) ? array_values(array_filter(array_map('sanitize_text_field', $_POST['color']))) : [],
        'style'   => (!empty($_POST['style']) && is_array($_POST['style'])) ? array_values(array_filter(array_map('sanitize_text_field', $_POST['style']))) : [],
    ];

    $map = [
        'product' => 'htc_product',
        'color'   => 'htc_color',
        'style'   => 'htc_style',
    ];

    $op_map = [
        'product' => isset($_POST['product_op']) ? sanitize_text_field($_POST['product_op']) : 'IN',
        'color'   => isset($_POST['color_op']) ? sanitize_text_field($_POST['color_op']) : 'IN',
        'style'   => isset($_POST['style_op']) ? sanitize_text_field($_POST['style_op']) : 'IN',
    ];
    foreach ($op_map as $k => $op) {
        $op_map[$k] = in_array($op, ['IN', 'AND'], true) ? $op : 'IN';
    }

    // Build a base query for IDs matching all current filters
    $tax_query = ['relation' => $relation];
    foreach ($map as $key => $tax) {
        if (!empty($selected[$key])) {
            $tax_query[] = [
                'taxonomy' => $tax,
                'field'    => 'slug',
                'terms'    => $selected[$key],
                'operator' => $op_map[$key],
            ];
        }
    }

    $args = [
        'post_type'      => 'htc_gallery_item',
        'post_status'    => 'publish',
        'posts_per_page' => -1,          // keep sane; can optimize later
        'fields'         => 'ids',
    ];
    if (count($tax_query) > 1) $args['tax_query'] = $tax_query;

    $q = new WP_Query($args);
    $ids = $q->posts;

    // If no matches, return empty lists (but still include selected terms)
    $response = [
        'product' => [],
        'color'   => [],
        'style'   => [],
        'total_matches' => intval($q->found_posts),
    ];

    if (!empty($ids)) {
        foreach ($map as $key => $taxonomy) {
            $terms = wp_get_object_terms($ids, $taxonomy, ['fields' => 'all']);
            if (is_wp_error($terms) || empty($terms)) continue;

            // Count occurrences
            $counts = [];
            foreach ($terms as $t) {
                $slug = $t->slug;
                if (!isset($counts[$slug])) {
                    $counts[$slug] = [
                        'name'  => $t->name,
                        'slug'  => $t->slug,
                        'count' => 0,
                    ];
                }
                $counts[$slug]['count']++;
            }

            // Sort by count desc then name
            usort($counts, function ($a, $b) {
                if ($a['count'] === $b['count']) return strcasecmp($a['name'], $b['name']);
                return $b['count'] - $a['count'];
            });

            $response[$key] = array_values($counts);
        }
    }

    wp_send_json_success($response);
}
