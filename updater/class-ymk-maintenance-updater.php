<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class YMK_Maintenance_Updater {

    const PLUGIN_SLUG     = 'ymk-maintenance';
    const LICENSE_KEY     = 'YMK-FREEDOOM';
    const VALIDATE_URL    = 'https://agencialibre.es/wp-json/ymk-licenses/v1/validate';
    const INFO_URL        = 'https://agencialibre.es/wp-json/ymk-licenses/v1/info';
    const DOWNLOAD_URL    = 'https://agencialibre.es/wp-json/ymk-licenses/v1/download';
    const TRANSIENT       = 'ymk_maintenance_update_check';
    const TRANSIENT_VALID = 'ymk_maintenance_license_valid';
    const CACHE_TTL       = 43200;
    const SETTINGS_PATH   = '/wp-admin/options-general.php?page=ymk-maintenance';
    const OPTION_KEY      = 'ymk_maintenance_license_key';
    const OPTION_STATUS   = 'ymk_maintenance_license_status';

    private static $plugin_file = null;

    public static function get_plugin_file() {
        if ( self::$plugin_file === null ) {
            self::$plugin_file = plugin_basename( dirname( __DIR__ ) . '/ymk-maintenance.php' );
        }
        return self::$plugin_file;
    }

    public static function init() {
        if ( ! function_exists( 'YMK_GitHub_Updater\init_updater' ) ) {
            add_filter( 'pre_set_site_transient_update_plugins', [ __CLASS__, 'check_update' ] );
            add_filter( 'plugins_api', [ __CLASS__, 'plugin_info' ], 10, 3 );
            add_filter( 'upgrader_package_options', [ __CLASS__, 'inject_download_package' ] );
        }
        add_filter( 'ymk_license_panels', [ __CLASS__, 'register_hub_panel' ] );
    }

    public static function register_hub_panel( array $panels ): array {
        $panels[ self::PLUGIN_SLUG ] = [
            'title'         => 'YMK Maintenance',
            'icon'          => 'dashicons-warning',
            'option_key'    => self::OPTION_KEY,
            'option_status' => self::OPTION_STATUS,
            'nonce_save'    => 'ymk_maintenance_save_license',
            'nonce_force'   => 'ymk_maintenance_force_check',
            'save_action'   => [ __CLASS__, 'handle_save' ],
            'force_action'  => [ __CLASS__, 'handle_force_check' ],
            'status_label'  => [ __CLASS__, 'status_label' ],
        ];
        return $panels;
    }

    public static function handle_save(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $key = sanitize_text_field( wp_unslash( $_POST[ self::OPTION_KEY ] ?? '' ) );
        update_option( self::OPTION_KEY, $key );
        $status = self::validate_remote( $key );
        update_option( self::OPTION_STATUS, $status );
        delete_transient( self::TRANSIENT );
        delete_transient( self::TRANSIENT_VALID );
        wp_safe_redirect( admin_url( 'options-general.php?page=ymk-licenses' ) );
        exit;
    }

    public static function handle_force_check(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        delete_transient( self::TRANSIENT );
        delete_transient( self::TRANSIENT_VALID );
        delete_site_transient( 'update_plugins' );
        wp_safe_redirect( admin_url( 'options-general.php?page=ymk-licenses' ) );
        exit;
    }

    public static function validate_remote( string $key ): string {
        if ( empty( $key ) ) return 'empty_key';
        $response = wp_remote_post( self::VALIDATE_URL, [
            'timeout' => 15,
            'body'    => [
                'license_key' => $key,
                'domain'      => self::get_domain(),
                'plugin'      => self::PLUGIN_SLUG,
            ],
        ] );
        if ( is_wp_error( $response ) ) return 'connection_error';
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code === 200 && ! empty( $body['valid'] ) ) return 'valid';
        return $body['code'] ?? 'unknown_error';
    }

    public static function is_valid(): bool {
        return get_option( self::OPTION_STATUS ) === 'valid';
    }

    public static function status_label( string $status ): string {
        $labels = [
            'empty_key'        => 'Introduce una clave de licencia.',
            'connection_error' => 'No se pudo conectar con el servidor de licencias.',
            'invalid_license'  => 'Licencia no encontrada.',
            'plugin_mismatch'  => 'Esta licencia no corresponde a YMK Maintenance.',
            'license_inactive' => 'La licencia está inactiva.',
            'license_expired'  => 'La licencia ha expirado.',
            'domain_mismatch'  => 'Esta licencia está registrada para otro dominio.',
            'unknown_error'    => 'Error desconocido. Contacta con soporte.',
        ];
        return $labels[ $status ] ?? $status;
    }

    public static function check_update( $transient ) {
        $remote = self::get_remote_info();
        if ( ! $remote || empty( $remote['version'] ) ) return $transient;
        if ( version_compare( $remote['version'], self::get_local_version(), '>' ) ) {
            $obj              = new stdClass();
            $obj->slug        = self::PLUGIN_SLUG;
            $obj->plugin      = self::get_plugin_file();
            $obj->new_version = $remote['version'];
            $obj->url         = 'https://github.com/ymikimonokia/' . self::PLUGIN_SLUG;
            $obj->package     = 'ymk_maintenance_download';
            $transient->response[ self::get_plugin_file() ] = $obj;
        }
        return $transient;
    }

    public static function inject_download_package( $options ) {
        if ( ! isset( $options['package'] ) || $options['package'] !== 'ymk_maintenance_download' ) return $options;
        $signed_url = self::get_download_url();
        if ( $signed_url ) $options['package'] = $signed_url;
        return $options;
    }

    public static function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) return $result;
        if ( ! isset( $args->slug ) || $args->slug !== self::PLUGIN_SLUG ) return $result;
        $remote        = self::get_remote_info();
        $info          = new stdClass();
        $info->name    = 'YMK Maintenance';
        $info->slug    = self::PLUGIN_SLUG;
        $info->version = $remote['version'] ?? self::get_local_version();
        $info->author  = '<a href="https://agencialibre.es">Agencia Libre</a>';
        $info->sections = [ 'description' => 'Modo mantenimiento para WordPress por Agencia Libre.' ];
        return $info;
    }

    public static function validate_license() {
        if ( get_transient( self::TRANSIENT_VALID ) !== false ) return;
        $license = get_option( self::OPTION_KEY, '' );
        $key     = $license ?: self::LICENSE_KEY;
        wp_remote_post( self::VALIDATE_URL, [
            'timeout'  => 10,
            'blocking' => false,
            'body'     => [
                'license_key'   => $key,
                'domain'        => self::get_domain(),
                'plugin'        => self::PLUGIN_SLUG,
                'version'       => self::get_local_version(),
                'settings_path' => self::SETTINGS_PATH,
            ],
        ] );
        set_transient( self::TRANSIENT_VALID, 1, DAY_IN_SECONDS );
    }

    private static function get_remote_info() {
        $cached = get_transient( self::TRANSIENT );
        if ( $cached !== false ) return $cached;
        $license  = get_option( self::OPTION_KEY, '' );
        $key      = $license ?: self::LICENSE_KEY;
        $response = wp_remote_post( self::INFO_URL, [
            'timeout' => 15,
            'body'    => [
                'license_key'   => $key,
                'domain'        => self::get_domain(),
                'plugin'        => self::PLUGIN_SLUG,
                'version'       => self::get_local_version(),
                'settings_path' => self::SETTINGS_PATH,
            ],
        ] );
        if ( is_wp_error( $response ) ) return null;
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code !== 200 || empty( $body['version'] ) ) return null;
        set_transient( self::TRANSIENT, $body, self::CACHE_TTL );
        return $body;
    }

    private static function get_download_url() {
        $response = wp_remote_post( self::DOWNLOAD_URL, [
            'timeout' => 15,
            'body'    => [
                'license_key' => self::LICENSE_KEY,
                'domain'      => self::get_domain(),
                'plugin'      => self::PLUGIN_SLUG,
            ],
        ] );
        if ( is_wp_error( $response ) ) return null;
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code !== 200 || empty( $body['download_url'] ) ) return null;
        return $body['download_url'];
    }

    private static function get_local_version() {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $data = get_plugin_data( WP_PLUGIN_DIR . '/' . self::get_plugin_file() );
        return $data['Version'] ?? '0.0.0';
    }

    private static function get_domain() {
        $domain = home_url();
        $domain = preg_replace( '#^https?://#', '', $domain );
        $domain = preg_replace( '#^www\.#', '', $domain );
        return strtolower( rtrim( $domain, '/' ) );
    }
}
