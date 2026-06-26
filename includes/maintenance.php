<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ymk_maintenance_boot', 1 );

function ymk_maintenance_boot() {
    if ( ! get_option( 'ymk_maintenance_active', false ) ) return;

    global $pagenow;

    // Siempre excluir: login y panel admin
    if ( $pagenow === 'wp-login.php' || is_admin() ) return;

    // Excluir roles configurados
    $excluded_roles = get_option( 'ymk_maintenance_excluded_roles', [ 'administrator', 'editor', 'shop_manager' ] );
    foreach ( (array) $excluded_roles as $role ) {
        if ( current_user_can( $role ) ) return;
    }

    // Excluir bots configurados
    if ( ymk_maintenance_is_excluded_bot() ) return;

    // Bloquear REST API si está marcado
    if ( get_option( 'ymk_maintenance_block_rest', '0' ) === '1' ) {
        add_filter( 'rest_authentication_errors', function( $result ) {
            if ( ! is_user_logged_in() ) {
                return new WP_Error( 'maintenance', __( 'Sitio en mantenimiento.', 'ymk-maintenance' ), [ 'status' => 503 ] );
            }
            return $result;
        } );
    }

    // Bloquear XML-RPC si está marcado
    if ( get_option( 'ymk_maintenance_block_xmlrpc', '0' ) === '1' ) {
        add_filter( 'xmlrpc_enabled', '__return_false' );
    }

    $url = get_option( 'ymk_maintenance_url', '' );
    if ( ! empty( $url ) ) {
        wp_redirect( esc_url_raw( $url ) );
        exit;
    }

    add_action( 'wp_loaded', 'ymk_maintenance_render' );
}

function ymk_maintenance_is_excluded_bot(): bool {
    $all_bots = ymk_maintenance_all_bots();
    $excluded = get_option( 'ymk_maintenance_excluded_bots', array_keys( $all_bots ) );
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    foreach ( (array) $excluded as $bot_key ) {
        $pattern = $all_bots[ $bot_key ] ?? $bot_key;
        if ( strpos( $ua, $pattern ) !== false ) return true;
    }
    return false;
}

function ymk_maintenance_all_bots(): array {
    return [
        'googlebot'    => 'Googlebot',
        'bingbot'      => 'Bingbot',
        'bingpreview'  => 'BingPreview',
        'msnbot'       => 'msnbot',
        'gtmetrix'     => 'GTmetrix',
        'lighthouse'   => 'Chrome-Lighthouse',
        'pagespeed'    => 'Google PageSpeed Insights',
        'slurp'        => 'slurp',
        'askjeeves'    => 'Ask Jeeves/Teoma',
        'baidu'        => 'Baidu',
        'duckduckbot'  => 'DuckDuckBot',
        'semrush'      => 'SemrushBot',
        'ahrefsbot'    => 'AhrefsBot',
        'mj12bot'      => 'MJ12bot',
    ];
}

function ymk_maintenance_all_roles(): array {
    if ( ! function_exists( 'get_editable_roles' ) ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
    }
    $roles = [];
    foreach ( get_editable_roles() as $slug => $data ) {
        $roles[ $slug ] = $data['name'];
    }
    return $roles;
}

function ymk_maintenance_render() {
    global $pagenow;
    if ( $pagenow === 'wp-login.php' || is_admin() ) return;

    $excluded_roles = get_option( 'ymk_maintenance_excluded_roles', [ 'administrator', 'editor', 'shop_manager' ] );
    foreach ( (array) $excluded_roles as $role ) {
        if ( current_user_can( $role ) ) return;
    }

    $slug = get_option( 'ymk_maintenance_slug', '' );

    header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
    header( 'Content-Type: text/html; charset=utf-8' );

    $page = get_posts( [
        'name'           => sanitize_title( $slug ),
        'post_type'      => 'article',
        'posts_per_page' => 1,
    ] );

    ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'maintenance-mode' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
    <div id="content" class="site-content">
        <main id="main" class="site-main">
            <?php if ( $page ) : ?>
                <div class="entry-content">
                    <?php echo apply_filters( 'the_content', $page[0]->post_content ); ?>
                </div>
            <?php else : ?>
                <div class="entry-content">
                    <h1><?php bloginfo( 'name' ); ?></h1>
                    <p><?php esc_html_e( 'Sitio en mantenimiento. Volvemos pronto.', 'ymk-maintenance' ); ?></p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
    <?php
    exit;
}
