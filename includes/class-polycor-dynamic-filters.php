<?php
/**
 * Core del sistema de filtros dinámicos PolyCor
 *
 * @package PolyCor_Dynamic_Filters
 */

if (!defined('ABSPATH')) exit;

class PolyCor_Dynamic_Filters {

    private static $instance = null;

    /** @var PolyCor_Category_Mapper */
    private $mapper;

    /** @var PolyCor_Attribute_Manager */
    private $attr_manager;

    /** 
     * Singleton 
     */
    public static function get_instance() {

        if (self::$instance === null) {

            // Dependencias mínimas
            $db           = new PolyCor_DB();
            $mapper       = new PolyCor_Category_Mapper($db);
            $attr_manager = new PolyCor_Attribute_Manager();

            // Instancia base
            self::$instance = new self($mapper, $attr_manager);

            // Renderer separado (solo renderiza)
            new PolyCor_Filter_Renderer($mapper, $attr_manager);

            // Cargar CSS/JS
            add_action('wp_enqueue_scripts', [self::$instance, 'enqueue_assets'], 20);
            add_action('admin_enqueue_scripts', [self::$instance, 'enqueue_admin_assets']);

        }

        return self::$instance;
    }


    private function __construct($mapper, $attr_manager) {
        $this->mapper       = $mapper;
        $this->attr_manager = $attr_manager;

        add_action('template_redirect', [$this, 'normalize_url'], 5);
        add_action('pre_get_posts',     [$this, 'modify_wc_query'], 20);
    }



    /* --------------------------------------------------------------
     *  NORMALIZAR/VALIDAR URL 
     * -------------------------------------------------------------- */
    public function normalize_url() {

        if (!is_product_category()) return;

        $cat_id  = get_queried_object_id();
        $allowed = $this->mapper->get_allowed_attribute_slugs($cat_id);

        if (empty($allowed)) return;

        $changed   = false;
        $to_remove = [];

        foreach ($_GET as $key => $value) {

            if (strpos($key, 'filter_') !== 0) continue;

            $attr = str_replace('filter_', '', $key);

            if (!in_array($attr, $allowed)) {
                $to_remove[] = $key;
                $changed     = true;
            }
        }

        if ($changed) {
            wp_safe_redirect(remove_query_arg($to_remove));
            exit;
        }
    }



    /* --------------------------------------------------------------
     *  PARSEAR FILTROS DE LA URL
     * -------------------------------------------------------------- */
    private function get_url_filters($cat_id) {

        $allowed = $this->mapper->get_allowed_attribute_slugs($cat_id);
        if (empty($allowed)) return [];

        $filters = [];

        foreach ($_GET as $key => $value) {

            if (strpos($key, 'filter_') !== 0) continue;

            $attr = str_replace('filter_', '', $key);
            if (!in_array($attr, $allowed)) continue;

            $terms = array_filter(
                array_map('sanitize_title', explode(',', $value))
            );

            if (!empty($terms)) {
                $filters[$attr] = $terms;
            }
        }

        return $filters;
    }



    /* --------------------------------------------------------------
     *  MODIFICAR LA QUERY PRINCIPAL DE WOOCOMMERCE
     * -------------------------------------------------------------- */
    public function modify_wc_query($query) {

        if (is_admin() || !$query->is_main_query() || !is_product_category()) return;

        $cat_id  = get_queried_object_id();
        $filters = $this->get_url_filters($cat_id);

        if (empty($filters)) return;

        // Leer la tax_query que WC ya estableció en priority 10
        // (incluye filtros de product_visibility, product_cat, etc.)
        $tax_query = (array) $query->get('tax_query');

        // Si por alguna razón está vacía, obtener la base de WC directamente
        if (empty($tax_query)) {
            $tax_query = WC()->query->get_tax_query();
        }

        $tax_query['relation'] = 'AND';

        foreach ($filters as $attr => $terms) {

            $taxonomy = 'pa_' . sanitize_title($attr);
            if (!taxonomy_exists($taxonomy)) continue;

            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'IN'
            ];
        }

        $query->set('tax_query', $tax_query);

        // Aplicar meta_query estándar de WC (stock, visibilidad) si no está ya establecida
        if (!$query->get('meta_query')) {
            $query->set('meta_query', WC()->query->get_meta_query());
        }
    }



    /* --------------------------------------------------------------
     *  ENCOLAR CSS / JS EN UN ÚNICO LUGAR
     * -------------------------------------------------------------- */
    public function enqueue_assets() {

        if (!is_woocommerce()) return;

        wp_enqueue_style(
            'polycor-frontend',
            POLYCOR_PLUGIN_URL . 'assets/css/polycor-frontend.css',
            [],
            POLYCOR_VERSION
        );

        wp_enqueue_style(
            'polycor-chips',
            POLYCOR_PLUGIN_URL . 'assets/css/polycor-chips.css',
            ['polycor-frontend'],
            POLYCOR_VERSION
        );

        wp_enqueue_script(
            'polycor-frontend',
            POLYCOR_PLUGIN_URL . 'assets/js/polycor-frontend.js',
            ['jquery'],
            POLYCOR_VERSION,
            true
        );
        
    
    }
    public function enqueue_admin_assets($hook) {

    // Solo cargar en la pantalla real
    if ($hook !== 'woocommerce_page_polycor-filters') {
        return;
    }

    wp_enqueue_style(
        'polycor-admin',
        POLYCOR_PLUGIN_URL . 'assets/css/polycor-admin.css',
        [],
        POLYCOR_VERSION
    );

    wp_enqueue_script(
        'polycor-admin',
        POLYCOR_PLUGIN_URL . 'assets/js/polycor-admin.js',
        ['jquery', 'jquery-ui-sortable'],
        POLYCOR_VERSION,
        true
    );

    wp_localize_script('polycor-admin', 'polycorAdminData', [
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('polycor_admin_action'),
        'confirmDelete' => __('¿Estás seguro de eliminar este mapeo?', 'polycor-filters'),
        'loadingText'   => 'Cargando…',
        'noFrequent'    => 'No hay atributos frecuentes.'
    ]);
}

}
