<?php
/**
 * Widget para mostrar los filtros dinámicos
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class PolyCor_Filters_Widget extends WP_Widget {

    /** @var PolyCor_Category_Mapper|null */
    private $category_mapper;

    public function __construct($category_mapper = null) {
        $this->category_mapper = $category_mapper;
        parent::__construct(
            'polycor_filters_widget',
            __('PolyCor Dynamic Filters', 'polycor-filters'),
            [
                'description' => __('Muestra filtros dinámicos basados en la categoría actual.', 'polycor-filters'),
                'customize_selective_refresh' => true,
            ]
        );
    }

    /**
     * Render frontend del widget
     */
    public function widget($args, $instance) {

        /** -------------------------------------------------------
         * 1. Mostrar solo en categorías de producto
         * ------------------------------------------------------ */
        if (!is_product_category()) {
            return;
        }

        $cat_id = get_queried_object_id();
        if (!$cat_id) {
            return;
        }

        /** -------------------------------------------------------
         * 2. Usar el mapper compartido cuando lo inyecta el bootstrap.
         *    Mantener fallback para instanciaciones sin argumentos.
         * ------------------------------------------------------ */
        $mapper = $this->category_mapper;
        if (!$mapper instanceof PolyCor_Category_Mapper) {
            $mapper = new PolyCor_Category_Mapper(new PolyCor_DB());
        }

        // Si la categoría NO tiene atributos mapeados → no mostrar nada
        if (!$mapper->category_has_attributes($cat_id)) {
            return;
        }

        /** -------------------------------------------------------
         * 3. Render del widget
         * ------------------------------------------------------ */
        echo $args['before_widget'];

        // Título del widget
        $title = apply_filters(
            'widget_title',
            empty($instance['title']) ? '' : $instance['title'],
            $instance,
            $this->id_base
        );

        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Filtros activos (chips)
        echo do_shortcode('[polycor_active_filters]');

        // Grupos de filtros
        echo do_shortcode('[polycor_filter_groups]');

        echo $args['after_widget'];
    }


    /**
     * Formulario de configuración en Admin
     */
    public function form($instance) {

        $title = isset($instance['title'])
            ? esc_attr($instance['title'])
            : __('Filtrar por', 'polycor-filters');

        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Título:', 'polycor-filters'); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                type="text"
                value="<?php echo $title; ?>"
            />
        </p>

        <p>
            <?php _e('Este widget mostrará automáticamente los filtros configurados para la categoría de producto actual.', 'polycor-filters'); ?>
        </p>
        <?php
    }


    /**
     * Guardar valores del widget
     */
    public function update($new_instance, $old_instance) {

        $instance = [];
        $instance['title'] = !empty($new_instance['title'])
            ? strip_tags($new_instance['title'])
            : '';

        return $instance;
    }
}
