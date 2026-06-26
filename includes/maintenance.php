<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ymk_maintenance_boot', 1 );

function ymk_maintenance_boot() {
    if ( ! get_option( 'ymk_maintenance_active', false ) ) return;

    global $pagenow;

    if (
        $pagenow === 'wp-login.php' ||
        current_user_can( 'edit_others_pages' ) ||
        is_admin() ||
        ymk_maintenance_is_bot()
    ) return;

    $url = get_option( 'ymk_maintenance_url', '' );

    if ( ! empty( $url ) ) {
        wp_redirect( esc_url_raw( $url ) );
        exit;
    }

    add_action( 'wp_loaded', 'ymk_maintenance_render' );
}

function ymk_maintenance_is_bot(): bool {
    $bots = [
        'Googlebot', 'Bingbot', 'BingPreview', 'msnbot',
        'GTmetrix', 'Chrome-Lighthouse', 'Google PageSpeed Insights',
        'slurp', 'Ask Jeeves/Teoma', 'Baidu', 'DuckDuckBot',
    ];
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    foreach ( $bots as $bot ) {
        if ( strpos( $ua, $bot ) !== false ) return true;
    }
    return false;
}

function ymk_maintenance_render() {
    global $pagenow;

    if ( $pagenow === 'wp-login.php' || current_user_can( 'edit_others_pages' ) || is_admin() ) return;

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
