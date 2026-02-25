<?php
/**
 * Plugin Name: Loft1325 Booking Hub Admin Loft Selector
 * Description: Replaces the free-text loft field with a controlled selector on the Booking Hub admin new booking screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return selectable loft options from published ND Booking rooms.
 *
 * @return array<int,array<string,mixed>>
 */
function loft1325_booking_hub_get_selectable_lofts() {
    $query = new WP_Query(
        array(
            'post_type'      => 'nd_booking_cpt_1',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        )
    );

    if ( empty( $query->posts ) ) {
        return array();
    }

    $lofts = array();

    foreach ( $query->posts as $loft_id ) {
        $title = get_the_title( $loft_id );

        if ( '' === trim( (string) $title ) ) {
            $title = sprintf( __( 'Loft #%d', 'default' ), (int) $loft_id );
        }

        $lofts[] = array(
            'id'    => (int) $loft_id,
            'label' => sanitize_text_field( $title ),
        );
    }

    return $lofts;
}

/**
 * AJAX endpoint for loft selector options.
 */
function loft1325_booking_hub_ajax_get_loft_options() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized', 'default' ) ), 401 );
    }

    wp_send_json_success(
        array(
            'lofts' => loft1325_booking_hub_get_selectable_lofts(),
        )
    );
}
add_action( 'wp_ajax_loft1325_get_loft_options', 'loft1325_booking_hub_ajax_get_loft_options' );

/**
 * Enqueue admin enhancer for Booking Hub page.
 *
 * @param string $hook_suffix The current admin page suffix.
 */
function loft1325_booking_hub_enqueue_admin_loft_selector( $hook_suffix ) {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'loft1325-new-booking' !== $page && false === strpos( (string) $hook_suffix, 'loft1325-new-booking' ) ) {
        return;
    }

    $script_path = __DIR__ . '/assets/booking-hub-admin-loft-selector.js';

    if ( ! file_exists( $script_path ) ) {
        return;
    }

    wp_enqueue_script(
        'loft1325-booking-hub-admin-loft-selector',
        plugin_dir_url( __FILE__ ) . 'assets/booking-hub-admin-loft-selector.js',
        array(),
        (string) filemtime( $script_path ),
        true
    );

    wp_localize_script(
        'loft1325-booking-hub-admin-loft-selector',
        'loft1325LoftSelector',
        array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'action'        => 'loft1325_get_loft_options',
            'defaultOption' => __( 'Sélectionner un loft…', 'default' ),
            'emptyOption'   => __( 'Aucun loft disponible', 'default' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'loft1325_booking_hub_enqueue_admin_loft_selector' );
