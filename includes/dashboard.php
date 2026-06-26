<?php
defined( 'ABSPATH' ) || exit;

add_action( 'ymk_overview_cards', 'ymk_maintenance_overview_card', 10, 2 );
add_filter( 'ymk_dashboard_sections', 'ymk_maintenance_register_section' );
add_action( 'ymk_dashboard_render_tab', 'ymk_maintenance_render_tab', 10, 1 );
add_filter( 'ymk_toggle_module_allowed', 'ymk_maintenance_register_toggle' );

function ymk_maintenance_overview_card( string $page_url, array $tags ): void {
    $active = '1' === get_option( 'ymk_maintenance_active', '0' );

    $badge = $active
        ? '<span class="ymk-badge" style="background:#d63638;color:#fff">' . esc_html__( 'Activo', 'ymk-maintenance' ) . '</span>'
        : '<span class="ymk-badge ymk-badge--off">' . esc_html__( 'Inactivo', 'ymk-maintenance' ) . '</span>';

    ymk_card_open(
        'dashicons-warning',
        __( 'Mantenimiento', 'ymk-maintenance' ),
        __( 'Bloquea el acceso al sitio mientras se realizan cambios.', 'ymk-maintenance' ),
        [ 'summary' => true, 'badge' => $badge ]
    );
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="ymk_toggle_module">
        <input type="hidden" name="ymk_module" value="ymk_maintenance_active">
        <?php wp_nonce_field( 'ymk_toggle_module' ); ?>
        <label class="ymk-toggle-label">
            <input type="checkbox" class="ymk-toggle" name="ymk_module_value" value="1"
                   <?php checked( $active ); ?> onchange="this.form.submit()">
            <span class="ymk-toggle-track"></span>
        </label>
    </form>
    <?php if ( $active ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'maintenance', $page_url ) ); ?>" class="ymk-card-link">
            <?php esc_html_e( 'Configurar', 'ymk-maintenance' ); ?> →
        </a>
    <?php endif; ?>
    <?php
    ymk_card_close();
}

function ymk_maintenance_register_section( array $sections ): array {
    $sections['maintenance'] = [
        'icon'  => 'dashicons-warning',
        'label' => __( 'Mantenimiento', 'ymk-maintenance' ),
    ];
    return $sections;
}

function ymk_maintenance_render_tab( string $tab ): void {
    if ( 'maintenance' !== $tab ) return;

    $active = get_option( 'ymk_maintenance_active', '0' );
    $slug   = get_option( 'ymk_maintenance_slug', '' );
    $url    = get_option( 'ymk_maintenance_url', '' );

    ymk_card_open(
        'dashicons-warning',
        __( 'Modo mantenimiento', 'ymk-maintenance' ),
        __( 'Bloquea el acceso a visitantes mostrando una página de aviso con HTTP 503.', 'ymk-maintenance' )
    );

    if ( '1' === $active ) {
        echo '<div class="ymk-notice ymk-notice-error">'
            . esc_html__( 'El modo mantenimiento está activo. Los visitantes no pueden acceder al sitio.', 'ymk-maintenance' )
            . '</div>';
    }
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="ymk_maintenance_save">
        <?php wp_nonce_field( 'ymk_maintenance_save' ); ?>

        <div class="ymk-form-row">
            <label class="ymk-form-label"><?php esc_html_e( 'Activar', 'ymk-maintenance' ); ?></label>
            <div class="ymk-form-field">
                <label class="ymk-toggle-label">
                    <input type="checkbox" name="ymk_maintenance_active" value="1" class="ymk-toggle"
                           <?php checked( '1', $active ); ?>>
                    <span class="ymk-toggle-track"></span>
                    <?php esc_html_e( 'Bloquear acceso a visitantes', 'ymk-maintenance' ); ?>
                </label>
            </div>
        </div>

        <div class="ymk-form-row">
            <label class="ymk-form-label" for="ymk_maint_slug"><?php esc_html_e( 'Slug del artículo', 'ymk-maintenance' ); ?></label>
            <div class="ymk-form-field">
                <input type="text" id="ymk_maint_slug" name="ymk_maintenance_slug"
                       value="<?php echo esc_attr( $slug ); ?>" class="regular-text">
                <p class="ymk-form-desc"><?php esc_html_e( 'Slug de un post del CPT "article" a mostrar como página de mantenimiento.', 'ymk-maintenance' ); ?></p>
            </div>
        </div>

        <div class="ymk-form-row">
            <label class="ymk-form-label" for="ymk_maint_url"><?php esc_html_e( 'URL de redirección', 'ymk-maintenance' ); ?></label>
            <div class="ymk-form-field">
                <input type="url" id="ymk_maint_url" name="ymk_maintenance_url"
                       value="<?php echo esc_attr( $url ); ?>" class="regular-text">
                <p class="ymk-form-desc"><?php esc_html_e( 'Si se indica, redirige aquí en lugar de mostrar el artículo.', 'ymk-maintenance' ); ?></p>
            </div>
        </div>

        <div class="ymk-form-actions">
            <?php submit_button( __( 'Guardar', 'ymk-maintenance' ), 'primary', 'submit', false, [ 'class' => 'ymk-btn ymk-btn-primary' ] ); ?>
        </div>
    </form>
    <?php
    ymk_card_close();
}

function ymk_maintenance_register_toggle( array $allowed ): array {
    $allowed[] = 'ymk_maintenance_active';
    return $allowed;
}
