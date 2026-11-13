<?php
/**
 * Formulario compartido para crear y editar configuraciones de filtros.
 *
 * Variables esperadas:
 * - $is_edit (bool)
 * - $category (array) en modo edición
 * - $categories (array) en modo creación
 * - $attributes (array)
 * - $selected_attributes (array)
 *
 * @package PolyCor_Dynamic_Filters
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = !empty($is_edit);
$selected_attributes = isset($selected_attributes) && is_array($selected_attributes)
    ? $selected_attributes
    : [];
?>

<form method="post" class="polycor-mapping-form">
    <?php wp_nonce_field('polycor_admin_action', 'polycor_nonce'); ?>
    <input type="hidden" name="polycor_action" value="save_mapping">

    <?php if ($is_edit): ?>
        <input type="hidden" name="category_id" value="<?php echo esc_attr($category['id']); ?>">
        <div class="polycor-form-context">
            <h3><?php esc_html_e('Categoría', 'polycor-filters'); ?></h3>
            <p>
                <strong><?php echo esc_html($category['name']); ?></strong>
                <span class="description"><?php printf(esc_html__('ID: %d', 'polycor-filters'), absint($category['id'])); ?></span>
            </p>
            <p class="description"><?php esc_html_e('Estás editando los filtros de esta categoría.', 'polycor-filters'); ?></p>
        </div>
    <?php else: ?>
        <div class="polycor-form-context">
            <h3><label for="category_id"><?php esc_html_e('¿En qué categoría querés configurar los filtros?', 'polycor-filters'); ?></label></h3>
            <p class="description"><?php esc_html_e('Los filtros que elijas se mostrarán para los productos de esta categoría.', 'polycor-filters'); ?></p>
            <select name="category_id" id="category_id" class="regular-text" required>
                <option value=""><?php esc_html_e('Seleccioná una categoría...', 'polycor-filters'); ?></option>
                <?php foreach ($categories as $item): ?>
                    <option value="<?php echo esc_attr($item->term_id); ?>">
                        <?php echo esc_html($item->name); ?> (<?php echo esc_html($item->count); ?> productos)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="notice notice-warning inline polycor-save-warning">
        <p><?php esc_html_e('Al guardar, esta selección reemplaza la configuración completa de filtros de la categoría. No se agregan características a la selección anterior.', 'polycor-filters'); ?></p>
    </div>

    <div class="polycor-form-attributes">
        <h3><?php esc_html_e('¿Qué características querés que tus clientes puedan filtrar?', 'polycor-filters'); ?></h3>
        <p class="description"><?php esc_html_e('Por ejemplo: marca, procesador, memoria RAM o color. Elegí las características y organizalas en el orden en que querés mostrarlas.', 'polycor-filters'); ?></p>

        <?php if (empty($attributes)): ?>
            <div class="notice notice-warning inline">
                <p>
                    <?php esc_html_e('Todavía no hay características disponibles.', 'polycor-filters'); ?>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=product&page=product_attributes')); ?>">
                        <?php esc_html_e('Crear características en WooCommerce', 'polycor-filters'); ?>
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div class="polycor-attributes-container">
                <section class="polycor-available-attrs" aria-labelledby="polycor-available-heading">
                    <h3 id="polycor-available-heading"><?php esc_html_e('Características disponibles', 'polycor-filters'); ?></h3>

                    <div class="polycor-search-wrapper">
                        <label class="screen-reader-text" for="polycor-attr-search"><?php esc_html_e('Buscar características', 'polycor-filters'); ?></label>
                        <input type="search" id="polycor-attr-search" class="regular-text" placeholder="<?php esc_attr_e('Buscar características...', 'polycor-filters'); ?>">
                        <button type="button" id="polycor-attr-search-btn" class="button"><?php esc_html_e('Buscar', 'polycor-filters'); ?></button>
                    </div>

                    <div id="polycor-frequent-attrs-list" class="polycor-frequent-attrs-list" hidden>
                        <strong><?php esc_html_e('Con valores en esta categoría', 'polycor-filters'); ?></strong>
                        <div class="polycor-frequent-items"></div>
                    </div>

                    <div class="polycor-available-list">
                        <?php foreach ($attributes as $attr): ?>
                            <button
                                type="button"
                                class="polycor-attr-item"
                                data-attr-slug="<?php echo esc_attr($attr['slug']); ?>"
                                data-attr-name="<?php echo esc_attr(strtolower($attr['name'])); ?>"
                            >
                                <span class="polycor-attr-name"><?php echo esc_html($attr['name']); ?></span>
                                <span class="polycor-attr-slug"><?php echo esc_html($attr['slug']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="polycor-selected-attrs" aria-labelledby="polycor-selected-heading">
                    <h3 id="polycor-selected-heading">
                        <?php esc_html_e('Características seleccionadas', 'polycor-filters'); ?>
                        <span id="polycor-selected-count">(<?php echo esc_html(count($selected_attributes)); ?>)</span>
                    </h3>

                    <div id="polycor-selected-list" class="polycor-selected-list" aria-live="polite">
                        <?php if (empty($selected_attributes)): ?>
                            <p class="polycor-empty-message"><?php esc_html_e('Elegí características de la lista para agregarlas acá.', 'polycor-filters'); ?></p>
                        <?php else: ?>
                            <?php foreach ($selected_attributes as $attr): ?>
                                <div class="polycor-selected-item" data-slug="<?php echo esc_attr($attr['slug']); ?>">
                                    <span class="polycor-drag-handle dashicons dashicons-menu" aria-hidden="true"></span>
                                    <span class="polycor-selected-name"><?php echo esc_html($attr['name']); ?></span>
                                    <button type="button" class="button button-small polycor-remove-attr" aria-label="<?php echo esc_attr(sprintf(__('Quitar %s', 'polycor-filters'), $attr['name'])); ?>">×</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div id="polycor-hidden-inputs">
                        <?php foreach ($selected_attributes as $attr): ?>
                            <input type="hidden" name="attributes[]" value="<?php echo esc_attr($attr['slug']); ?>">
                        <?php endforeach; ?>
                    </div>
                    <p class="description"><?php esc_html_e('Podés cambiar el orden de las características seleccionadas.', 'polycor-filters'); ?></p>
                </section>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($attributes)): ?>
        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php echo $is_edit ? esc_html__('Guardar cambios', 'polycor-filters') : esc_html__('Guardar configuración', 'polycor-filters'); ?>
            </button>
        </p>
    <?php endif; ?>
</form>
