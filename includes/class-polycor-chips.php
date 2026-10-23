<?php
/**
 * Clase placeholder segura para compatibilidad.
 * Esta clase ya no genera chips — la lógica fue movida al Filter Renderer.
 *
 * @package PolyCor_Dynamic_Filters
 */

if (!defined('ABSPATH')) exit;

class Polycor_Chips {

    public function __construct() {
        // Clase intencionalmente vacía.
        // Mantiene compatibilidad sin duplicar shortcodes ni lógica.
    }
}
