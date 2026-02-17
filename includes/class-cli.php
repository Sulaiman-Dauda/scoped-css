<?php
/**
 * WP-CLI Commands
 *
 * @package Scoped_CSS
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) ) {
    return;
}

/**
 * Scoped CSS WP-CLI commands
 */
class Scoped_CSS_CLI extends WP_CLI_Command {

    /**
     * List all CSS rules
     *
     * @param array $args Positional arguments.
     * @param array $assoc_args Named arguments.
     */
    public function list( $args, $assoc_args ): void {
        $posts = get_posts( array(
            'post_type'      => 'scoped_css',
            'post_status'    => isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'any',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ) );

        if ( empty( $posts ) ) {
            WP_CLI::log( 'No CSS rules found.' );
            return;
        }

        $rows = array();
        foreach ( $posts as $post ) {
            $status = get_post_status( $post );
            $condition_count = 1;
            $conditions = get_post_meta( $post->ID, '_scoped_css_conditions', true );
            if ( ! empty( $conditions['rules'] ) ) {
                $condition_count = count( $conditions['rules'] );
            }

            $rows[] = array(
                'ID'         => $post->ID,
                'Title'      => $post->post_title,
                'Status'     => $status,
                'Size'       => strlen( $post->post_content ) . 'b',
                'Conditions' => $condition_count,
            );
        }

        WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'Title', 'Status', 'Size', 'Conditions' ) );
    }

    /**
     * Enable a CSS rule
     *
     * @param array $args Positional arguments.
     * @param array $assoc_args Named arguments.
     */
    public function enable( $args, $assoc_args ): void {
        if ( empty( $args ) ) {
            WP_CLI::error( 'Please provide a rule ID.' );
        }

        $post_id = intval( $args[0] );
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'scoped_css' ) {
            WP_CLI::error( 'Rule not found.' );
        }

        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        ) );

        Scoped_CSS_Loader::clear_cache();
        WP_CLI::success( sprintf( 'Rule %d enabled.', $post_id ) );
    }

    /**
     * Disable a CSS rule
     *
     * @param array $args Positional arguments.
     * @param array $assoc_args Named arguments.
     */
    public function disable( $args, $assoc_args ): void {
        if ( empty( $args ) ) {
            WP_CLI::error( 'Please provide a rule ID.' );
        }

        $post_id = intval( $args[0] );
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'scoped_css' ) {
            WP_CLI::error( 'Rule not found.' );
        }

        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'draft',
        ) );

        Scoped_CSS_Loader::clear_cache();
        WP_CLI::success( sprintf( 'Rule %d disabled.', $post_id ) );
    }

    /**
     * Get details of a specific rule
     *
     * @param array $args Positional arguments.
     * @param array $assoc_args Named arguments.
     */
    public function show( $args, $assoc_args ): void {
        if ( empty( $args ) ) {
            WP_CLI::error( 'Please provide a rule ID.' );
        }

        $post_id = intval( $args[0] );
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'scoped_css' ) {
            WP_CLI::error( 'Rule not found.' );
        }

        $location = get_post_meta( $post->ID, '_scoped_css_location', true ) ?: 'head';
        $conditions = get_post_meta( $post->ID, '_scoped_css_conditions', true );

        WP_CLI::log( sprintf( 'ID: %d', $post->ID ) );
        WP_CLI::log( sprintf( 'Title: %s', $post->post_title ) );
        WP_CLI::log( sprintf( 'Status: %s', get_post_status( $post ) ) );
        WP_CLI::log( sprintf( 'Location: %s', $location ) );
        WP_CLI::log( sprintf( 'CSS Length: %d bytes', strlen( $post->post_content ) ) );
        WP_CLI::log( sprintf( 'Conditions: %d', ! empty( $conditions['rules'] ) ? count( $conditions['rules'] ) : 0 ) );
    }

    /**
     * Clear all caches
     *
     * @param array $args Positional arguments.
     * @param array $assoc_args Named arguments.
     */
    public function cache_clear( $args, $assoc_args ): void {
        Scoped_CSS_Loader::clear_cache();
        WP_CLI::success( 'Cache cleared.' );
    }
}

WP_CLI::add_command( 'scoped-css', 'Scoped_CSS_CLI' );
