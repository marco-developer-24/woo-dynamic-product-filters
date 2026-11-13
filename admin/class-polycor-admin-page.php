<?php
/**
 * Clase para el panel de administración
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class PolyCor_Admin_Page {

    private $category_mapper;
    private $attribute_manager;

    public function __construct($category_mapper, $attribute_manager) {
        $this->category_mapper   = $category_mapper;
        $this->attribute_manager = $attribute_manager;

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'process_form_submission'));

        // Registrar AJAX siempre
        add_action('wp_ajax_polycor_delete_mapping', array($this, 'ajax_delete_mapping'));
        add_action('wp_ajax_polycor_update_order', array($this, 'ajax_update_order'));
        add_action('wp_ajax_polycor_get_frequent_attributes', array($this, 'ajax_get_frequent_attributes'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Dynamic Filters', 'polycor-filters'),
            __('Dynamic Filters', 'polycor-filters'),
            'manage_woocommerce',
            'polycor-filters',
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page() {

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'polycor-filters'));
        }

        $action      = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $category_id = isset($_GET['category_id']) ? absint($_GET['category_id']) : 0;

        /**
         * ----------------------------
         * MODO EDICIÓN
         * ----------------------------
         */
        if ($action === 'edit' && $category_id > 0) {

    // Obtener info de categoría desde el Mapper
    $cat_info = $this->category_mapper->get_category_info($category_id);

    // Validación fuerte
    if (!$cat_info || !is_array($cat_info)) {
        wp_die(__('No se encontró la categoría solicitada.', 'polycor-filters'));
    }

    // Convertir array al formato que espera la vista
    $category = [
        'id'   => isset($cat_info['id']) ? $cat_info['id'] : 0,
        'name' => isset($cat_info['name']) ? $cat_info['name'] : ''
    ];

    if ($category['id'] === 0) {
        wp_die(__('Categoría inválida o sin datos.', 'polycor-filters'));
    }

    // Atributos seleccionados en esta categoría
    $selected_attributes = $this->category_mapper->get_attributes_by_category($category_id);
    if (!is_array($selected_attributes)) $selected_attributes = [];

    // Todos los atributos disponibles
    $attributes = $this->attribute_manager->get_all_attributes();
    if (!is_array($attributes)) $attributes = [];

    // Si el guardado anterior falló, recuperar la selección enviada en lugar de
    // reemplazarla silenciosamente por los datos persistidos.
    $failed_submission_key = 'polycor_save_error_' . get_current_user_id();
    $failed_submission = get_transient($failed_submission_key);

    if (
        is_array($failed_submission) &&
        absint($failed_submission['category_id'] ?? 0) === $category_id
    ) {
        delete_transient($failed_submission_key);
        $failed_slugs = isset($failed_submission['attributes']) && is_array($failed_submission['attributes'])
            ? $failed_submission['attributes']
            : [];

        $attributes_by_slug = [];
        foreach ($attributes as $attribute) {
            if (!empty($attribute['slug'])) {
                $attributes_by_slug[$attribute['slug']] = $attribute;
            }
        }

        $selected_attributes = [];
        foreach ($failed_slugs as $slug) {
            if (isset($attributes_by_slug[$slug])) {
                $selected_attributes[] = $attributes_by_slug[$slug];
            }
        }
    }

    include POLYCOR_PLUGIN_DIR . 'admin/views/admin-edit-mapping.php';
    return;
}

        /**
         * ----------------------------
         * MODO NORMAL (lista / tabs)
         * ----------------------------
         */
        $categories       = $this->category_mapper->get_all_product_categories();
        $attributes       = $this->attribute_manager->get_all_attributes();
        $mappings_summary = $this->category_mapper->get_mappings_summary();

        include POLYCOR_PLUGIN_DIR . 'admin/views/admin-mapping.php';
    }

    public function process_form_submission() {

        if (!isset($_POST['polycor_action'])) return;

        if (!isset($_POST['polycor_nonce']) || !wp_verify_nonce($_POST['polycor_nonce'], 'polycor_admin_action')) {
            wp_die(__('Error de seguridad', 'polycor-filters'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos', 'polycor-filters'));
        }

        $action = sanitize_text_field($_POST['polycor_action']);

        switch ($action) {
            case 'save_mapping':
                $this->process_save_mapping();
                break;
            case 'delete_mapping':
                $this->process_delete_mapping();
                break;
        }
    }

    private function process_save_mapping() {

        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        $raw_attributes = isset($_POST['attributes']) && is_array($_POST['attributes'])
            ? wp_unslash($_POST['attributes'])
            : [];

        $clean = [];
        foreach ($raw_attributes as $attribute) {
            if (!is_scalar($attribute)) {
                continue;
            }

            $slug = sanitize_text_field((string) $attribute);
            if ($slug !== '') {
                $clean[] = $slug;
            }
        }
        $clean = array_values(array_unique($clean));

        if (!$category_id) {
            $this->add_admin_notice('error', __('Debes seleccionar una categoría', 'polycor-filters'));
            wp_safe_redirect(admin_url('admin.php?page=polycor-filters'));
            exit;
        }

        if (empty($clean)) {
            $this->preserve_failed_submission($category_id, $clean);
            $this->add_admin_notice('error', __('Debes seleccionar al menos una característica', 'polycor-filters'));
            $this->redirect_to_edit_form($category_id);
        }

        $result = $this->category_mapper->assign_attributes_to_category($category_id, $clean);

        if ($result) {
            $this->add_admin_notice('success', __('La configuración se guardó correctamente', 'polycor-filters'));
            wp_safe_redirect(admin_url('admin.php?page=polycor-filters'));
            exit;
        }

        $this->preserve_failed_submission($category_id, $clean);
        $this->add_admin_notice('error', __('No pudimos guardar la configuración. Tus cambios se conservaron en el formulario para que puedas volver a intentarlo.', 'polycor-filters'));
        $this->redirect_to_edit_form($category_id);
    }

    /**
     * Conservar temporalmente la selección enviada cuando falla el guardado.
     */
    private function preserve_failed_submission($category_id, $attributes) {
        set_transient(
            'polycor_save_error_' . get_current_user_id(),
            [
                'category_id' => absint($category_id),
                'attributes'  => array_values(array_unique(array_map('sanitize_title', $attributes))),
            ],
            5 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Redirigir a edición para evitar el reenvío del POST y recuperar el formulario.
     */
    private function redirect_to_edit_form($category_id) {
        $category = $this->category_mapper->get_category_info($category_id);

        if (!$category) {
            wp_safe_redirect(admin_url('admin.php?page=polycor-filters'));
            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'        => 'polycor-filters',
                    'action'      => 'edit',
                    'category_id' => absint($category_id),
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    private function process_delete_mapping() {

        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;

        if (!$category_id) {
            $this->add_admin_notice('error', __('ID de categoría inválido', 'polycor-filters'));
            return;
        }

        $result = $this->category_mapper->get_db()->delete_mappings_by_category($category_id);

        if ($result) {
            $this->add_admin_notice('success', __('Mapeo eliminado correctamente', 'polycor-filters'));
        } else {
            $this->add_admin_notice('error', __('Error al eliminar el mapeo', 'polycor-filters'));
        }
    }

    private function add_admin_notice($type, $message) {
        add_settings_error('polycor_messages', 'polycor_message', $message, $type);
        set_transient('polycor_admin_notices', get_settings_errors('polycor_messages'), 30);
    }

    /* AJAX */
    public function ajax_delete_mapping() {
        check_ajax_referer('polycor_admin_action', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message' => 'Sin permisos']);

        $mapping_id = isset($_POST['mapping_id']) ? absint($_POST['mapping_id']) : 0;
        if (!$mapping_id) wp_send_json_error(['message'=>'ID inválido']);

        $result = $this->category_mapper->remove_attribute_from_category($mapping_id);

        $result ? wp_send_json_success(['message'=>'Eliminado correctamente'])
                : wp_send_json_error(['message'=>'Error al eliminar']);
    }

    public function ajax_update_order() {
        check_ajax_referer('polycor_admin_action', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message'=>'Sin permisos']);

        $order = isset($_POST['order']) ? $_POST['order'] : [];
        if (!$order) wp_send_json_error(['message'=>'Datos vacíos']);

        $clean = [];
        foreach ($order as $id => $n) $clean[absint($id)] = absint($n);

        $result = $this->category_mapper->update_attribute_order($clean);

        $result ? wp_send_json_success(['message'=>'Orden actualizado'])
                : wp_send_json_error(['message'=>'Error al actualizar']);
    }

    public function ajax_get_frequent_attributes() {
        check_ajax_referer('polycor_admin_action', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message'=>'Sin permisos']);

        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        if (!$category_id) wp_send_json_error(['message'=>'ID inválido']);

        $attributes = wc_get_attribute_taxonomies();
        $frequent = [];

        if ($attributes) {
            foreach ($attributes as $attr) {
                $taxonomy = 'pa_' . $attr->attribute_name;

                $terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => true,
                    'object_ids' => get_objects_in_term($category_id, 'product_cat')
                ]);

                if (!is_wp_error($terms) && !empty($terms)) {
                    $frequent[] = [
                        'name'  => $attr->attribute_label,
                        'slug'  => $attr->attribute_name,
                        'count' => count($terms)
                    ];
                }
            }
        }

        wp_send_json_success(['attributes' => $frequent]);
    }

}
