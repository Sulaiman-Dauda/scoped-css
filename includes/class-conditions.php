<?php
/**
 * Condition matching engine
 *
 * @package Scoped_CSS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Conditions class - evaluates display conditions for CSS rules
 */
class Scoped_CSS_Conditions {

    /**
     * Current URL path (cached per request)
     *
     * @var string
     */
    private $current_url = null;

    /**
     * Check if conditions match current page
     *
     * @param array $conditions Conditions array with match_type and rules.
     * @return bool
     */
    public function matches( $conditions ) {
        if ( empty( $conditions ) || ! is_array( $conditions ) ) {
            return true; // No conditions = show everywhere
        }

        $match_type = isset( $conditions['match_type'] ) ? $conditions['match_type'] : 'any';
        $rules = isset( $conditions['rules'] ) ? $conditions['rules'] : array();

        if ( empty( $rules ) ) {
            return true; // No rules = show everywhere
        }

        $results = array();
        foreach ( $rules as $rule ) {
            $results[] = $this->evaluate_rule( $rule );
        }

        if ( $match_type === 'all' ) {
            // AND logic - all must be true
            return ! in_array( false, $results, true );
        } else {
            // OR logic - at least one must be true
            return in_array( true, $results, true );
        }
    }

    /**
     * Evaluate a single condition rule
     *
     * @param array $rule Rule with type and value.
     * @return bool
     */
    private function evaluate_rule( $rule ) {
        if ( empty( $rule ) || ! isset( $rule['type'] ) ) {
            return true;
        }

        $type = $rule['type'];
        $value = isset( $rule['value'] ) ? $rule['value'] : '';

        switch ( $type ) {
            case 'entire_site':
                return $this->is_entire_site();

            case 'front_page':
                return $this->is_front_page();

            case 'blog_page':
                return $this->is_blog_page();

            case '404':
                return $this->is_404();

            case 'page':
                return $this->is_specific_page( $value );

            case 'post':
                return $this->is_specific_post( $value );

            case 'post_type':
                return $this->is_post_type( $value );

            case 'url_exact':
                return $this->url_exact( $value );

            case 'url_contains':
                return $this->url_contains( $value );

            case 'url_starts':
                return $this->url_starts( $value );

            default:
                return false;
        }
    }

    /**
     * Check: Entire site (always true on frontend)
     *
     * @return bool
     */
    private function is_entire_site() {
        return true;
    }

    /**
     * Check: Front page
     *
     * @return bool
     */
    private function is_front_page() {
        return is_front_page();
    }

    /**
     * Check: Blog page (posts page)
     *
     * @return bool
     */
    private function is_blog_page() {
        return is_home();
    }

    /**
     * Check: 404 page
     *
     * @return bool
     */
    private function is_404() {
        return is_404();
    }

    /**
     * Check: Specific page by ID
     *
     * @param int|string $page_id Page ID.
     * @return bool
     */
    private function is_specific_page( $page_id ) {
        if ( empty( $page_id ) ) {
            return false;
        }
        return is_page( intval( $page_id ) );
    }

    /**
     * Check: Specific post by ID
     *
     * @param int|string $post_id Post ID.
     * @return bool
     */
    private function is_specific_post( $post_id ) {
        if ( empty( $post_id ) ) {
            return false;
        }
        return is_single( intval( $post_id ) );
    }

    /**
     * Check: Singular of post type
     *
     * @param string $post_type Post type slug.
     * @return bool
     */
    private function is_post_type( $post_type ) {
        if ( empty( $post_type ) ) {
            return false;
        }
        return is_singular( $post_type );
    }

    /**
     * Get current URL path (cached)
     *
     * @return string
     */
    private function get_current_url() {
        if ( $this->current_url === null ) {
            // Get request URI without query string for cleaner matching
            $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
            
            // Parse to get path component
            $parsed = wp_parse_url( $request_uri );
            $this->current_url = isset( $parsed['path'] ) ? $parsed['path'] : '/';
        }
        return $this->current_url;
    }

    /**
     * Get current full URL (with query string)
     *
     * @return string
     */
    private function get_current_full_url() {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
        return $request_uri;
    }

    /**
     * Check: URL exact match
     *
     * @param string $url URL to match.
     * @return bool
     */
    private function url_exact( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        $current = $this->get_current_url();
        $compare = trim( $url );

        // Normalize slashes
        $current = rtrim( $current, '/' );
        $compare = rtrim( $compare, '/' );

        // Handle home page
        if ( $current === '' ) {
            $current = '/';
        }
        if ( $compare === '' ) {
            $compare = '/';
        }

        return $current === $compare;
    }

    /**
     * Check: URL contains string
     *
     * @param string $string String to find.
     * @return bool
     */
    private function url_contains( $string ) {
        if ( empty( $string ) ) {
            return false;
        }

        $current = $this->get_current_full_url();
        return strpos( $current, $string ) !== false;
    }

    /**
     * Check: URL starts with prefix
     *
     * @param string $prefix URL prefix.
     * @return bool
     */
    private function url_starts( $prefix ) {
        if ( empty( $prefix ) ) {
            return false;
        }

        $current = $this->get_current_url();
        return strpos( $current, $prefix ) === 0;
    }
}
