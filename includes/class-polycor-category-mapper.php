<?php
/**
 * Clase para mapear categorías con atributos – Versión estable final
 *
 * @package PolyCor_Dynamic_Filters
 */

if (!defined('ABSPATH')) exit;

class PolyCor_Category_Mapper {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Obtener atributos asignados a una categoría
     */
    public function get_attributes_for_category($category_id) {
        return $this->db->get_mappings_by_category($category_id, true);
    }

    /**
     * Atributos formateados completos para admin
     */
    public function get_attributes_by_category($category_id) {

        $raw = $this->get_attributes_for_category($category_id);
        if (empty($raw)) return [];

        $result = [];

        foreach ($raw as $mapping) {

            $slug = $mapping['attribute_slug'];
            $attr = wc_get_attribute(wc_attribute_taxonomy_id_by_name($slug));

            $result[] = [
                'slug'          => $slug,
                'name'          => $attr ? $attr->name : ucfirst($slug),
                'id'            => $mapping['id'],
                'display_order' => $mapping['display_order']
            ];
        }

        return $result;
    }

    /**
     * Asignar atributos a categoría (reemplaza todo)
     */
    public function assign_attributes_to_category($category_id, $attributes) {

        $this->db->delete_mappings_by_category($category_id);

        $success = true;
        $order = 0;

        foreach ($attributes as $slug) {

            $attribute = wc_get_attribute(wc_attribute_taxonomy_id_by_name($slug));
            if (!$attribute) continue;

            $result = $this->db->insert_mapping(
                $category_id,
                $attribute->id,
                $slug,
                $order
            );

            if (!$result) $success = false;
            $order++;
        }

        return $success;
    }

    /**
     * Agregar atributo individual
     */
    public function add_attribute_to_category($category_id, $attribute_slug) {

        $attribute = wc_get_attribute(wc_attribute_taxonomy_id_by_name($attribute_slug));
        if (!$attribute) return false;

        if ($this->db->mapping_exists($category_id, $attribute->id)) return false;

        $existing = $this->db->get_mappings_by_category($category_id, false);
        $max_order = 0;

        foreach ($existing as $m) {
            if ($m['display_order'] > $max_order)
                $max_order = $m['display_order'];
        }

        return $this->db->insert_mapping(
            $category_id,
            $attribute->id,
            $attribute_slug,
            $max_order + 1
        );
    }

    /**
     * Eliminar mapeo
     */
    public function remove_attribute_from_category($mapping_id) {
        return $this->db->delete_mapping($mapping_id);
    }

    /**
     * Actualizar orden
     */
    public function update_attribute_order($order_data) {
        return $this->db->update_display_order($order_data);
    }

    /**
     * Obtener categorías
     */
    public function get_all_product_categories($args = []) {

        $defaults = [
            'taxonomy'   => 'product_cat',
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $categories = get_terms($args);
        if (is_wp_error($categories)) return [];

        return $categories;
    }

    /**
     * Categoría actual
     */
    public function get_current_category_id() {
        if (is_product_category()) {
            $obj = get_queried_object();
            return $obj->term_id ?? null;
        }
        return null;
    }

    /**
     * Info completa de categoría
     */
    public function get_category_info($category_id) {

        $category = get_term($category_id, 'product_cat');
        if (is_wp_error($category) || !$category) return null;

        return [
            'id'          => $category->term_id,
            'name'        => $category->name,
            'slug'        => $category->slug,
            'description' => $category->description,
            'count'       => $category->count,
            'parent'      => $category->parent
        ];
    }

    /**
     * ¿La categoría tiene atributos definidos?
     */
    public function category_has_attributes($category_id) {
        $m = $this->db->get_mappings_by_category($category_id, true);
        return !empty($m);
    }

    /**
     * Obtener slugs permitidos
     */
    public function get_allowed_attribute_slugs($category_id) {
        $mappings = $this->get_attributes_for_category($category_id);
        if (empty($mappings)) return [];
        return wp_list_pluck($mappings, 'attribute_slug');
    }

    /**
     * ¿La categoría tiene *este* atributo?
     */
    public function has_attribute($category_id, $attr_slug) {
        $allowed = $this->get_allowed_attribute_slugs($category_id);
        return in_array($attr_slug, $allowed, true);
    }

    /**
     * Validar claves GET tipo "filter_color"
     */
    public function is_valid_filter_key($category_id, $filter_key) {

        if (strpos($filter_key, 'filter_') !== 0) return false;

        $slug = str_replace('filter_', '', $filter_key);

        return $this->has_attribute($category_id, $slug);
    }

    /**
     * Resumen para admin
     */
    public function get_mappings_summary() {

        $m = $this->db->get_all_mappings(true);
        $summary = [];

        foreach ($m as $map) {

            $cat_id = $map['category_id'];

            if (!isset($summary[$cat_id])) {
                $summary[$cat_id] = [
                    'category'   => $this->get_category_info($cat_id),
                    'attributes' => [],
                    'count'      => 0
                ];
            }

            $summary[$cat_id]['attributes'][] = [
                'id'            => $map['id'],
                'attribute_id'  => $map['attribute_id'],
                'slug'          => $map['attribute_slug'],
                'display_order' => $map['display_order']
            ];

            $summary[$cat_id]['count']++;
        }

        return $summary;
    }

    public function get_db() {
        return $this->db;
    }
}
