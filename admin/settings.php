<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_ymk_maintenance_save', function() {
    check_admin_referer( 'ymk_maintenance_save' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

    update_option( 'ymk_maintenance_active', ! empty( $_POST['ymk_maintenance_active'] ) ? '1' : '0' );
    update_option( 'ymk_maintenance_slug',   sanitize_text_field( wp_unslash( $_POST['ymk_maintenance_slug'] ?? '' ) ) );
    update_option( 'ymk_maintenance_url',    esc_url_raw( wp_unslash( $_POST['ymk_maintenance_url'] ?? '' ) ) );

    $all_roles        = array_keys( ymk_maintenance_all_roles() );
    $excluded_roles   = array_intersect( (array) ( $_POST['ymk_maintenance_excluded_roles'] ?? [] ), $all_roles );
    update_option( 'ymk_maintenance_excluded_roles', array_values( $excluded_roles ) );

    $all_bots        = array_keys( ymk_maintenance_all_bots() );
    $excluded_bots   = array_intersect( (array) ( $_POST['ymk_maintenance_excluded_bots'] ?? [] ), $all_bots );
    update_option( 'ymk_maintenance_excluded_bots', array_values( $excluded_bots ) );

    update_option( 'ymk_maintenance_block_rest',   ! empty( $_POST['ymk_maintenance_block_rest'] )   ? '1' : '0' );
    update_option( 'ymk_maintenance_block_xmlrpc', ! empty( $_POST['ymk_maintenance_block_xmlrpc'] ) ? '1' : '0' );

    $referer = wp_get_referer() ?: admin_url( 'admin.php?page=ortogest-online&tab=maintenance' );
    wp_safe_redirect( add_query_arg( 'settings-updated', '1', $referer ) );
    exit;
} );

add_action( 'admin_post_ymk_maintenance_reset_stats', function() {
    check_admin_referer( 'ymk_maintenance_reset_stats' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );
    ymk_maintenance_stats_reset();
    $referer = wp_get_referer() ?: admin_url( 'admin.php?page=ortogest-online&tab=maintenance' );
    wp_safe_redirect( add_query_arg( 'stats-reset', '1', $referer ) );
    exit;
} );

add_action( 'admin_post_ymk_maintenance_save_license', function() {
    check_admin_referer( 'ymk_maintenance_save_license' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

    if ( isset( $_POST['ymk_maintenance_force_check'] ) ) {
        delete_transient( YMK_Maintenance_Updater::TRANSIENT );
        delete_transient( YMK_Maintenance_Updater::TRANSIENT_VALID );
        delete_site_transient( 'update_plugins' );
    } else {
        $key = sanitize_text_field( wp_unslash( $_POST['ymk_maintenance_license_key'] ?? '' ) );
        update_option( YMK_Maintenance_Updater::OPTION_KEY, $key );
        $status = YMK_Maintenance_Updater::validate_remote( $key );
        update_option( YMK_Maintenance_Updater::OPTION_STATUS, $status );
        delete_transient( YMK_Maintenance_Updater::TRANSIENT );
        delete_transient( YMK_Maintenance_Updater::TRANSIENT_VALID );
    }

    $referer = wp_get_referer() ?: admin_url( 'options-general.php?page=ymk-licenses' );
    wp_safe_redirect( add_query_arg( 'settings-updated', '1', $referer ) );
    exit;
} );
