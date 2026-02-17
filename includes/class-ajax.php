<?php
/**
 * AJAX handlers
 *
 * @package Scoped_CSS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ajax class - handles AJAX requests for admin UI
 */
class Scoped_CSS_Ajax {

    /**
     * Initialize hooks
     */
    public function init(): void {
        add_action( 'wp_ajax_scoped_css_search_posts', array( $this, 'search_posts' ) );
        add_action( 'wp_ajax_scoped_css_minify', array( $this, 'minify_css' ) );
        add_action( 'wp_ajax_scoped_css_unminify', array( $this, 'unminify_css' ) );
    }

    /**
     * Check rate limit for a user
     *
     * @return bool True if within limits, false if rate limited.
     */
    private function check_rate_limit(): bool {
        $user_id = get_current_user_id();
        $user_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $cache_key = 'scoped_css_rate_' . $user_id . '_' . md5( $user_ip );
        $request_count = (int) get_transient( $cache_key );

        if ( $request_count >= 50 ) {
            return false;
        }

        set_transient( $cache_key, $request_count + 1, MINUTE_IN_SECONDS );
        return true;
    }

    /**
     * Search posts/pages by title
     */
    public function search_posts(): void {
        // Check rate limit
        if ( ! $this->check_rate_limit() ) {
            wp_send_json_error( array(
                'message' => __( 'Too many requests. Please try again later.', 'scoped-css' ),
            ), 429 );
        }

        // Verify nonce
        if ( ! check_ajax_referer( 'scoped_css_search', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid security token', 'scoped-css' ),
            ) );
        }

        // Check capabilities
        if ( ! current_user_can( 'edit_scoped_css' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Permission denied', 'scoped-css' ),
            ) );
        }

        // Get parameters
        $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $post_type = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : 'page';

        // Validate post type
        if ( ! in_array( $post_type, array( 'page', 'post' ), true ) ) {
            $post_type = 'page';
        }

        // Minimum search length
        if ( strlen( $search ) < 2 ) {
            wp_send_json_success( array(
                'results' => array(),
            ) );
        }

        // Query posts
        $args = array(
            'post_type'      => $post_type,
            'post_status'    => array( 'publish', 'draft', 'private' ),
            's'              => $search,
            'posts_per_page' => 10,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        );

        $posts = get_posts( $args );

        $results = array();
        foreach ( $posts as $post ) {
            $results[] = array(
                'id'    => $post->ID,
                'title' => $post->post_title ?: __( '(No title)', 'scoped-css' ),
                'type'  => get_post_type_object( $post->post_type )->labels->singular_name,
            );
        }

        wp_send_json_success( array(
            'results' => $results,
        ) );
    }

    /**
     * Minify CSS via AJAX
     */
    public function minify_css(): void {
        try {
            // Check rate limit
            if ( ! $this->check_rate_limit() ) {
                wp_send_json_error( array(
                    'message' => __( 'Too many requests. Please try again later.', 'scoped-css' ),
                ), 429 );
                return;
            }

            // Verify nonce
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'scoped_css_minify' ) ) {
                wp_send_json_error( array(
                    'message' => __( 'Invalid security token. Please refresh the page.', 'scoped-css' ),
                ), 403 );
                return;
            }

            // Check capabilities
            if ( ! current_user_can( 'edit_scoped_css' ) ) {
                wp_send_json_error( array(
                    'message' => __( 'Permission denied', 'scoped-css' ),
                ), 403 );
                return;
            }

            // Get CSS
            $css = isset( $_POST['css'] ) ? wp_unslash( $_POST['css'] ) : '';

            if ( empty( $css ) ) {
                wp_send_json_error( array(
                    'message' => __( 'No CSS provided', 'scoped-css' ),
                ) );
                return;
            }

            // Minify CSS
            $minified = Scoped_CSS_Minifier::minify( $css );
            $savings = Scoped_CSS_Minifier::calculate_savings( $css, $minified );

            wp_send_json_success( array(
                'minified' => $minified,
                'savings'  => $savings,
                'message'  => sprintf(
                    __( 'Minified! Saved %d%% (%d bytes)', 'scoped-css' ),
                    $savings['percent'],
                    $savings['saved']
                ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'message' => 'Error: ' . $e->getMessage(),
            ), 500 );
        }
    }

    /**
     * Unminify CSS via AJAX
     */
    public function unminify_css(): void {
        try {
            // Check rate limit
            if ( ! $this->check_rate_limit() ) {
                wp_send_json_error( array(
                    'message' => __( 'Too many requests. Please try again later.', 'scoped-css' ),
                ), 429 );
                return;
            }

            // Verify nonce
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'scoped_css_minify' ) ) {
                wp_send_json_error( array(
                    'message' => __( 'Invalid security token. Please refresh the page.', 'scoped-css' ),
                ), 403 );
                return;
            }

            // Check capabilities
            if ( ! current_user_can( 'edit_scoped_css' ) ) {
                wp_send_json_error( array(
                    'message' => __( 'Permission denied', 'scoped-css' ),
                ), 403 );
                return;
            }

            // Get CSS
            $css = isset( $_POST['css'] ) ? wp_unslash( $_POST['css'] ) : '';

            if ( empty( $css ) ) {
                wp_send_json_error( array(
                    'message' => __( 'No CSS provided', 'scoped-css' ),
                ) );
                return;
            }

            // Unminify CSS
            $unminified = Scoped_CSS_Minifier::unminify( $css );

            wp_send_json_success( array(
                'unminified' => $unminified,
                'message'    => __( 'Formatted for readability', 'scoped-css' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'message' => 'Error: ' . $e->getMessage(),
            ), 500 );
        }
    }
}
