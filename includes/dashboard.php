<?php
defined( 'ABSPATH' ) || exit;

add_action( 'ymk_overview_cards', 'ymk_maintenance_overview_card', 10, 2 );
add_filter( 'ymk_rules_submenus', 'ymk_maintenance_register_rules_submenu' );
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

    $active         = get_option( 'ymk_maintenance_active', '0' );
    $slug           = get_option( 'ymk_maintenance_slug', '' );
    $url            = get_option( 'ymk_maintenance_url', '' );
    $excluded_roles = (array) get_option( 'ymk_maintenance_excluded_roles', [ 'administrator', 'editor', 'shop_manager' ] );
    $excluded_bots  = (array) get_option( 'ymk_maintenance_excluded_bots', array_keys( ymk_maintenance_all_bots() ) );
    $block_rest     = get_option( 'ymk_maintenance_block_rest', '0' );
    $block_xmlrpc   = get_option( 'ymk_maintenance_block_xmlrpc', '0' );

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
            <label class="ymk-form-label" for="ymk_maint_slug"><?php esc_html_e( 'Artículo (slug)', 'ymk-maintenance' ); ?></label>
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

        <div class="ymk-form-row">
            <label class="ymk-form-label"><?php esc_html_e( 'Roles que NO se bloquean', 'ymk-maintenance' ); ?></label>
            <div class="ymk-form-field">
                <?php foreach ( ymk_maintenance_all_roles() as $role_slug => $role_name ) : ?>
                    <label style="display:block;margin-bottom:4px;">
                        <input type="checkbox" name="ymk_maintenance_excluded_roles[]"
                               value="<?php echo esc_attr( $role_slug ); ?>"
                               <?php checked( in_array( $role_slug, $excluded_roles, true ) ); ?>>
                        <?php echo esc_html( $role_name ); ?>
                        <?php if ( $role_slug === 'shop_manager' ) : ?>
                            <span style="color:#888;font-size:11px;"><?php esc_html_e( '← recomendado para OrtoGest ERP', 'ymk-maintenance' ); ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                <p class="ymk-form-desc"><?php esc_html_e( 'Los usuarios con estos roles acceden al sitio aunque el mantenimiento esté activo.', 'ymk-maintenance' ); ?></p>
            </div>
        </div>

        <div class="ymk-form-row">
            <label class="ymk-form-label"><?php esc_html_e( 'Bots excluidos del bloqueo', 'ymk-maintenance' ); ?></label>
            <div class="ymk-form-field">
                <?php foreach ( ymk_maintenance_all_bots() as $bot_key => $bot_label ) : ?>
                    <label style="display:inline-block;margin-right:12px;margin-bottom:4px;">
                        <input type="checkbox" name="ymk_maintenance_excluded_bots[]"
                               value="<?php echo esc_attr( $bot_key ); ?>"
                               <?php checked( in_array( $bot_key, $excluded_bots, true ) ); ?>>
                        <?php echo esc_html( $bot_label ); ?>
                    </label>
                <?php endforeach; ?>
                <p class="ymk-form-desc"><?php esc_html_e( 'Los bots marcados ven el sitio normal (recomendado para SEO).', 'ymk-maintenance' ); ?></p>
            </div>
        </div>

        <div class="ymk-form-row">
            <label class="ymk-form-label"><?php esc_html_e( 'Bloqueos adicionales', 'ymk-maintenance' ); ?></label>
            <div class="ymk-form-field">
                <label style="display:block;margin-bottom:4px;">
                    <input type="checkbox" name="ymk_maintenance_block_rest" value="1" <?php checked( '1', $block_rest ); ?>>
                    <?php esc_html_e( 'Bloquear REST API para usuarios no autenticados', 'ymk-maintenance' ); ?>
                </label>
                <label style="display:block;">
                    <input type="checkbox" name="ymk_maintenance_block_xmlrpc" value="1" <?php checked( '1', $block_xmlrpc ); ?>>
                    <?php esc_html_e( 'Bloquear XML-RPC', 'ymk-maintenance' ); ?>
                </label>
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

