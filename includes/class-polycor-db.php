<?php
/**
 * Clase para gestionar la base de datos del plugin
 *
 * @package PolyCor_Dynamic_Filters
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PolyCor_DB {
    
    /**
     * Nombre de la tabla
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Instancia de WPDB
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'polycor_cat_attrs';
    }
    
    /**
     * Crear tabla en la base de datos
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            category_id bigint(20) unsigned NOT NULL,
            attribute_id bigint(20) unsigned NOT NULL,
            attribute_slug varchar(200) NOT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY attribute_slug (attribute_slug),
            KEY is_active (is_active),
            UNIQUE KEY unique_cat_attr (category_id, attribute_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Reemplazar todos los mappings de una categoría de forma atómica.
     *
     * Requiere InnoDB para garantizar rollback ante fallos de inserción.
     *
     * @param int   $category_id ID de categoría.
     * @param array $mappings    Filas normalizadas para insertar.
     * @return bool
     */
    public function replace_mappings_for_category($category_id, $mappings) {
        $category_id = absint($category_id);

        if (!$category_id || !is_array($mappings)) {
            return false;
        }

        // No iniciar una operación destructiva si la tabla no admite transacciones.
        $table_status = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SHOW TABLE STATUS WHERE Name = %s",
                $this->table_name
            ),
            ARRAY_A
        );

        if (
            !is_array($table_status) ||
            empty($table_status['Engine']) ||
            strtolower($table_status['Engine']) !== 'innodb'
        ) {
            return false;
        }

        if ($this->wpdb->query('START TRANSACTION') === false) {
            return false;
        }

        $deleted = $this->wpdb->delete(
            $this->table_name,
            array('category_id' => $category_id),
            array('%d')
        );

        if ($deleted === false) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }

        foreach ($mappings as $mapping) {
            $result = $this->wpdb->insert(
                $this->table_name,
                array(
                    'category_id'    => $category_id,
                    'attribute_id'   => absint($mapping['attribute_id'] ?? 0),
                    'attribute_slug' => sanitize_title($mapping['attribute_slug'] ?? ''),
                    'display_order'  => absint($mapping['display_order'] ?? 0),
                    'is_active'      => 1,
                ),
                array('%d', '%d', '%s', '%d', '%d')
            );

            if ($result === false) {
                $this->wpdb->query('ROLLBACK');
                return false;
            }
        }

        if ($this->wpdb->query('COMMIT') === false) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }

        return true;
    }

    /**
     * Obtener nombre de la tabla
     *
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }
    
    /**
     * Insertar mapeo de categoría y atributo
     *
     * @param int $category_id ID de categoría
     * @param int $attribute_id ID de atributo
     * @param string $attribute_slug Slug del atributo
     * @param int $display_order Orden de visualización
     * @return int|false ID insertado o false en error
     */
    public function insert_mapping($category_id, $attribute_id, $attribute_slug, $display_order = 0) {
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'category_id'    => absint($category_id),
                'attribute_id'   => absint($attribute_id),
                'attribute_slug' => sanitize_title($attribute_slug),
                'display_order'  => absint($display_order),
                'is_active'      => 1
            ),
            array('%d', '%d', '%s', '%d', '%d')
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Actualizar mapeo
     *
     * @param int $id ID del registro
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update_mapping($id, $data) {
        $allowed_fields = array('category_id', 'attribute_id', 'attribute_slug', 'display_order', 'is_active');
        $update_data = array();
        $format = array();
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                if ($key === 'attribute_slug') {
                    $update_data[$key] = sanitize_title($value);
                    $format[] = '%s';
                } else {
                    $update_data[$key] = absint($value);
                    $format[] = '%d';
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => absint($id)),
            $format,
            array('%d')
        ) !== false;
    }
    
    /**
     * Eliminar mapeo
     *
     * @param int $id ID del registro
     * @return bool
     */
    public function delete_mapping($id) {
        return $this->wpdb->delete(
            $this->table_name,
            array('id' => absint($id)),
            array('%d')
        ) !== false;
    }
    
    /**
     * Obtener mapeos por categoría
     *
     * @param int $category_id ID de categoría
     * @param bool $active_only Solo activos
     * @return array
     */
    public function get_mappings_by_category($category_id, $active_only = true) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE category_id = %d",
            absint($category_id)
        );
        
        if ($active_only) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY display_order ASC, id ASC";
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Obtener todos los mapeos
     *
     * @param bool $active_only Solo activos
     * @return array
     */
    public function get_all_mappings($active_only = true) {
        $sql = "SELECT * FROM {$this->table_name}";
        
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY category_id ASC, display_order ASC";
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Verificar si existe un mapeo
     *
     * @param int $category_id ID de categoría
     * @param int $attribute_id ID de atributo
     * @return bool
     */
    public function mapping_exists($category_id, $attribute_id) {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE category_id = %d AND attribute_id = %d",
                absint($category_id),
                absint($attribute_id)
            )
        );
        
        return $result > 0;
    }
    
    /**
     * Eliminar mapeos por categoría
     *
     * @param int $category_id ID de categoría
     * @return bool
     */
    public function delete_mappings_by_category($category_id) {
        return $this->wpdb->delete(
            $this->table_name,
            array('category_id' => absint($category_id)),
            array('%d')
        ) !== false;
    }
    
    /**
     * Actualizar orden de visualización
     *
     * @param array $order_data Array de ['id' => orden]
     * @return bool
     */
    public function update_display_order($order_data) {
        $success = true;
        
        foreach ($order_data as $id => $order) {
            $result = $this->wpdb->update(
                $this->table_name,
                array('display_order' => absint($order)),
                array('id' => absint($id)),
                array('%d'),
                array('%d')
            );
            
            if ($result === false) {
                $success = false;
            }
        }
        
        return $success;
    }
}