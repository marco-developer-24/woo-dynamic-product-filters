<?php
/**
 * Vista del panel de administración
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mostrar notificaciones
$notices = get_transient('polycor_admin_notices');
if ($notices) {
    delete_transient('polycor_admin_notices');
    foreach ($notices as $notice) {
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($notice['type']),
            esc_html($notice['message'])
        );
    }
}
?>

<div class="wrap polycor-admin-wrap">
    <h1><?php _e('Dynamic Filters - Mapeo de Filtros', 'polycor-filters'); ?></h1>
    
    <p class="description">
        <?php _e('Asocia atributos de WooCommerce con categorías de productos para mostrar filtros dinámicos en el front-end.', 'polycor-filters'); ?>
    </p>
    
    <hr class="wp-header-end">
    
    <!-- Tabs de navegación -->
    <h2 class="nav-tab-wrapper">
        <a href="#tab-mappings" class="nav-tab nav-tab-active"><?php _e('Mapeos Actuales', 'polycor-filters'); ?></a>
        <a href="#tab-new-mapping" class="nav-tab"><?php _e('Nuevo Mapeo', 'polycor-filters'); ?></a>
        <a href="#tab-shortcode" class="nav-tab"><?php _e('Uso del Shortcode', 'polycor-filters'); ?></a>
    </h2>
    
    <!-- Tab: Mapeos Actuales -->
    <div id="tab-mappings" class="polycor-tab-content">
        <div class="polycor-admin-section">
            <h2><?php _e('Mapeos Configurados', 'polycor-filters'); ?></h2>
            
            <?php if (empty($mappings_summary)): ?>
                <div class="notice notice-info inline">
                    <p><?php _e('No hay mapeos configurados aún. Crea uno en la pestaña "Nuevo Mapeo".', 'polycor-filters'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Categoría', 'polycor-filters'); ?></th>
                            <th><?php _e('Atributos Asignados', 'polycor-filters'); ?></th>
                            <th><?php _e('Cantidad', 'polycor-filters'); ?></th>
                            <th><?php _e('Acciones', 'polycor-filters'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings_summary as $cat_id => $data): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($data['category']['name']); ?></strong>
                                    <br>
                                    <small class="description">ID: <?php echo esc_html($cat_id); ?></small>
                                </td>
                                <td>
                                    <ul class="polycor-attribute-list" data-category="<?php echo esc_attr($cat_id); ?>">
                                        <?php foreach ($data['attributes'] as $attr): ?>
                                            <?php 
                                            $attr_label = $this->attribute_manager->get_attribute_label($attr['slug']); 
                                            ?>
                                            <li data-id="<?php echo esc_attr($attr['id']); ?>" data-order="<?php echo esc_attr($attr['display_order']); ?>">
                                                <span class="dashicons dashicons-menu polycor-drag-handle"></span>
                                                <span class="polycor-attr-name"><?php echo esc_html($attr_label); ?></span>
                                                <span class="polycor-attr-slug">(<?php echo esc_html($attr['slug']); ?>)</span>
                                                <button type="button" class="button button-small polycor-delete-attr" data-id="<?php echo esc_attr($attr['id']); ?>" title="<?php _e('Eliminar', 'polycor-filters'); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <span class="polycor-count-badge"><?php echo esc_html($data['count']); ?></span>
                                </td>
                                <td>
                                    <div class="polycor-row-actions">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=polycor-filters&action=edit&category_id=' . $cat_id)); ?>" class="button button-small">
                                            <?php _e('Editar', 'polycor-filters'); ?>
                                        </a>
                                        <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('polycor_admin_action', 'polycor_nonce'); ?>
                                        <input type="hidden" name="polycor_action" value="delete_mapping">
                                        <input type="hidden" name="category_id" value="<?php echo esc_attr($cat_id); ?>">
                                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php _e('¿Eliminar todos los mapeos de esta categoría?', 'polycor-filters'); ?>');">
                                            <?php _e('Eliminar Todo', 'polycor-filters'); ?>
                                        </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tab: Nueva configuración -->
    <div id="tab-new-mapping" class="polycor-tab-content" style="display:none;">
        <div class="polycor-admin-section">
            <h2><?php esc_html_e('Configurar una categoría', 'polycor-filters'); ?></h2>
            <?php
            $is_edit = false;
            $selected_attributes = [];
            include POLYCOR_PLUGIN_DIR . 'admin/views/partials/mapping-form.php';
            ?>
        </div>
    </div>

    <div id="tab-shortcode" class="polycor-tab-content" style="display:none;">
        <div class="polycor-admin-section">
            <h2><?php esc_html_e('Cómo mostrar los filtros en tu tienda', 'polycor-filters'); ?></h2>
            <p class="description"><?php esc_html_e('Cada código cumple una función distinta. Elegí el que corresponda al lugar donde querés mostrar el contenido.', 'polycor-filters'); ?></p>

            <h3><?php esc_html_e('Filtros de una categoría', 'polycor-filters'); ?></h3>
            <code class="polycor-code-block">[polycor_filter_groups]</code>
            <p class="description"><?php esc_html_e('Muestra los grupos de filtros de la categoría actual. También podés indicar una categoría concreta:', 'polycor-filters'); ?></p>
            <code class="polycor-code-block">[polycor_filter_groups category_id="23"]</code>
            <p class="description"><?php esc_html_e('Reemplazá 23 por el ID de la categoría. Parámetros opcionales: layout (vertical u horizontal), show_count (yes o no) y style (checkboxes o buttons).', 'polycor-filters'); ?></p>

            <h3><?php esc_html_e('Filtros activos', 'polycor-filters'); ?></h3>
            <code class="polycor-code-block">[polycor_active_filters]</code>
            <p class="description"><?php esc_html_e('Muestra los filtros que el cliente ya seleccionó en la URL actual.', 'polycor-filters'); ?></p>

            <h3><?php esc_html_e('Características del producto', 'polycor-filters'); ?></h3>
            <code class="polycor-code-block">[polycor_dynamic_chips]</code>
            <p class="description"><?php esc_html_e('Muestra características configuradas como etiquetas en la página de producto.', 'polycor-filters'); ?></p>

            <p class="description"><?php esc_html_e('Copiá el código correspondiente y pegalo en el bloque de shortcode o área de contenido donde quieras mostrarlo. Después guardá la página y comprobá el resultado en tu tienda.', 'polycor-filters'); ?></p>
        </div>
    </div>
</div>