<?php
/**
 * Clase para gestionar atributos de WooCommerce
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PolyCor_Attribute_Manager {
    
    /**
     * Obtener todos los atributos de WooCommerce
     *
     * @return array Array de atributos
     */
    public function get_all_attributes() {
        $attributes = array();
        
        if (!function_exists('wc_get_attribute_taxonomies')) {
            return $attributes;
        }
        
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        
        foreach ($attribute_taxonomies as $tax) {
            $attributes[] = array(
                'id'    => $tax->attribute_id,
                'name'  => $tax->attribute_label,
                'slug'  => $tax->attribute_name,
                'type'  => $tax->attribute_type,
                'order' => $tax->attribute_orderby
            );
        }
        
        return $attributes;
    }
    
    /**
     * Obtener atributo por ID
     *
     * @param int $attribute_id ID del atributo
     * @return object|null
     */
    public function get_attribute_by_id($attribute_id) {
        if (!function_exists('wc_get_attribute')) {
            return null;
        }
        
        return wc_get_attribute($attribute_id);
    }
    
    /**
     * Obtener atributo por slug
     *
     * @param string $slug Slug del atributo
     * @return object|null
     */
    public function get_attribute_by_slug($slug) {
        global $wpdb;
        
        $attribute = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
                sanitize_title($slug)
            )
        );
        
        return $attribute;
    }
    
    /**
     * Obtener términos de un atributo
     *
     * @param string $attribute_slug Slug del atributo
     * @param array $args Argumentos adicionales
     * @return array
     */
    public function get_attribute_terms($attribute_slug, $args = array()) {
        $taxonomy = 'pa_' . sanitize_title($attribute_slug);
        
        if (!taxonomy_exists($taxonomy)) {
            return array();
        }
        
        $defaults = array(
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => true,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $terms = get_terms($taxonomy, $args);
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        return $terms;
    }
    
    /**
     * Obtener términos con conteo de productos para una categoría
     *
     * @param string $attribute_slug Slug del atributo
     * @param int $category_id ID de categoría (opcional)
     * @return array
     */

   
   /**
 * Obtener términos de un atributo SOLO con productos dentro de la categoría actual.
 *
 * @param string $attribute_slug Slug del atributo (sin "pa_")
 * @param int    $category_id ID de la categoría actual
 * @return array Lista de términos con conteo real (>0)
 */
 
 
    /**
 * Obtener términos de un atributo que tengan productos dentro de la categoría actual.
 * OPTIMIZADO con caché por atributo + categoría.
 *
 * @param string $attribute_slug
 * @param int    $category_id
 * @return array
 */
public function get_attribute_terms_with_count($attribute_slug, $category_id) {

    if (empty($attribute_slug) || empty($category_id)) {
        return [];
    }

    $taxonomy = 'pa_' . sanitize_title($attribute_slug);

    if (!taxonomy_exists($taxonomy)) {
        return [];
    }

    // ---------------------------------------------------------
    // CACHE KEY → único por categoría + atributo
    // ---------------------------------------------------------
    $cache_key = "polycor_attr_{$attribute_slug}_cat_{$category_id}";
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached; // 🚀 Devuelve cache instantáneo
    }

    // Obtener todos los términos del atributo
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ]);

    if (empty($terms) || is_wp_error($terms)) {
        return [];
    }

    // meta_query estándar de WC: respeta configuración de stock y visibilidad de catálogo
    $wc_meta_query = WC()->query->get_meta_query();

    // ---------------------------------------------------------
    // POR CADA TÉRMINO → contar productos
    // ---------------------------------------------------------
    $results = [];

    foreach ($terms as $term) {

        $products = wc_get_products([
            'status'     => 'publish',
            'limit'      => -1,
            'return'     => 'ids',
            'tax_query'  => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [$category_id],
                ],
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => [$term->slug],
                ],
            ],
            'meta_query' => $wc_meta_query,
        ]);

        $count = count($products);

        if ($count > 0) {
            $term->count = $count;
            $results[] = $term;
        }
    }

    // ---------------------------------------------------------
    // Guardar en caché 6 horas (ideal para catálogos dinámicos)
    // ---------------------------------------------------------
    set_transient($cache_key, $results, 6 * HOUR_IN_SECONDS);

    return $results;
}


    /**
     * Verificar si un atributo existe
     *
     * @param string $slug Slug del atributo
     * @return bool
     */
    public function attribute_exists($slug) {
        $taxonomy = 'pa_' . sanitize_title($slug);
        return taxonomy_exists($taxonomy);
    }
    
    /**
     * Obtener nombre legible del atributo
     *
     * @param string $slug Slug del atributo
     * @return string
     */
    public function get_attribute_label($slug) {
        $attribute = $this->get_attribute_by_slug($slug);
        
        if ($attribute && isset($attribute->attribute_label)) {
            return $attribute->attribute_label;
        }
        
        return ucfirst(str_replace('-', ' ', $slug));
    }
    
    /**
     * Obtener atributos formateados para select
     *
     * @return array Array con formato ['value' => 'label']
     */
    public function get_attributes_for_select() {
        $attributes = $this->get_all_attributes();
        $options = array();
        
        foreach ($attributes as $attr) {
            $options[$attr['slug']] = $attr['name'];
        }
        
        return $options;
    }
    
    /**
     * Validar que los atributos existen
     *
     * @param array $attribute_slugs Array de slugs
     * @return array Array de slugs válidos
     */
    public function validate_attributes($attribute_slugs) {
        $valid = array();
        
        foreach ($attribute_slugs as $slug) {
            if ($this->attribute_exists($slug)) {
                $valid[] = sanitize_title($slug);
            }
        }
        
        return $valid;
    }

    /**
 * Verificar si un término existe dentro de un atributo
 *
 * @param string $attribute_slug Slug del atributo (sin "pa_")
 * @param string $term_slug Slug del término
 * @return bool
 */
public function term_exists_in_attribute($attribute_slug, $term_slug) {

    $taxonomy = 'pa_' . sanitize_title($attribute_slug);

    if (!taxonomy_exists($taxonomy)) {
        return false;
    }

    $term = get_term_by('slug', sanitize_title($term_slug), $taxonomy);

    return ($term && !is_wp_error($term));
}


}

