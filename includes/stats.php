<?php
defined( 'ABSPATH' ) || exit;

function ymk_maintenance_stats_get(): array {
    return (array) get_option( 'ymk_maintenance_stats', [
        'blocked_visitors' => 0,
        'blocked_bots'     => 0,
        'passed_roles'     => [],
        'passed_bots'      => 0,
        'blocked_rest'     => 0,
        'blocked_xmlrpc'   => 0,
        'first_at'         => null,
        'last_at'          => null,
    ] );
}

function ymk_maintenance_stats_increment( string $group, string $subkey = '' ): void {
    $stats = ymk_maintenance_stats_get();
    $now   = current_time( 'mysql' );

    if ( ! $stats['first_at'] ) $stats['first_at'] = $now;
    $stats['last_at'] = $now;

    if ( $subkey ) {
        if ( ! isset( $stats[ $group ] ) || ! is_array( $stats[ $group ] ) ) {
            $stats[ $group ] = [];
        }
        $stats[ $group ][ $subkey ] = ( $stats[ $group ][ $subkey ] ?? 0 ) + 1;
    } else {
        $stats[ $group ] = ( $stats[ $group ] ?? 0 ) + 1;
    }

    update_option( 'ymk_maintenance_stats', $stats, false );
}

function ymk_maintenance_stats_reset(): void {
    delete_option( 'ymk_maintenance_stats' );
}
