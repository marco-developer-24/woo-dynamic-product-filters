<?php
/**
 * Script de desinstalación de PolyCor Dynamic Filters
 * 
 * Se ejecuta cuando el plugin es eliminado desde WordPress
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

// Si no se llama desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Eliminar tabla de la base de datos
 */
function polycor_uninstall_database() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'polycor_cat_attrs';
    
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}

/**
 * Eliminar opciones del plugin
 */
function polycor_uninstall_options() {
    delete_option('polycor_version');
    delete_option('polycor_settings');
    
    // Eliminar transients
    delete_transient('polycor_admin_notices');
}

/**
 * Limpiar caché
 */
function polycor_uninstall_cache() {
    wp_cache_flush();
}

// Ejecutar limpieza
polycor_uninstall_database();
polycor_uninstall_options();
polycor_uninstall_cache();