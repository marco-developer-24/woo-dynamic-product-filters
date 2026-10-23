/**
 * JavaScript del panel de administración PolyCor Dynamic Filters
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    const PolycorAdmin = {
        
        /**
         * Inicializar
         */
        init: function() {
            this.setupTabs();
            this.setupSortable();
            this.setupDeleteButtons();            
            this.setupFormValidation();

            // 👇 Importante: siempre inicializar la lógica de mapeo
            // tanto para "Nuevo Mapeo" como para "Editar Mapeo"
            this.setupMappingForm();
        },
        
        /* ============================================================
         * 🔍 Buscador de atributos (con fuzzy search simple)
         * ============================================================ */

        /**
         * Puntaje simple de "proximidad" entre búsqueda y texto
         */
        fuzzyScore: function(term, text) {
            if (!text) return 0;

            term = term.toLowerCase();
            text = text.toLowerCase();

            if (term === '') return 0;

            // Coincidencia exacta → score alto
            if (term === text) return 200;

            let score = 0;
            let ti = 0;

            for (let i = 0; i < term.length; i++) {
                const ch = term[i];
                const idx = text.indexOf(ch, ti);
                if (idx === -1) {
                    return 0; // si falta una letra en orden, descartamos
                }
                const penalty = Math.max(0, i - idx);
                score += 10 - penalty;
                ti = idx + 1;
            }

            // Bonus: empieza igual
            if (text.startsWith(term)) score += 50;

            // Bonus: coincide como palabra
            if (text.includes(' ' + term)) score += 30;

            return score;
        },

        /**
         * Configurar buscador de atributos
         */
        setupAttributeSearch: function() {
            const self = this;

            const doSearch = function() {
                const term  = $('#polycor-attr-search').val().trim().toLowerCase();
                const items = $('.polycor-attr-item');

                // Si el campo está vacío, mostramos todo y salimos
                if (term === '') {
                    items.show();
                    return;
                }

                let results = [];

                items.each(function() {
                    const $el   = $(this);
                    const name  = $el.data('attr-name') || '';
                    const score = self.fuzzyScore(term, name);

                    if (score > 0) {
                        results.push({ el: $el, score: score });
                    } else {
                        $el.hide();
                    }
                });

                // Ordenar por relevancia
                results.sort(function(a, b) {
                    return b.score - a.score;
                });

                // Mostrar en orden (el DOM mantiene el orden visual)
                results.forEach(function(r) {
                    r.el.show();
                });
            };

            $('#polycor-attr-search-btn').on('click', function(e) {
                e.preventDefault();
                doSearch();
            });

            // Buscar mientras escribe
            $('#polycor-attr-search').on('keyup', function() {
                doSearch();
            });
        },

        /* ============================================================
         * Formulario de Mapeo (Nuevo + Editar)
         * ============================================================ */

        /**
         * Configurar toda la lógica del formulario de mapeo
         * (se usa tanto en "Nuevo Mapeo" como en "Editar Mapeo")
         */
        setupMappingForm: function() {
            // Si no existe el contenedor de atributos, no hacemos nada
            if ($('.polycor-attributes-container').length === 0) {
                return;
            }

            this.setupAttributeSearch();
            this.setupCategoryPreview();
            this.setupAttributeSelection();
            this.setupRemoveAttribute();
            this.setupSelectedSortable();
        },

        /**
         * Configurar la selección de atributos
         */
        setupAttributeSelection: function() {
            // Click en atributo disponible para seleccionarlo
            $(document).on('click', '.polycor-attr-item', function() {
                const slug = $(this).data('attr-slug');
                const name = $(this).find('div:first').text();
                PolycorAdmin.addSelectedAttribute(slug, name);
            });
        },
        
        /**
         * Agregar atributo seleccionado
         */
        addSelectedAttribute: function(slug, name) {
            // Verificar si ya está seleccionado
            if ($('#polycor-selected-list').find('.polycor-selected-item[data-slug="' + slug + '"]').length > 0) {
                return;
            }
            
            // Ocultar mensaje vacío
            $('.polycor-empty-message').hide();
            
            // Crear item
            const $item = $(
                '<div class="polycor-selected-item" data-slug="' + slug + '" ' +
                'style="padding: 8px 10px; margin-bottom: 5px; background: #fff; ' +
                'border: 1px solid #ddd; border-radius: 4px; display: flex; ' +
                'align-items: center; gap: 10px;">' +
                    '<span class="polycor-drag-handle dashicons dashicons-menu" style="color: #999;"></span>' +
                    '<span style="flex: 1; font-weight: 600;">' + name + '</span>' +
                    '<button type="button" class="polycor-remove-attr button button-small" style="padding: 2px 8px;">&times;</button>' +
                '</div>'
            );
            
            $('#polycor-selected-list').append($item);
            
            this.updateHiddenInputs();
            this.updateSelectedCount();
        },
        
        /**
         * Actualizar inputs hidden
         */
        updateHiddenInputs: function() {
            const $container = $('#polycor-hidden-inputs');
            $container.empty();
            
            $('#polycor-selected-list .polycor-selected-item').each(function() {
                const slug = $(this).data('slug');
                $container.append('<input type="hidden" name="attributes[]" value="' + slug + '">');
            });
        },
        
        /**
         * Actualizar contador
         */
        updateSelectedCount: function() {
            const count = $('#polycor-selected-list .polycor-selected-item').length;
            $('#polycor-selected-count').text('(' + count + ')');
            
            if (count === 0) {
                $('.polycor-empty-message').show();
            }
        },
        
        /**
         * Eliminar atributo
         */
        setupRemoveAttribute: function() {
            $(document).on('click', '.polycor-remove-attr', function() {
                $(this).closest('.polycor-selected-item').remove();
                PolycorAdmin.updateHiddenInputs();
                PolycorAdmin.updateSelectedCount();
            });
        },
        
        /* ============================================================
         * Atributos frecuentes por categoría
         * (solo se usa en "Nuevo Mapeo")
         * ============================================================ */
        setupCategoryPreview: function() {
            const self = this;
            
            $('#polycor-category-preview').on('change', function() {
                const categoryId = $(this).val();
                
                if (!categoryId) {
                    $('#polycor-frequent-attrs-list').hide();
                    return;
                }
                
                // Sincronizar con el select principal
                $('#category_id').val(categoryId);
                
                self.loadFrequentAttributes(categoryId);
            });
        },
        
        loadFrequentAttributes: function(categoryId) {
            const $container    = $('#polycor-frequent-attrs-list .polycor-frequent-items');
            const $listWrapper  = $('#polycor-frequent-attrs-list');
            
            if ($container.length === 0) return;
            
            $container.html('<span class="spinner is-active" style="float:none; margin: 0 5px 0 0;"></span>Cargando...');
            $listWrapper.show();
            
            $.ajax({
                url:  polycorAdminData.ajaxUrl,
                type: 'POST',
                data: {
                    action:      'polycor_get_frequent_attributes',
                    nonce:       polycorAdminData.nonce,
                    category_id: categoryId
                },
                success: function(response) {
                    if (!response || !response.success || !response.data) {
                        $container.html('<p class="description">Error al cargar atributos frecuentes.</p>');
                        return;
                    }

                    const attrs = response.data.attributes || [];
                    let html    = '';
                    
                    if (attrs.length === 0) {
                        html = '<p class="description">' + (polycorAdminData.noFrequentAttrs || 'No hay atributos frecuentes.') + '</p>';
                    } else {
                        attrs.forEach(function(attr) {
                            const slug = attr.slug;
                            const name = attr.name;
                            const cnt  = attr.count;

                            html +=
                                '<div class="polycor-attr-item polycor-frequent-item" ' +
                                     'data-attr-slug="' + slug + '" ' +
                                     'data-attr-name="' + name.toLowerCase() + '" ' +
                                     'style="padding: 8px; border-bottom: 1px solid #e0e0e0; cursor: pointer;">' +
                                    '<div style="font-weight: 600;">' + name + '</div>' +
                                    '<div style="font-size: 11px; color: #666;">(' + cnt + ' productos)</div>' +
                                '</div>';
                        });
                    }
                    
                    $container.html(html);
                },
                error: function() {
                    $container.html('<p class="description" style="color:#d63638;">Error al cargar atributos frecuentes.</p>');
                }
            });
        },
        
        /* ============================================================
         * Tabs
         * ============================================================ */
        setupTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                const target = $(this).attr('href');
                
                // Actualizar tabs activos
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Mostrar/ocultar contenido
                $('.polycor-tab-content').hide();
                $(target).show();
            });
            
            // Activar tab desde hash de URL
            if (window.location.hash) {
                const hash = window.location.hash;
                $('.nav-tab[href="' + hash + '"]').trigger('click');
            }
        },
        
        /* ============================================================
         * Ordenamiento drag & drop en la tabla principal
         * ============================================================ */
        setupSortable: function() {
            const self = this;
            
            $('.polycor-attribute-list').sortable({
                handle: '.polycor-drag-handle',
                placeholder: 'polycor-sortable-placeholder',
                cursor: 'move',
                opacity: 0.8,
                update: function() {
                    const categoryId = $(this).data('category');
                    const orderData  = {};
                    
                    $(this).find('li').each(function(index) {
                        const mappingId = $(this).data('id');
                        orderData[mappingId] = index;
                    });
                    
                    self.updateOrder(categoryId, orderData);
                }
            });
        },
        
        updateOrder: function(categoryId, orderData) {
            $.ajax({
                url:  polycorAdminData.ajaxUrl,
                type: 'POST',
                data: {
                    action:      'polycor_update_order',
                    nonce:       polycorAdminData.nonce,
                    category_id: categoryId,
                    order:       orderData
                },
                success: function(response) {
                    if (response && response.success) {
                        PolycorAdmin.showNotice('success', response.data.message || 'Orden actualizado');
                    } else {
                        PolycorAdmin.showNotice('error', (response && response.data && response.data.message) || 'Error al actualizar el orden');
                    }
                },
                error: function() {
                    PolycorAdmin.showNotice('error', 'Error al actualizar el orden');
                }
            });
        },
        
        /**
         * Hacer que la lista de atributos seleccionados sea ordenable
         */
        setupSelectedSortable: function() {
            if (typeof $.fn.sortable !== 'undefined') {
                $('#polycor-selected-list').sortable({
                    handle: '.polycor-drag-handle',
                    placeholder: 'polycor-sortable-placeholder',
                    update: function() {
                        PolycorAdmin.updateHiddenInputs();
                    }
                });
            }
        },

        /* ============================================================
         * Botones de eliminar (tabla de mapeos)
         * ============================================================ */
        setupDeleteButtons: function() {
            const self = this;
            
            $(document).on('click', '.polycor-delete-attr', function(e) {
                e.preventDefault();
                
                if (!confirm(polycorAdminData.confirmDelete || '¿Eliminar este atributo?')) {
                    return;
                }
                
                const $button   = $(this);
                const mappingId = $button.data('id');
                const $listItem = $button.closest('li');
                
                $button.prop('disabled', true);
                
                $.ajax({
                    url:  polycorAdminData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action:     'polycor_delete_mapping',
                        nonce:      polycorAdminData.nonce,
                        mapping_id: mappingId
                    },
                    success: function(response) {
                        if (response && response.success) {
                            $listItem.fadeOut(300, function() {
                                $(this).remove();
                                self.updateCategoryCount($listItem.closest('tr'));
                            });
                            self.showNotice('success', response.data.message || 'Eliminado correctamente');
                        } else {
                            self.showNotice('error', (response && response.data && response.data.message) || 'Error al eliminar');
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        self.showNotice('error', 'Error al eliminar');
                        $button.prop('disabled', false);
                    }
                });
            });
        },
        
        /**
         * Actualizar conteo de categoría (tabla principal)
         */
        updateCategoryCount: function($row) {
            const $list  = $row.find('.polycor-attribute-list');
            const count  = $list.find('li').length;
            const $badge = $row.find('.polycor-count-badge');
            
            $badge.text(count);
            
            // Si no quedan atributos, eliminar la fila
            if (count === 0) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        },
        
        /* ============================================================
         * Validación de formularios
         * ============================================================ */
        setupFormValidation: function() {
            // Validar que se seleccione al menos un atributo
            $('form.polycor-mapping-form').on('submit', function(e) {
                const checkedCount = $(this).find('#polycor-hidden-inputs input').length;

                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('Debes seleccionar al menos un atributo');
                    return false;
                }
            });
            
            // Validar formulario de clonación
            $('form.polycor-clone-form').on('submit', function(e) {
                const sourceId = $('#source_category').val();
                const targetId = $('#target_category').val();
                
                if (!sourceId || !targetId) {
                    e.preventDefault();
                    alert('Debes seleccionar ambas categorías');
                    return false;
                }
                
                if (sourceId === targetId) {
                    e.preventDefault();
                    alert('Las categorías deben ser diferentes');
                    return false;
                }
            });
        },
        
        /**
         * Mostrar notificación
         */
        showNotice: function(type, message) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.polycor-admin-wrap h1').after($notice);
            
            // Auto-cerrar después de 3 segundos
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        PolycorAdmin.init();
    });
    
})(jQuery);
