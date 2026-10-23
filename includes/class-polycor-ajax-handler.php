<?php
/**
 * Clase para manejar peticiones AJAX
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PolyCor_Ajax_Handler {
    
    /**
     * Instancia del mapeador de categorías
     *
     * @var PolyCor_Category_Mapper
     */
    private $category_mapper;
    
    /**
     * Instancia del gestor de atributos
     *
     * @var PolyCor_Attribute_Manager
     */
    private $attribute_manager;
    
    /**
     * Constructor
     */
    public function __construct($category_mapper, $attribute_manager) {
        $this->category_mapper   = $category_mapper;
        $this->attribute_manager = $attribute_manager;
        
        // Hooks AJAX (logged in y no logged in)
        add_action('wp_ajax_polycor_filter_products', array($this, 'ajax_filter_products'));
        add_action('wp_ajax_nopriv_polycor_filter_products', array($this, 'ajax_filter_products'));
        
        add_action('wp_ajax_polycor_get_terms', array($this, 'ajax_get_terms'));
        add_action('wp_ajax_nopriv_polycor_get_terms', array($this, 'ajax_get_terms'));
    }
    
    /**
     * Filtrar productos vía AJAX
     */
    public function ajax_filter_products() {
        // Verificar nonce
        check_ajax_referer('polycor_filter_nonce', 'nonce');
        
        // Obtener y sanitizar parámetros
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        $filters = isset($_POST['filters']) ? $this->sanitize_filters($_POST['filters']) : array();
        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
        
        if (!$category_id) {
            wp_send_json_error(array(
                'message' => __('ID de categoría inválido', 'polycor-filters')
            ));
        }
        
        // Construir query de productos
        $products = $this->query_filtered_products($category_id, $filters, $paged);
        
        // Renderizar partes de la plantilla de WooCommerce
        ob_start();
        if ($products->have_posts()) {
            woocommerce_product_loop_start();
            while ($products->have_posts()) {
                $products->the_post();
                wc_get_template_part('content', 'product');
            }
            woocommerce_product_loop_end();
        } else {
            wc_get_template('loop/no-products-found.php');
        }
        $products_html = ob_get_clean();

        ob_start();
        woocommerce_pagination();
        $pagination_html = ob_get_clean();

        ob_start();
        woocommerce_result_count();
        $result_count_html = ob_get_clean();

        // Restaurar datos globales
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'products_html'     => $products_html,
            'pagination_html'   => $pagination_html,
            'result_count_html' => $result_count_html,
            'found_posts'       => $products->found_posts,
            'total_pages'       => $products->max_num_pages,
            'current_page'      => $paged,
        ));
    }
    
    /**
     * Consultar productos filtrados
     *
     * @param int $category_id ID de categoría
     * @param array $filters Filtros aplicados
     * @param int $paged Página actual
     * @return WP_Query
     */
    private function query_filtered_products($category_id, $filters, $paged = 1) {
        global $woocommerce_loop;

        // Base tax_query desde WC: incluye filtro de visibilidad de catálogo (product_visibility)
        $tax_query             = WC()->query->get_tax_query();
        $tax_query['relation'] = 'AND';

        // Filtro de categoría
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $category_id,
        );

        // Filtros de atributos
        if (!empty($filters)) {
            foreach ($filters as $attribute_slug => $terms) {
                if (empty($terms)) {
                    continue;
                }

                $taxonomy = 'pa_' . sanitize_title($attribute_slug);

                if (taxonomy_exists($taxonomy)) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $terms,
                        'operator' => 'IN',
                    );
                }
            }
        }

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => wc_get_default_products_per_row() * wc_get_default_product_rows_per_page(),
            'paged'          => $paged,
            'tax_query'      => $tax_query,
            // meta_query estándar de WC: stock y visibilidad según configuración WC
            'meta_query'     => WC()->query->get_meta_query(),
        );

        // Aplicar ordenación de WooCommerce
        $ordering        = WC()->query->get_catalog_ordering_args();
        $args['orderby'] = $ordering['orderby'];
        $args['order']   = $ordering['order'];
        if (isset($ordering['meta_key'])) {
            $args['meta_key'] = $ordering['meta_key'];
        }

        $query = new WP_Query($args);
        $query->set('product_cat', get_term_field('slug', $category_id));

        // Establecer variables globales para que las plantillas de WC funcionen
        $woocommerce_loop['total']        = $query->found_posts;
        $woocommerce_loop['total_pages']  = $query->max_num_pages;
        $woocommerce_loop['per_page']     = $args['posts_per_page'];
        $woocommerce_loop['current_page'] = $paged;

        return $query;
    }
    
    /**
     * Obtener términos de atributo vía AJAX
     */
    public function ajax_get_terms() {
        check_ajax_referer('polycor_filter_nonce', 'nonce');
        
        $attribute_slug = isset($_POST['attribute_slug']) ? sanitize_text_field($_POST['attribute_slug']) : '';
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        
        if (!$attribute_slug) {
            wp_send_json_error(array(
                'message' => __('Atributo inválido', 'polycor-filters')
            ));
        }
        
        $terms = $this->attribute_manager->get_attribute_terms_with_count($attribute_slug, $category_id);
        
        wp_send_json_success(array(
            'terms' => $terms
        ));
    }
    
    /**
     * Sanitizar filtros recibidos
     *
     * @param array $filters Filtros sin sanitizar
     * @return array Filtros sanitizados
     */
    private function sanitize_filters($filters) {
        if (!is_array($filters)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($filters as $key => $value) {
            $clean_key = sanitize_text_field($key);
            
            if (is_array($value)) {
                $sanitized[$clean_key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$clean_key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}