<?php
if (!defined('ABSPATH')) exit;

class HTC_Granular_Gallery_Module extends ET_Builder_Module {

    public $slug       = 'htc_granular_gallery';
    public $vb_support = 'on';

    function init() {
        $this->name = esc_html__('HTC Granular Gallery', 'htc-gallery');
    }

    function get_fields() {
        return [
            'per_page' => [
                'label'           => esc_html__('Images Per Page', 'htc-gallery'),
                'type'            => 'range',
                'default'         => '24',
                'range_settings'  => ['min' => 6, 'max' => 60, 'step' => 1],
                'tab_slug'        => 'general',
            ],
            'show_filters' => [
                'label'           => esc_html__('Show Filters', 'htc-gallery'),
                'type'            => 'yes_no_button',
                'options'         => ['off' => 'No', 'on' => 'Yes'],
                'default'         => 'on',
                'tab_slug'        => 'general',
            ],
            'filter_products' => [
                'label'           => esc_html__('Enable Product Filter', 'htc-gallery'),
                'type'            => 'yes_no_button',
                'options'         => ['off' => 'No', 'on' => 'Yes'],
                'default'         => 'on',
                'show_if'         => ['show_filters' => 'on'],
                'tab_slug'        => 'general',
            ],
            'filter_colors' => [
                'label'           => esc_html__('Enable Color Filter', 'htc-gallery'),
                'type'            => 'yes_no_button',
                'options'         => ['off' => 'No', 'on' => 'Yes'],
                'default'         => 'on',
                'show_if'         => ['show_filters' => 'on'],
                'tab_slug'        => 'general',
            ],
            'filter_styles' => [
                'label'           => esc_html__('Enable Style Filter', 'htc-gallery'),
                'type'            => 'yes_no_button',
                'options'         => ['off' => 'No', 'on' => 'Yes'],
                'default'         => 'on',
                'show_if'         => ['show_filters' => 'on'],
                'tab_slug'        => 'general',
            ],

            'tax_relation' => [
                'label'       => esc_html__('Relation Between Filters', 'htc-gallery'),
                'type'        => 'select',
                'options'     => [
                    'AND' => esc_html__('AND (Product + Color + Style)', 'htc-gallery'),
                    'OR'  => esc_html__('OR (Product or Color or Style)', 'htc-gallery'),
                ],
                'default'     => 'AND',
                'show_if'     => ['show_filters' => 'on'],
                'tab_slug'    => 'general',
            ],
            'product_op' => [
                'label'       => esc_html__('Product Operator', 'htc-gallery'),
                'type'        => 'select',
                'options'     => [
                    'IN'  => esc_html__('IN (OR)', 'htc-gallery'),
                    'AND' => esc_html__('AND (must match all selected)', 'htc-gallery'),
                ],
                'default'     => 'IN',
                'show_if'     => ['show_filters' => 'on'],
                'tab_slug'    => 'general',
            ],
            'color_op' => [
                'label'       => esc_html__('Color Operator', 'htc-gallery'),
                'type'        => 'select',
                'options'     => [
                    'IN'  => esc_html__('IN (OR)', 'htc-gallery'),
                    'AND' => esc_html__('AND (must match all selected)', 'htc-gallery'),
                ],
                'default'     => 'IN',
                'show_if'     => ['show_filters' => 'on'],
                'tab_slug'    => 'general',
            ],
            'style_op' => [
                'label'       => esc_html__('Style Operator', 'htc-gallery'),
                'type'        => 'select',
                'options'     => [
                    'IN'  => esc_html__('IN (OR)', 'htc-gallery'),
                    'AND' => esc_html__('AND (must match all selected)', 'htc-gallery'),
                ],
                'default'     => 'IN',
                'show_if'     => ['show_filters' => 'on'],
                'tab_slug'    => 'general',
            ],
        ];
    }

    function render($attrs, $content = null, $render_slug = null) {
        wp_enqueue_style('htc-gallery-css');
        wp_enqueue_script('htc-gallery-js');

        $per_page     = intval($this->props['per_page']);
        $show_filters = ($this->props['show_filters'] === 'on');

        $tax_relation = in_array($this->props['tax_relation'], ['AND', 'OR'], true) ? $this->props['tax_relation'] : 'AND';
        $product_op   = in_array($this->props['product_op'], ['IN', 'AND'], true) ? $this->props['product_op'] : 'IN';
        $color_op     = in_array($this->props['color_op'], ['IN', 'AND'], true) ? $this->props['color_op'] : 'IN';
        $style_op     = in_array($this->props['style_op'], ['IN', 'AND'], true) ? $this->props['style_op'] : 'IN';

        $q = new WP_Query([
            'post_type'      => 'htc_gallery_item',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => 1,
        ]);

        ob_start();
        ?>
        <div class="htc-gallery"
             data-per-page="<?php echo esc_attr($per_page); ?>"
             data-show-filters="<?php echo esc_attr($show_filters ? '1' : '0'); ?>"
             data-tax-relation="<?php echo esc_attr($tax_relation); ?>"
             data-product-op="<?php echo esc_attr($product_op); ?>"
             data-color-op="<?php echo esc_attr($color_op); ?>"
             data-style-op="<?php echo esc_attr($style_op); ?>">

            <?php if ($show_filters): ?>
                <div class="htc-gallery__filters">
                    <?php echo $this->render_filter_ui(); ?>
                </div>
            <?php endif; ?>

            <div class="htc-gallery__grid" data-page="1">
                <?php
                if ($q->have_posts()) :
                    while ($q->have_posts()) : $q->the_post();
                        $img = get_the_post_thumbnail_url(get_the_ID(), 'large');
                        if (!$img) continue;
                        ?>
                        <a class="htc-gallery__item" href="<?php echo esc_url($img); ?>" data-id="<?php echo esc_attr(get_the_ID()); ?>">
                            <?php the_post_thumbnail('medium_large', ['class' => 'htc-gallery__thumb']); ?>
                        </a>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>

            <div class="htc-gallery__pager">
                <button class="htc-gallery__loadmore" type="button">Load more</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_filter_ui() {
        $groups = [
            ['key' => 'product', 'label' => 'Product'],
            ['key' => 'color',   'label' => 'Color'],
            ['key' => 'style',   'label' => 'Style'],
        ];

        $out = '';

        foreach ($groups as $g) {
            $out .= '<div class="htc-gallery__filtergroup" data-group="' . esc_attr($g['key']) . '">';
            $out .= '<div class="htc-gallery__filtertitle">' . esc_html($g['label']) . '</div>';
            $out .= '<div class="htc-gallery__filterlist"></div>';
            $out .= '</div>';
        }

        return $out;
    }
}
