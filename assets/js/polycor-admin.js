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
            $(document).on('click', '.polycor-attr-item', function() {
                const slug = $(this).attr('data-attr-slug');
                const name = $(this).find('.polycor-attr-name').text() || $(this).text().trim();
                PolycorAdmin.addSelectedAttribute(slug, name);
            });
        },

        
        addSelectedAttribute: function(slug, name) {
            if (!slug || $('#polycor-selected-list .polycor-selected-item').filter(function() {
                return $(this).attr('data-slug') === slug;
            }).length > 0) {
                return;
            }

            $('.polycor-empty-message').hide();

            const $item = $('<div>', {
                'class': 'polycor-selected-item'
            }).attr('data-slug', slug);

            $('<span>', {
                'class': 'polycor-drag-handle dashicons dashicons-menu',
                'aria-hidden': 'true'
            }).appendTo($item);

            $('<span>', {
                'class': 'polycor-selected-name'
            }).text(name).appendTo($item);

            $('<button>', {
                type: 'button',
                'class': 'button button-small polycor-remove-attr',
                'aria-label': 'Quitar ' + name,
                text: '×'
            }).appendTo($item);

            $('#polycor-selected-list').append($item);
            this.updateHiddenInputs();
            this.updateSelectedCount();
        },

        
        updateHiddenInputs: function() {
            const $container = $('#polycor-hidden-inputs');
            $container.empty();

            $('#polycor-selected-list .polycor-selected-item').each(function() {
                const slug = $(this).attr('data-slug');
                $('<input>', {
                    type: 'hidden',
                    name: 'attributes[]'
                }).val(slug).appendTo($container);
            });
        },

        
        updateSelectedCount: function() {
            const count = $('#polycor-selected-list .polycor-selected-item').length;
            $('#polycor-selected-count').text('(' + count + ')');
            
            if (count === 0) {
                let $empty = $('#polycor-selected-list .polycor-empty-message');

                if (!$empty.length) {
                    $empty = $('<p>', {
                        'class': 'polycor-empty-message',
                        text: 'Elegí características de la lista para agregarlas acá.'
                    }).prependTo('#polycor-selected-list');
                }

                $empty.show();
            } else {
                $('#polycor-selected-list .polycor-empty-message').remove();
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
         * (Nuevo + Editar)
         * ============================================================ */
        setupCategoryPreview: function() {
            const self = this;
            const $categoryField = $('form.polycor-mapping-form [name="category_id"]');

            if ($categoryField.is('select')) {
                $categoryField.on('change', function() {
                    const categoryId = $(this).val();

                    if (!categoryId) {
                        $('#polycor-frequent-attrs-list').prop('hidden', true);
                        return;
                    }

                    self.loadFrequentAttributes(categoryId);
                });

                return;
            }

            // En edición la categoría es fija y llega en un input hidden.
            const categoryId = $categoryField.val();

            if (categoryId) {
                self.loadFrequentAttributes(categoryId);
            }
        },

        
        loadFrequentAttributes: function(categoryId) {
            const $container = $('#polycor-frequent-attrs-list .polycor-frequent-items');
            const $wrapper = $('#polycor-frequent-attrs-list');

            if (!$container.length) return;

            $container.empty().append(
                $('<p>', { 'class': 'description', text: 'Cargando...' })
            );
            $wrapper.prop('hidden', false);

            $.ajax({
                url: polycorAdminData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'polycor_get_frequent_attributes',
                    nonce: polycorAdminData.nonce,
                    category_id: categoryId
                },
                success: function(response) {
                    $container.empty();

                    if (!response || !response.success || !response.data) {
                        $container.append($('<p>', {
                            'class': 'description',
                            text: 'No pudimos cargar las características frecuentes.'
                        }));
                        return;
                    }

                    const attrs = response.data.attributes || [];

                    if (attrs.length === 0) {
                        $container.append($('<p>', {
                            'class': 'description',
                            text: polycorAdminData.noFrequentAttrs || 'No encontramos valores de características para esta categoría.'
                        }));
                        return;
                    }

                    attrs.forEach(function(attr) {
                        const name = String(attr.name || '');
                        const slug = String(attr.slug || '');
                        const count = Number(attr.count) || 0;

                        const $item = $('<button>', {
                            type: 'button',
                            'class': 'polycor-attr-item polycor-frequent-item'
                        }).attr({
                            'data-attr-slug': slug,
                            'data-attr-name': name.toLowerCase()
                        });

                        $('<span>', {
                            'class': 'polycor-attr-name'
                        }).text(name).appendTo($item);

                        $('<span>', {
                            'class': 'polycor-attr-count',
                            text: '(' + count + ' valores)'
                        }).appendTo($item);

                        $container.append($item);
                    });
                },
                error: function() {
                    $container.empty().append($('<p>', {
                        'class': 'description',
                        text: 'No pudimos cargar las características frecuentes.'
                    }));
                }
            });
        },

        
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
            
        },
        
        /**
         * Mostrar notificación
         */
        showNotice: function(type, message) {
            const allowedTypes = ['success', 'error', 'warning', 'info'];
            const noticeType = allowedTypes.includes(type) ? type : 'info';
            const $notice = $('<div>', {
                'class': 'notice notice-' + noticeType + ' is-dismissible'
            });
            $('<p>').text(message).appendTo($notice);
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