function ymk_maintenance_register_rules_submenu( array $items ): array {
    $items[] = [
        'menu_title' => __( 'Mantenimiento', 'ymk-maintenance' ),
        'page_title' => __( 'YMK Maintenance', 'ymk-maintenance' ),
        'slug'       => 'ymk-maintenance',
        'callback'   => 'ymk_maintenance_rules_page',
    ];
    return $items;
}

function ymk_maintenance_rules_page(): void {
    $active = get_option( 'ymk_maintenance_active', '0' );
    $slug   = get_option( 'ymk_maintenance_slug', '' );
    $url    = get_option( 'ymk_maintenance_url', '' );

    if ( isset( $_GET['settings-updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ajustes guardados.', 'ymk-maintenance' ) . '</p></div>';
    }
    if ( '1' === $active ) {
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Modo mantenimiento activo.', 'ymk-maintenance' ) . '</strong> ' . esc_html__( 'Los visitantes no pueden acceder al sitio.', 'ymk-maintenance' ) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'YMK Maintenance', 'ymk-maintenance' ); ?></h1>
        <?php
        $excluded_roles = (array) get_option( 'ymk_maintenance_excluded_roles', [ 'administrator', 'editor', 'shop_manager' ] );
        $excluded_bots  = (array) get_option( 'ymk_maintenance_excluded_bots', array_keys( ymk_maintenance_all_bots() ) );
        $block_rest     = get_option( 'ymk_maintenance_block_rest', '0' );
        $block_xmlrpc   = get_option( 'ymk_maintenance_block_xmlrpc', '0' );
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ymk_maintenance_save">
            <?php wp_nonce_field( 'ymk_maintenance_save' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Activar', 'ymk-maintenance' ); ?></th>
                    <td><label><input type="checkbox" name="ymk_maintenance_active" value="1" <?php checked( '1', $active ); ?>> <?php esc_html_e( 'Bloquear acceso a visitantes', 'ymk-maintenance' ); ?></label></td>
                </tr>
                <tr>
                    <th><label for="ymk_maint_slug2"><?php esc_html_e( 'Artículo (slug)', 'ymk-maintenance' ); ?></label></th>
                    <td><input type="text" id="ymk_maint_slug2" name="ymk_maintenance_slug" value="<?php echo esc_attr( $slug ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="ymk_maint_url2"><?php esc_html_e( 'URL de redirección', 'ymk-maintenance' ); ?></label></th>
                    <td><input type="url" id="ymk_maint_url2" name="ymk_maintenance_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Roles excluidos', 'ymk-maintenance' ); ?></th>
                    <td><?php foreach ( ymk_maintenance_all_roles() as $rs => $rn ) : ?>
                        <label style="display:block;"><input type="checkbox" name="ymk_maintenance_excluded_roles[]" value="<?php echo esc_attr( $rs ); ?>" <?php checked( in_array( $rs, $excluded_roles, true ) ); ?>> <?php echo esc_html( $rn ); ?></label>
                    <?php endforeach; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Bots excluidos', 'ymk-maintenance' ); ?></th>
                    <td><?php foreach ( ymk_maintenance_all_bots() as $bk => $bl ) : ?>
                        <label style="display:inline-block;margin-right:10px;"><input type="checkbox" name="ymk_maintenance_excluded_bots[]" value="<?php echo esc_attr( $bk ); ?>" <?php checked( in_array( $bk, $excluded_bots, true ) ); ?>> <?php echo esc_html( $bl ); ?></label>
                    <?php endforeach; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Bloqueos adicionales', 'ymk-maintenance' ); ?></th>
                    <td>
                        <label style="display:block;"><input type="checkbox" name="ymk_maintenance_block_rest" value="1" <?php checked( '1', $block_rest ); ?>> <?php esc_html_e( 'Bloquear REST API (no autenticados)', 'ymk-maintenance' ); ?></label>
                        <label style="display:block;"><input type="checkbox" name="ymk_maintenance_block_xmlrpc" value="1" <?php checked( '1', $block_xmlrpc ); ?>> <?php esc_html_e( 'Bloquear XML-RPC', 'ymk-maintenance' ); ?></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Guardar', 'ymk-maintenance' ) ); ?>
        </form>
    </div>
    <?php
}
