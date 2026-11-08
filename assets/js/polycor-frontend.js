/**
 * JavaScript del frontend PolyCor Dynamic Filters
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    const PolycorFilters = {
        
        /**
         * Inicializar
         */
        init: function() {
            // La lógica AJAX ha sido desactivada. Los filtros ahora recargan la página.
            this.setupDropdowns();   // Mantenemos los desplegables
        },

        /**
         * Configura los títulos de los filtros como desplegables.
         */
        setupDropdowns: function() {
            $('body').on('click', '.polycor-filter-title', function() {
                const $button = $(this);
                const $group = $button.closest('.polycor-filter-group');
                const isExpanded = $button.attr('aria-expanded') === 'true';

                $button.attr('aria-expanded', String(!isExpanded));
                $group.toggleClass('active', !isExpanded);
            });
        },
        
        /**
         * Configura el filtrado AJAX
         */
        setupAjaxFilters: function() { /* La funcionalidad AJAX ha sido desactivada. */ },
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        PolycorFilters.init();
    });
    
    // Exponer API pública
    window.PolycorFilters = PolycorFilters;
    
})(jQuery);