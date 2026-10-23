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
    <h1><?php _e('PolyCor Dynamic Filters - Mapeo de Filtros', 'polycor-filters'); ?></h1>
    
    <p class="description">
        <?php _e('Asocia atributos de WooCommerce con categorías de productos para mostrar filtros dinámicos en el front-end.', 'polycor-filters'); ?>
    </p>
    
    <hr class="wp-header-end">
    
    <!-- Tabs de navegación -->
    <h2 class="nav-tab-wrapper">
        <a href="#tab-mappings" class="nav-tab nav-tab-active"><?php _e('Mapeos Actuales', 'polycor-filters'); ?></a>
        <a href="#tab-new-mapping" class="nav-tab"><?php _e('Nuevo Mapeo', 'polycor-filters'); ?></a>
        <a href="#tab-clone" class="nav-tab"><?php _e('Clonar Mapeo', 'polycor-filters'); ?></a>
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
    
    <!-- Tab: Nuevo Mapeo -->
    <div id="tab-new-mapping" class="polycor-tab-content" style="display:none;">
        <div class="polycor-admin-section">
            <h2><?php _e('Crear Nuevo Mapeo', 'polycor-filters'); ?></h2>
            
            <form method="post" class="polycor-mapping-form">
                <?php wp_nonce_field('polycor_admin_action', 'polycor_nonce'); ?>
                <input type="hidden" name="polycor_action" value="save_mapping">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="category_id"><?php _e('Categoría', 'polycor-filters'); ?></label>
                        </th>
                        <td>
                            <select name="category_id" id="category_id" class="regular-text" required>
                                <option value=""><?php _e('Selecciona una categoría...', 'polycor-filters'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>">
                                        <?php echo esc_html($category->name); ?> (<?php echo esc_html($category->count); ?> productos)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Selecciona la categoría de productos a la que quieres asignar filtros', 'polycor-filters'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Atributos', 'polycor-filters'); ?></label>
                        </th>
                        <td>
                            <?php if (empty($attributes)): ?>
                                <div class="notice notice-warning inline">
                                    <p>
                                        <?php _e('No hay atributos disponibles.', 'polycor-filters'); ?>
                                        <a href="<?php echo admin_url('edit.php?post_type=product&page=product_attributes'); ?>">
                                            <?php _e('Crear atributos en WooCommerce', 'polycor-filters'); ?>
                                        </a>
                                    </p>
                                </div>
                            <?php else: ?>
                                <!-- Selector de categoría para ver atributos frecuentes -->
                                <div class="polycor-category-preview" style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Preview de Categoría:', 'polycor-filters'); ?>
                                    </label>
                                    <select id="polycor-category-preview" class="regular-text">
                                        <option value=""><?php _e('Selecciona una categoría para ver sus atributos más frecuentes...', 'polycor-filters'); ?></option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo esc_attr($cat->term_id); ?>">
                                                <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?> productos)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Contenedor principal con dos columnas -->
                                <div class="polycor-attributes-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    
                                    <!-- Columna izquierda: Atributos disponibles -->
                                    <div class="polycor-available-attrs">
                                        <h3 style="margin: 0 0 10px 0; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                                            📋 <?php _e('Atributos Disponibles', 'polycor-filters'); ?>
                                        </h3>
                                        
                                        <!-- Buscador -->
                                        <div class="polycor-search-wrapper" style="display: flex; gap: 5px; margin-bottom: 10px;">
                                            <input type="text" id="polycor-attr-search" class="regular-text" placeholder="🔍 <?php _e('Buscar atributos...', 'polycor-filters'); ?>" style="flex: 1;">
                                            <button type="button" id="polycor-attr-search-btn" class="button"><?php _e('Buscar', 'polycor-filters'); ?></button>
                                        </div>
                                        
                                        <!-- Atributos frecuentes -->
                                        <div id="polycor-frequent-attrs-list" style="display: none; margin-bottom: 15px; padding: 10px; background: #e7f5ff; border-left: 3px solid #0073aa; border-radius: 4px;">
                                            <strong style="display: block; margin-bottom: 8px;">⭐ Más usados en esta categoría:</strong>
                                            <div class="polycor-frequent-items"></div>
                                        </div>
                                        
                                        <!-- Lista de todos los atributos -->
                                        <div class="polycor-available-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background: #fff;">
                                            <?php foreach ($attributes as $attr): ?>
                                                <div class="polycor-attr-item" data-attr-slug="<?php echo esc_attr($attr['slug']); ?>" data-attr-name="<?php echo esc_attr(strtolower($attr['name'])); ?>" style="padding: 10px; border-bottom: 1px solid #f0f0f1; cursor: pointer; transition: background 0.2s;">
                                                    <div style="font-weight: 600;"><?php echo esc_html($attr['name']); ?></div>
                                                    <div style="font-size: 12px; color: #666;"><?php echo esc_html($attr['slug']); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Columna derecha: Atributos seleccionados -->
                                    <div class="polycor-selected-attrs">
                                        <h3 style="margin: 0 0 10px 0; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                                            ✅ <?php _e('Atributos Seleccionados', 'polycor-filters'); ?>
                                            <span id="polycor-selected-count" style="float: right; font-weight: normal; color: #666;">(0)</span>
                                        </h3>
                                        
                                        <div id="polycor-selected-list" class="polycor-selected-list" style="min-height: 200px; max-height: 400px; overflow-y: auto; border: 2px dashed #ddd; border-radius: 4px; padding: 10px; background: #fafafa;">
                                            <p class="polycor-empty-message" style="text-align: center; color: #999; padding: 40px 10px;">
                                                <?php _e('Haz clic en los atributos de la izquierda para agregarlos aquí', 'polycor-filters'); ?>
                                            </p>
                                        </div>
                                        
                                        <!-- Hidden inputs para enviar al servidor -->
                                        <div id="polycor-hidden-inputs"></div>
                                    </div>
                                </div>
                                
                                <p class="description">
                                    💡 <?php _e('Haz clic en un atributo para seleccionarlo. Arrastra para reordenar en la columna derecha.', 'polycor-filters'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php if (!empty($attributes)): ?>
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <?php _e('Guardar Mapeo', 'polycor-filters'); ?>
                        </button>
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Tab: Clonar Mapeo -->
    <div id="tab-clone" class="polycor-tab-content" style="display:none;">
        <div class="polycor-admin-section">
            <h2><?php _e('Clonar Mapeo Existente', 'polycor-filters'); ?></h2>
            
            <form method="post" class="polycor-clone-form">
                <?php wp_nonce_field('polycor_admin_action', 'polycor_nonce'); ?>
                <input type="hidden" name="polycor_action" value="clone_mapping">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="source_category"><?php _e('Categoría Origen', 'polycor-filters'); ?></label>
                        </th>
                        <td>
                            <select name="source_category" id="source_category" class="regular-text" required>
                                <option value=""><?php _e('Selecciona...', 'polycor-filters'); ?></option>
                                <?php foreach ($mappings_summary as $cat_id => $data): ?>
                                    <option value="<?php echo esc_attr($cat_id); ?>">
                                        <?php echo esc_html($data['category']['name']); ?> (<?php echo esc_html($data['count']); ?> atributos)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="target_category"><?php _e('Categoría Destino', 'polycor-filters'); ?></label>
                        </th>
                        <td>
                            <select name="target_category" id="target_category" class="regular-text" required>
                                <option value=""><?php _e('Selecciona...', 'polycor-filters'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Los mapeos existentes en la categoría destino serán reemplazados', 'polycor-filters'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Clonar Mapeo', 'polycor-filters'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Tab: Shortcode -->
    <div id="tab-shortcode" class="polycor-tab-content" style="display:none;">
        <div class="polycor-admin-section">
            <h2><?php _e('Uso del Shortcode', 'polycor-filters'); ?></h2>
            
            <div class="polycor-shortcode-info">
                <h3><?php _e('Shortcode Básico', 'polycor-filters'); ?></h3>
                <code class="polycor-code-block">[polycor_filters]</code>
                <p class="description">
                    <?php _e('Detecta automáticamente la categoría actual y muestra los filtros configurados.', 'polycor-filters'); ?>
                </p>
                
                <h3><?php _e('Shortcode con Categoría Específica', 'polycor-filters'); ?></h3>
                <code class="polycor-code-block">[polycor_filters category_id="23"]</code>
                <p class="description">
                    <?php _e('Muestra filtros para una categoría específica (reemplaza 23 con el ID de tu categoría).', 'polycor-filters'); ?>
                </p>
                
                <h3><?php _e('Parámetros Disponibles', 'polycor-filters'); ?></h3>
                <ul class="polycor-param-list">
                    <li>
                        <strong>category_id</strong>: <?php _e('ID de categoría (opcional, detecta automática)', 'polycor-filters'); ?>
                    </li>
                    <li>
                        <strong>layout</strong>: <?php _e('"vertical" o "horizontal" (por defecto: vertical)', 'polycor-filters'); ?>
                    </li>
                    <li>
                        <strong>show_count</strong>: <?php _e('"yes" o "no" para mostrar conteo de productos (por defecto: yes)', 'polycor-filters'); ?>
                    </li>
                    <li>
                        <strong>style</strong>: <?php _e('"checkboxes" o "buttons" (por defecto: checkboxes)', 'polycor-filters'); ?>
                    </li>
                </ul>
                
                <h3><?php _e('Ejemplo Completo', 'polycor-filters'); ?></h3>
                <code class="polycor-code-block">[polycor_filters category_id="23" layout="horizontal" show_count="no" style="buttons"]</code>
            </div>
        </div>
    </div>
</div>