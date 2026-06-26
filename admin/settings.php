<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function() {
    add_options_page(
        __( 'YMK Maintenance', 'ymk-maintenance' ),
        __( 'YMK Maintenance', 'ymk-maintenance' ),
        'manage_options',
        'ymk-maintenance',
        'ymk_maintenance_settings_page'
    );
} );

add_action( 'admin_post_ymk_maintenance_save', function() {
    check_admin_referer( 'ymk_maintenance_save' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

    update_option( 'ymk_maintenance_active', ! empty( $_POST['ymk_maintenance_active'] ) ? '1' : '0' );
    update_option( 'ymk_maintenance_slug',   sanitize_text_field( wp_unslash( $_POST['ymk_maintenance_slug'] ?? '' ) ) );
    update_option( 'ymk_maintenance_url',    esc_url_raw( wp_unslash( $_POST['ymk_maintenance_url'] ?? '' ) ) );

    wp_safe_redirect( add_query_arg( 'settings-updated', '1', admin_url( 'options-general.php?page=ymk-maintenance' ) ) );
    exit;
} );

function ymk_maintenance_settings_page() {
    $active = get_option( 'ymk_maintenance_active', '0' );
    $slug   = get_option( 'ymk_maintenance_slug', '' );
    $url    = get_option( 'ymk_maintenance_url', '' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'YMK Maintenance', 'ymk-maintenance' ); ?></h1>

        <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Ajustes guardados.', 'ymk-maintenance' ); ?></p></div>
        <?php endif; ?>

        <?php if ( '1' === $active ) : ?>
            <div class="notice notice-warning inline" style="margin-bottom:16px;">
                <p><strong><?php esc_html_e( 'El modo mantenimiento está activo.', 'ymk-maintenance' ); ?></strong>
                <?php esc_html_e( 'Los visitantes no pueden acceder al sitio.', 'ymk-maintenance' ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ymk_maintenance_save">
            <?php wp_nonce_field( 'ymk_maintenance_save' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Activar modo mantenimiento', 'ymk-maintenance' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ymk_maintenance_active" value="1" <?php checked( '1', $active ); ?>>
                            <?php esc_html_e( 'Bloquear acceso a visitantes (excluye admins, bots y login)', 'ymk-maintenance' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ymk_maintenance_slug"><?php esc_html_e( 'Slug del artículo (CPT article)', 'ymk-maintenance' ); ?></label></th>
                    <td>
                        <input type="text" id="ymk_maintenance_slug" name="ymk_maintenance_slug"
                               value="<?php echo esc_attr( $slug ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Slug de un artículo del CPT "article" a mostrar como página de mantenimiento.', 'ymk-maintenance' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ymk_maintenance_url"><?php esc_html_e( 'URL de redirección (opcional)', 'ymk-maintenance' ); ?></label></th>
                    <td>
                        <input type="url" id="ymk_maintenance_url" name="ymk_maintenance_url"
                               value="<?php echo esc_attr( $url ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Si se indica, redirige aquí en lugar de mostrar el artículo.', 'ymk-maintenance' ); ?></p>
                    </td>
                </tr>
            </table>

            <p><?php submit_button( __( 'Guardar', 'ymk-maintenance' ), 'primary', 'submit', false ); ?></p>
        </form>

        <hr>
        <h2 class="title"><?php esc_html_e( 'Licencia', 'ymk-maintenance' ); ?></h2>
        <?php
        $license_key    = get_option( YMK_Maintenance_Updater::OPTION_KEY, '' );
        $license_status = get_option( YMK_Maintenance_Updater::OPTION_STATUS, '' );
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ymk_maintenance_save_license">
            <?php wp_nonce_field( 'ymk_maintenance_save_license' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ymk_maintenance_license_key"><?php esc_html_e( 'Clave de licencia', 'ymk-maintenance' ); ?></label></th>
                    <td>
                        <input type="text" id="ymk_maintenance_license_key" name="ymk_maintenance_license_key"
                               value="<?php echo esc_attr( $license_key ); ?>" class="regular-text">
                        <?php if ( $license_status ) : ?>
                            <p class="description"><?php echo esc_html( YMK_Maintenance_Updater::status_label( $license_status ) ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <p>
                <?php submit_button( __( 'Guardar licencia', 'ymk-maintenance' ), 'secondary', 'submit', false ); ?>
                &nbsp;
                <button type="submit" name="ymk_maintenance_force_check" value="1" class="button">
                    <?php esc_html_e( 'Comprobar actualizaciones ahora', 'ymk-maintenance' ); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}

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

    wp_safe_redirect( add_query_arg( 'settings-updated', '1', admin_url( 'options-general.php?page=ymk-maintenance' ) ) );
    exit;
} );
