<?php
/**
 * Vista de edición. Reutiliza el formulario compartido de creación.
 *
 * @package PolyCor_Dynamic_Filters
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = true;
?>
<div class="wrap polycor-admin-wrap">
    <h1>
        <?php esc_html_e('Editar configuración de filtros', 'polycor-filters'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=polycor-filters')); ?>" class="page-title-action">
            <?php esc_html_e('Volver a configuraciones', 'polycor-filters'); ?>
        </a>
    </h1>

    <div class="polycor-admin-section">
        <?php include POLYCOR_PLUGIN_DIR . 'admin/views/partials/mapping-form.php'; ?>
    </div>
</div>
