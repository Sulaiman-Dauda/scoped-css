<?php

/**
 * Frontend CSS output
 *
 * @package Scoped_CSS
 */

defined('ABSPATH') || exit;

/**
 * Output class - renders CSS on the frontend
 */
class Scoped_CSS_Output
{

    /**
     * Conditions handler
     *
     * @var Scoped_CSS_Conditions
     */
    private $conditions;

    /**
     * Queued CSS for head output
     *
     * @var array
     */
    private $queued_css = array();

    /**
     * Constructor
     *
     * @param Scoped_CSS_Conditions $conditions Conditions handler.
     */
    public function __construct(Scoped_CSS_Conditions $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Initialize hooks
     */
    public function init()
    {
        // Process rules early (after query is set)
        add_action('wp', array($this, 'process_rules'));

        // Output CSS in head
        add_action('wp_head', array($this, 'output_head_css'), 100);
    }

    /**
     * Process all rules and queue matching CSS
     */
    public function process_rules()
    {
        $rules = $this->get_rules();

        if (empty($rules)) {
            return;
        }

        foreach ($rules as $rule) {
            $conditions = isset($rule['conditions']) ? $rule['conditions'] : array();

            // Check if this rule matches current page
            if ($this->conditions->matches($conditions)) {
                $css = isset($rule['css']) ? $rule['css'] : '';

                if (! empty($css)) {
                    $this->queued_css[] = array(
                        'id'  => $rule['id'],
                        'css' => $css,
                    );
                }
            }
        }
    }

    /**
     * Get all active CSS rules
     *
     * @return array
     */
    private function get_rules()
    {
        // Try to get from transient cache
        $rules = get_transient('scoped_css_rules');

        if (false === $rules) {
            $rules = $this->query_rules();

            // Cache for 12 hours (will be invalidated on save)
            set_transient('scoped_css_rules', $rules, 12 * HOUR_IN_SECONDS);
        }

        return $rules;
    }

    /**
     * Query all published CSS rules from database
     *
     * @return array
     */
    private function query_rules()
    {
        $posts = get_posts(array(
            'post_type'      => 'scoped_css',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'cache_results'  => true,
        ));

        if (empty($posts)) {
            return array();
        }

        $rules = array();
        foreach ($posts as $post) {
            $rules[] = array(
                'id'         => $post->ID,
                'css'        => $post->post_content,
                'conditions' => get_post_meta($post->ID, '_scoped_css_conditions', true) ?: array(),
            );
        }

        return $rules;
    }

    /**
     * Output CSS in head
     */
    public function output_head_css()
    {
        if (empty($this->queued_css)) {
            return;
        }

        foreach ($this->queued_css as $item) {
            $css = $this->sanitize_css($item['css']);

            if (empty($css)) {
                continue;
            }

            printf(
                "\n<style id=\"scoped-css-%d\">\n%s\n</style>\n",
                intval($item['id']),
                $css // Already sanitized
            );
        }
    }

    /**
     * Sanitize CSS output
     *
     * @param string $css Raw CSS.
     * @return string
     */
    private function sanitize_css($css)
    {
        // Strip any HTML tags
        $css = wp_strip_all_tags($css);

        // Fix any escaped characters
        $css = str_replace('&gt;', '>', $css);
        $css = str_replace('&lt;', '<', $css);
        $css = str_replace('&amp;', '&', $css);

        return trim($css);
    }
}
