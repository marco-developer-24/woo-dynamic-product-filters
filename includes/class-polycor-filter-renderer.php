<?php
/**
 * Clase para renderizar filtros y chips en el frontend
 *
 * @package PolyCor_Dynamic_Filters
 */

if (!defined('ABSPATH')) exit;

class PolyCor_Filter_Renderer {

    /** @var PolyCor_Category_Mapper */
    private $category_mapper;

    /** @var PolyCor_Attribute_Manager */
    private $attribute_manager;


    /**
     * Constructor
     */
    public function __construct($category_mapper, $attribute_manager) {
        $this->category_mapper   = $category_mapper;
        $this->attribute_manager = $attribute_manager;

        // Registrar shortcodes
        add_shortcode('polycor_filter_groups',  [$this, 'render_filter_groups_shortcode']);
        add_shortcode('polycor_active_filters', [$this, 'render_active_filters_shortcode']);
        add_shortcode('polycor_dynamic_chips',  [$this, 'render_dynamic_chips_shortcode']);
    }


    /* =======================================================
     *  SHORTCODE: [polycor_filter_groups]
     * ======================================================= */
    public function render_filter_groups_shortcode($atts) {

    $atts = shortcode_atts([
        'category_id' => '',
        'layout'      => 'vertical',
        'show_count'  => 'yes',
        'style'       => 'checkboxes'
    ], $atts);

    $category_id = !empty($atts['category_id'])
        ? absint($atts['category_id'])
        : $this->category_mapper->get_current_category_id();

    if (!$category_id) {
        // En tienda, archivos, páginas, NO mostrar nada
        return '';
    }

    $mappings = $this->category_mapper->get_attributes_for_category($category_id);

    if (empty($mappings))
        return '<div class="polycor-filters-notice">No hay filtros configurados para esta categoría.</div>';

    ob_start();
    $this->render_filter_groups($category_id, $mappings, $atts);
    return ob_get_clean();
}

    
    /* =======================================================
     *  SHORTCODE: [polycor_active_filters]
     * ======================================================= */
    public function render_active_filters_shortcode() {

        $selected_filters = $this->get_selected_filters();
        if (empty($selected_filters)) return '';

        ob_start();

        echo '<div class="polycor-active-filters">';
        echo '<h4 class="polycor-active-filters-title">Filtros Activos</h4>';
        echo '<ul>';

        foreach ($selected_filters as $key => $values) {

            $attribute_slug  = str_replace('filter_', '', $key);
            $attribute_label = $this->attribute_manager->get_attribute_label($attribute_slug);

            foreach ($values as $slug) {

                $term = get_term_by('slug', $slug, 'pa_' . $attribute_slug);
                if (!$term) continue;

                // Generar URL sin este filtro
                $current_values = $selected_filters[$key];
                $new_values     = array_diff($current_values, [$slug]);

                $remove_link = remove_query_arg($key);
                if (!empty($new_values)) {
                    $remove_link = add_query_arg($key, implode(',', $new_values), $remove_link);
                }

                echo '<li class="polycor-active-filter-chip">';
                echo '   <span class="polycor-chip-cell polycor-chip-text">'.esc_html($term->name).'</span>';
                echo '   <span class="polycor-chip-cell polycor-chip-action">';
                echo '       <a href="'.esc_url($remove_link).'" class="polycor-remove-filter" aria-label="'.esc_attr(sprintf(__('Remove filter: %s', 'polycor-dynamic-filters'), $term->name)).'">';
                echo '           <span class="polycor-remove-icon" aria-hidden="true">&times;</span>';
                echo '       </a>';
                echo '   </span>';
                echo '</li>';
            }
        }

        echo '</ul></div>';
        return ob_get_clean();
    }


    /* =======================================================
     *  SHORTCODE: [polycor_dynamic_chips]
     * ======================================================= */
    public function render_dynamic_chips_shortcode() {
        return $this->render_product_attribute_chips();
    }


