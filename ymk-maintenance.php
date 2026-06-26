<?php
/**
 * Plugin Name: YMK Maintenance
 * Plugin URI:  https://github.com/ymikimonokia/ymk-maintenance
 * Description: Modo mantenimiento para WordPress. Muestra artículo CPT con HTTP 503 o redirige a URL externa. Excluye bots, admins y login.
 * Version:     1.2.0
 * Author:      Agencia Libre
 * Author URI:  https://agencialibre.es
 * License:     Proprietary
 * Text Domain: ymk-maintenance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'YMK_MAINTENANCE_VERSION', '1.2.0' );
define( 'YMK_MAINTENANCE_DIR', plugin_dir_path( __FILE__ ) );
define( 'YMK_MAINTENANCE_URL', plugin_dir_url( __FILE__ ) );

require_once YMK_MAINTENANCE_DIR . 'updater/class-ymk-maintenance-updater.php';
require_once YMK_MAINTENANCE_DIR . 'includes/stats.php';
require_once YMK_MAINTENANCE_DIR . 'includes/maintenance.php';
require_once YMK_MAINTENANCE_DIR . 'includes/dashboard.php';
require_once YMK_MAINTENANCE_DIR . 'admin/settings.php';

add_action( 'plugins_loaded', [ 'YMK_Maintenance_Updater', 'init' ] );
add_action( 'plugins_loaded', [ 'YMK_Maintenance_Updater', 'validate_license' ] );

