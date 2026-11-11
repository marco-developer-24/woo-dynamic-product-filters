<?php
/**
 * Plugin Name: PolyCor Dynamic Filters
 * Plugin URI: https://polycor.com
 * Description: Sistema avanzado de filtros dinámicos por categoría para WooCommerce
 * Version: 1.0.0
 * Author: PolyCor Development Team
 * Author URI: https://polycor.com
 * Text Domain: polycor-filters
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
if (!defined('POLYCOR_VERSION')) {
    define('POLYCOR_VERSION', '1.0.0');
}
if (!defined('POLYCOR_PLUGIN_DIR')) {
    define('POLYCOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('POLYCOR_PLUGIN_URL')) {
    define('POLYCOR_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('POLYCOR_PLUGIN_BASENAME')) {
    define('POLYCOR_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * Verificar si WooCommerce está activo
 */
if (!function_exists('polycor_check_woocommerce')) {
    function polycor_check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo '<strong>PolyCor Dynamic Filters</strong> requiere que WooCommerce esté instalado y activado.';
                echo '</p></div>';
            });
            return false;
        }
        return true;
    }
}

/**
 * Cargar archivos y registrar el widget
 */
function polycor_load_files() {
    // Cargar clases principales
    require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-db.php';
    require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-attribute-manager.php';
    require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-category-mapper.php';
    require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-filter-renderer.php';
    require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-ajax-handler.php';
    require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-chips.php';
    require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-dynamic-filters.php';
    require_once POLYCOR_PLUGIN_DIR . 'class-polycor-filters-widget.php';
    
    // Cargar el archivo de la página de administración solo si estamos en el admin
    if (is_admin()) {
        require_once POLYCOR_PLUGIN_DIR . 'admin/class-polycor-admin-page.php';
    }
}


/**
 * Declarar compatibilidad con WooCommerce HPOS
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});


/**
 * Inicializar el plugin
 */
if (!function_exists('polycor_init')) {


function polycor_init() {

    polycor_load_files();

    if (!polycor_check_woocommerce()) {
        return;
    }

    // Construir las dependencias una sola vez y compartirlas entre componentes.
    $db = new PolyCor_DB();
    $mapper = new PolyCor_Category_Mapper($db);
    $attribute_manager = new PolyCor_Attribute_Manager();

    // Inicializar el núcleo con las dependencias compartidas.
    PolyCor_Dynamic_Filters::get_instance($mapper, $attribute_manager);

    // Registrar el widget con el mapper compartido cuando WordPress inicialice widgets.
    add_action('widgets_init', function() use ($mapper) {
        register_widget(new PolyCor_Filters_Widget($mapper));
    });

    // Registrar los shortcodes del renderer una sola vez.
    // Se conserva el global por compatibilidad con integraciones existentes.
    global $polycor_renderer;
    $polycor_renderer = new PolyCor_Filter_Renderer($mapper, $attribute_manager);

    // Admin page
    if (is_admin()) {
        new PolyCor_Admin_Page($mapper, $attribute_manager);
    }

    // Chips
    new Polycor_Chips();
}

    
}
add_action('plugins_loaded', 'polycor_init');





/**
 * Hook de activación
 */
if (!function_exists('polycor_activate')) {
    function polycor_activate() {
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Este plugin requiere WooCommerce. Por favor, instala y activa WooCommerce primero.');
        }
        
        // Cargar archivos necesarios para la activación
        require_once POLYCOR_PLUGIN_DIR . 'includes/class-polycor-db.php';

        // Cargar clase de base de datos
        $db = new PolyCor_DB();
        $db->create_tables();
        
        // Crear opciones por defecto
        add_option('polycor_version', POLYCOR_VERSION);
        
        // Limpiar rewrite rules
        flush_rewrite_rules();
    }
}
register_activation_hook(__FILE__, 'polycor_activate');

/**
 * Hook de desactivación
 */
if (!function_exists('polycor_deactivate')) {
    function polycor_deactivate() {
        flush_rewrite_rules();
    }
}
register_deactivation_hook(__FILE__, 'polycor_deactivate');

/**
 * Cargar estilos del frontend
 */
 /*
function polycor_enqueue_frontend_assets() {

    // Solo cargar en páginas de WooCommerce (optimiza)
    if (!is_woocommerce()) {
        return;
    }

    // CSS principal del plugin
    wp_enqueue_style(
        'polycor-filters-frontend',
        POLYCOR_PLUGIN_URL . 'assets/css/polycor-frontend.css',
        [],
        POLYCOR_VERSION
    );

    // CSS de chips (si está separado)
    wp_enqueue_style(
        'polycor-filters-chips',
        POLYCOR_PLUGIN_URL . 'assets/css/polycor-chips.css',
        ['polycor-filters-frontend'], // depende del principal
        POLYCOR_VERSION
    );
}
add_action('wp_enqueue_scripts', 'polycor_enqueue_frontend_assets');
*/