    /* =======================================================
     *  RENDER DE FILTROS AGRUPADOS
     * ======================================================= */
    private function render_filter_groups($category_id, $mappings, $atts) {

        $layout      = sanitize_text_field($atts['layout']);
        $show_count  = $atts['show_count'] === 'yes';
        $style       = sanitize_text_field($atts['style']);
        $selected    = $this->get_selected_filters();

        echo '<div class="polycor-filters-wrapper" data-category="'.esc_attr($category_id).'">';
        echo '<div class="polycor-filters-container polycor-layout-'.esc_attr($layout).'">';

        foreach ($mappings as $mapping):

            $attribute_slug = $mapping['attribute_slug'];
            $filter_key     = 'filter_' . $attribute_slug;

            // Si ya está filtrado, no mostrarlo otra vez
            if (isset($selected[$filter_key])) continue;

            $terms = $this->attribute_manager->get_attribute_terms_with_count($attribute_slug, $category_id);
            if (empty($terms)) continue;

            $label = $this->attribute_manager->get_attribute_label($attribute_slug);

            $options_id = wp_unique_id('polycor-filter-options-');

            echo '<div class="polycor-filter-group" data-attribute="'.esc_attr($attribute_slug).'">';
            echo '<h4 class="polycor-filter-heading">';
            echo '<button type="button" class="polycor-filter-title" aria-expanded="false" aria-controls="'.esc_attr($options_id).'">';
            echo esc_html($label).'<span class="polycor-toggle-icon" aria-hidden="true"></span>';
            echo '</button>';
            echo '</h4>';

            echo '<ul id="'.esc_attr($options_id).'" class="polycor-filter-options polycor-style-'.esc_attr($style).'">';

            foreach ($terms as $term):

                $current_values = $selected[$filter_key] ?? [];
                $is_active      = in_array($term->slug, $current_values);

                // Construir nueva lista (multi-select)
                $new_values = $is_active
                                ? array_diff($current_values, [$term->slug])
                                : array_merge($current_values, [$term->slug]);

                $link = add_query_arg($filter_key, implode(',', $new_values));

                echo '<li class="polycor-filter-item '.($is_active ? 'active':'').'">';
                echo '   <a href="'.esc_url($link).'">';
                echo '       <span class="polycor-filter-icon"></span>';
                echo            esc_html($term->name);
                if ($show_count)
                    echo '   <span class="polycor-filter-count">('.intval($term->count).')</span>';
                echo '   </a>';
                echo '</li>';

            endforeach;

            echo '</ul></div>';

        endforeach;

        echo '</div>';

        // Botón limpiar si hay filtros activos
        if (!empty($selected)) {
            echo '<div class="polycor-filters-actions">';
            echo '<a class="polycor-btn polycor-btn-clear" href="'.esc_url(get_term_link($category_id, 'product_cat')).'">';
            echo 'Limpiar Todo</a>';
            echo '</div>';
        }

        echo '</div>';
    }


    /* =======================================================
     *  OBTENER FILTROS SELECCIONADOS
     * ======================================================= */
    public function get_selected_filters() {

        $filters = [];

        foreach ($_GET as $key => $value) {
            if (strpos($key, 'filter_') !== 0) continue;
            if (empty($value)) continue;

            $filters[$key] = array_map('trim', explode(',', sanitize_text_field($value)));
        }

        return $filters;
    }


    /* =======================================================
     *  CHIPS VISIBLES EN PRODUCT PAGE
     * ======================================================= */

    public function render_product_attribute_chips() {

    global $product;
    if (!$product) return '';

    // Categoría principal
    $cats = wp_get_post_terms($product->get_id(), 'product_cat');
    if (empty($cats)) return '';

    $cat_id = $cats[0]->term_id;

    // Obtener SOLO atributos mapeados
    $allowed = $this->category_mapper->get_allowed_attribute_slugs($cat_id);
    if (empty($allowed)) return '';

    $attributes = $product->get_attributes();
    if (empty($attributes)) return '';

    $chips = '';

    foreach ($attributes as $attribute) {

        $taxonomy = $attribute->get_name();
        if (strpos($taxonomy, 'pa_') !== 0) continue;

        // pa_memoria-ram → memoria-ram
        $slug = str_replace('pa_', '', $taxonomy);

        if (!in_array($slug, $allowed)) continue;

        $terms = wp_get_post_terms($product->get_id(), $taxonomy);
        if (empty($terms)) continue;

        foreach ($terms as $term) {

            $filter_key = 'filter_' . $slug;

            $link = add_query_arg(
                $filter_key,
                $term->slug,
                get_term_link($cat_id, 'product_cat')
            );
            $attr_label = $this->attribute_manager->get_attribute_label($slug);

$chips .= '<a class="polycor-chip" href="' . esc_url($link) . '">'
            . '<span class="polycor-chip-attr">' . esc_html($attr_label) . ' : </span> '
            . esc_html($term->name) .
          '</a>';
        /*
            $chips .= '<a class="polycor-chip" href="' . esc_url($link) . '">'
                        . esc_html($term->name) .
                      '</a>';
        */
        }
    }

    if (!$chips) return '';

    return '
        <div class="polycor-featured-attributes">
            <h4 class="polycor-featured-attributes-title">Características</h4>
            <div class="polycor-product-chips">' . $chips . '</div>
        </div>
    ';
}

}

