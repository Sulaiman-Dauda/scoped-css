<?php

/**
 * CSS Validator
 *
 * @package Scoped_CSS
 */

defined('ABSPATH') || exit;

/**
 * Validator class - validates and sanitizes CSS
 */
class Scoped_CSS_Validator
{

    /**
     * Dangerous CSS patterns that could cause issues
     *
     * @var array
     */
    private static $dangerous_patterns = array(
        'expression',     // IE expressions
        'behavior',       // IE filters
        'javascript:',    // JavaScript protocol
        '@import',        // Cross-domain imports
        'vbscript:',      // VB script
        'data:text/html', // Data URI with HTML
    );

    /**
     * Validate CSS syntax and content
     *
     * @param string $css Raw CSS input.
     * @return array { valid: bool, message: string, css: string }
     */
    public static function validate_css(string $css): array
    {
        if (empty($css)) {
            return array(
                'valid'   => false,
                'message' => __('CSS cannot be empty', 'scoped-css'),
                'css'     => '',
            );
        }

        // Check length (reasonable max)
        if (strlen($css) > 500000) { // 500KB
            return array(
                'valid'   => false,
                'message' => __('CSS exceeds maximum size (500KB)', 'scoped-css'),
                'css'     => '',
            );
        }

        // Check for dangerous patterns
        $dangerous = self::check_dangerous_patterns($css);
        if ($dangerous) {
            return array(
                'valid'   => false,
                // translators: %s is the dangerous pattern detected.
                'message' => sprintf(__('Dangerous pattern detected: %s', 'scoped-css'), esc_html($dangerous)),
                'css'     => '',
            );
        }

        // Basic bracket matching
        $open_braces = substr_count($css, '{');
        $close_braces = substr_count($css, '}');

        if ($open_braces !== $close_braces) {
            return array(
                'valid'   => false,
                'message' => __('Mismatched braces in CSS', 'scoped-css'),
                'css'     => '',
            );
        }

        // Sanitize
        $sanitized = wp_strip_all_tags($css);
        $sanitized = str_replace('&gt;', '>', $sanitized);
        $sanitized = str_replace('&lt;', '<', $sanitized);
        $sanitized = str_replace('&amp;', '&', $sanitized);

        return array(
            'valid'   => true,
            'message' => '',
            'css'     => trim($sanitized),
        );
    }

    /**
     * Validate a condition rule
     *
     * @param array $rule Condition rule.
     * @return bool
     */
    public static function validate_condition(array $rule): bool
    {
        if (! isset($rule['type'])) {
            return false;
        }

        $type = $rule['type'];
        $value = isset($rule['value']) ? $rule['value'] : '';

        $valid_types = array('entire_site', 'front_page', 'blog_page', '404', 'page', 'post', 'post_type', 'url_exact', 'url_contains', 'url_starts');

        if (! in_array($type, $valid_types, true)) {
            return false;
        }

        // Validate required values
        switch ($type) {
            case 'page':
            case 'post':
                return ! empty($value) && is_numeric($value) && get_post(intval($value)) !== null;

            case 'post_type':
                return ! empty($value) && post_type_exists($value);

            case 'url_exact':
            case 'url_contains':
            case 'url_starts':
                return ! empty($value) && strlen($value) > 0;

            case 'entire_site':
            case 'front_page':
            case 'blog_page':
            case '404':
                return true;

            default:
                return false;
        }
    }

    /**
     * Check for dangerous patterns in CSS
     *
     * @param string $css CSS content.
     * @return string|null Dangerous pattern if found, null otherwise.
     */
    private static function check_dangerous_patterns(string $css): ?string
    {
        $css_lower = strtolower($css);

        foreach (self::$dangerous_patterns as $pattern) {
            if (strpos($css_lower, strtolower($pattern)) !== false) {
                return $pattern;
            }
        }

        return null;
    }
}
