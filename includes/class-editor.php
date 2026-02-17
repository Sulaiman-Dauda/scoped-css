<?php
/**
 * CodeMirror editor integration
 *
 * @package Scoped_CSS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Editor class - handles CodeMirror integration
 */
class Scoped_CSS_Editor {

    /**
     * Initialize hooks
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor' ) );
    }

    /**
     * Enqueue CodeMirror editor
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_editor( $hook ) {
        global $post_type, $post;

        // Only load on our CPT edit screens
        if ( $post_type !== 'scoped_css' ) {
            return;
        }

        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        // Use WordPress built-in CodeMirror (WP 4.9+)
        $settings = wp_enqueue_code_editor( array(
            'type'       => 'text/css',
            'codemirror' => array(
                'indentUnit'   => 2,
                'tabSize'      => 2,
                'lineNumbers'  => true,
                'lineWrapping' => true,
                'lint'         => true,
                'gutters'      => array( 'CodeMirror-lint-markers' ),
                'extraKeys'    => array(
                    'Ctrl-Space' => 'autocomplete',
                    'F11'        => 'toggleFullscreen',
                    'Esc'        => 'closeFullscreen',
                ),
            ),
        ) );

        // Bail if user disabled CodeMirror
        if ( false === $settings ) {
            return;
        }

        // Enqueue editor initialization script
        wp_enqueue_script(
            'scoped-css-editor',
            SCOPED_CSS_URL . 'assets/js/editor.js',
            array( 'jquery', 'wp-theme-plugin-editor' ),
            SCOPED_CSS_VERSION,
            true
        );

        wp_localize_script( 'scoped-css-editor', 'scopedCssEditor', array(
            'settings' => $settings,
        ) );
    }
}
