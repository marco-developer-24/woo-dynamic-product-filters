<?php
/**
 * Vista: Editar mapeo de categoría
 *
 * @package PolyCor_Dynamic_Filters
 */

if (!defined('ABSPATH')) exit;

// Forzar clase CSS en el body
add_filter('admin_body_class', function($classes) {
    return $classes . ' polycor-edit-mapping-page ';
});
?>

<div class="wrap polycor-admin-wrap">

    <h1>
        <?php _e('Editar Mapeo de Filtros', 'polycor-filters'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=polycor-filters')); ?>" class="page-title-action">
            <?php _e('Volver a Mapeos', 'polycor-filters'); ?>
        </a>
    </h1>

    <div class="polycor-admin-section">
        <form method="post" class="polycor-mapping-form">

            <?php wp_nonce_field('polycor_admin_action', 'polycor_nonce'); ?>
            <input type="hidden" name="polycor_action" value="save_mapping">
            <input type="hidden" name="category_id" value="<?php echo esc_attr($category['id']); ?>">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Categoría', 'polycor-filters'); ?></label>
                    </th>
                    <td>
                        <p class="polycor-category-display">
                            <strong><?php echo esc_html($category['name']); ?></strong>
                            (ID: <?php echo esc_html($category['id']); ?>)
                        </p>
                        <p class="description"><?php _e('Estás editando los filtros para esta categoría.', 'polycor-filters'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label><?php _e('Atributos', 'polycor-filters'); ?></label></th>
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

                        <div class="polycor-attributes-container"
                             style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">

                            <!-- LEFT COLUMN -->
                            <div class="polycor-available-attrs">

                                <h3 style="margin:0 0 10px 0; padding:10px; background:#f0f0f1; border-radius:4px;">
                                    📋 <?php _e('Atributos disponibles', 'polycor-filters'); ?>
                                </h3>

                                <!-- Search -->
                                <div class="polycor-search-wrapper" style="display:flex; gap:5px; margin-bottom:10px;">
                                    <input type="text" id="polycor-attr-search" class="regular-text"
                                           placeholder="🔍 Buscar atributos..." style="flex:1;">
                                    <button type="button" id="polycor-attr-search-btn" class="button">Buscar</button>
                                </div>

                                <!-- Frequent Attributes -->
                                <div id="polycor-frequent-attrs-list"
                                     style="display:none; margin-bottom:15px; padding:10px; background:#e7f5ff;
                                            border-left:3px solid #0073aa; border-radius:4px;">
                                    <strong>⭐ Más usados:</strong>
                                    <div class="polycor-frequent-items"></div>
                                </div>

                                <!-- All Attributes -->
                                <div class="polycor-available-list"
                                     style="max-height:400px; overflow-y:auto; border:1px solid #ddd;
                                            border-radius:4px; background:#fff;">

                                    <?php foreach ($attributes as $attr): ?>
                                        <div class="polycor-attr-item"
                                             data-attr-slug="<?php echo esc_attr($attr['slug']); ?>"
                                             data-attr-name="<?php echo esc_attr(strtolower($attr['name'])); ?>"
                                             style="padding:10px; border-bottom:1px solid #f0f0f1; cursor:pointer;
                                                    transition:background 0.2s;">
                                            <div style="font-weight:600;"><?php echo esc_html($attr['name']); ?></div>
                                            <div style="font-size:12px; color:#666;"><?php echo esc_html($attr['slug']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="polycor-selected-attrs">

                                <h3 style="margin:0 0 10px 0; padding:10px; background:#f0f0f1; border-radius:4px;">
                                    ✅ <?php _e('Atributos seleccionados', 'polycor-filters'); ?>
                                    <span id="polycor-selected-count" style="float:right; color:#666;">
                                        (<?php echo count($selected_attributes); ?>)
                                    </span>
                                </h3>

                                <div id="polycor-selected-list"
                                     class="polycor-selected-list"
                                     style="min-height:200px; max-height:400px; overflow-y:auto;
                                            border:2px dashed #ddd; border-radius:4px; padding:10px; background:#fafafa;">

                                    <?php if (empty($selected_attributes)): ?>

                                        <p class="polycor-empty-message"
                                           style="text-align:center; color:#999; padding:40px 10px;">
                                            Haz clic en los atributos de la izquierda para agregarlos
                                        </p>

                                    <?php else: ?>

                                        <?php foreach ($selected_attributes as $attr): ?>
                                            <div class="polycor-selected-item"
                                                 data-slug="<?php echo esc_attr($attr['slug']); ?>"
                                                 style="padding:8px 10px; margin-bottom:5px; background:#fff;
                                                        border:1px solid #ddd; border-radius:4px; display:flex;
                                                        align-items:center; gap:10px;">
                                                <span class="polycor-drag-handle dashicons dashicons-menu"
                                                      style="color:#999;"></span>
                                                <span style="flex:1; font-weight:600;">
                                                    <?php echo esc_html($attr['name']); ?>
                                                </span>
                                                <button type="button" class="polycor-remove-attr button button-small">×</button>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </div>

                                <!-- Hidden Inputs -->
                                <div id="polycor-hidden-inputs">
                                    <?php foreach ($selected_attributes as $attr): ?>
                                        <input type="hidden" name="attributes[]" value="<?php echo esc_attr($attr['slug']); ?>">
                                    <?php endforeach; ?>
                                </div>

                            </div><!-- /RIGHT -->

                        </div><!-- /WRAP GRID -->

                        <p class="description">💡 Haz clic en un atributo para seleccionarlo. Arrastra para reordenar.</p>

                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php if (!empty($attributes)): ?>
            <p class="submit">
                <button type="submit" class="button button-primary button-large">
                    <?php _e('Actualizar Mapeo', 'polycor-filters'); ?>
                </button>
            </p>
            <?php endif; ?>
        </form>
    </div>
</div>
