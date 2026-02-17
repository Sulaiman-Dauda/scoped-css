<?php
/**
 * Core plugin loader
 *
 * @package Scoped_CSS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main loader class - orchestrates all plugin components
 */
class Scoped_CSS_Loader {

    /**
     * Singleton instance
     *
     * @var Scoped_CSS_Loader
     */
    private static $instance = null;

    /**
     * Admin handler instance
     *
     * @var Scoped_CSS_Admin
     */
    public $admin;

    /**
     * Output handler instance
     *
     * @var Scoped_CSS_Output
     */
    public $output;

    /**
     * Conditions handler instance
     *
     * @var Scoped_CSS_Conditions
     */
    public $conditions;

    /**
     * Editor handler instance
     *
     * @var Scoped_CSS_Editor
     */
    public $editor;

    /**
     * AJAX handler instance
     *
     * @var Scoped_CSS_Ajax
     */
    public $ajax;

    /**
     * Get singleton instance
     *
     * @return Scoped_CSS_Loader
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct() {
        // Instantiate components
        $this->conditions = new Scoped_CSS_Conditions();
        $this->output     = new Scoped_CSS_Output( $this->conditions );

        if ( is_admin() ) {
            $this->admin  = new Scoped_CSS_Admin();
            $this->editor = new Scoped_CSS_Editor();
            $this->ajax   = new Scoped_CSS_Ajax();
        }
    }

    /**
     * Initialize all hooks
     */
    public function init() {
        // Register CPT early
        add_action( 'init', array( $this, 'register_post_type' ) );

        // Admin hooks
        if ( is_admin() ) {
            $this->admin->init();
            $this->editor->init();
            $this->ajax->init();
        }

        // Frontend hooks
        if ( ! is_admin() || wp_doing_ajax() ) {
            $this->output->init();
        }

        // WP-CLI commands
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            require_once SCOPED_CSS_PATH . 'includes/class-cli.php';
            WP_CLI::add_command( 'scoped-css', 'Scoped_CSS_CLI' );
        }

        // Common hooks
        add_filter( 'plugin_action_links_' . SCOPED_CSS_BASENAME, array( $this, 'settings_link' ) );
    }

    /**
     * Register Custom Post Type
     */
    public function register_post_type(): void {
        $labels = array(
            'name'                  => _x( 'CSS Rules', 'Post type general name', 'scoped-css' ),
            'singular_name'         => _x( 'CSS Rule', 'Post type singular name', 'scoped-css' ),
            'menu_name'             => _x( 'Scoped CSS', 'Admin Menu text', 'scoped-css' ),
            'name_admin_bar'        => _x( 'CSS Rule', 'Add New on Toolbar', 'scoped-css' ),
            'add_new'               => __( 'Add New', 'scoped-css' ),
            'add_new_item'          => __( 'Add New CSS Rule', 'scoped-css' ),
            'new_item'              => __( 'New CSS Rule', 'scoped-css' ),
            'edit_item'             => __( 'Edit CSS Rule', 'scoped-css' ),
            'view_item'             => __( 'View CSS Rule', 'scoped-css' ),
            'all_items'             => __( 'Scoped CSS', 'scoped-css' ),
            'search_items'          => __( 'Search CSS Rules', 'scoped-css' ),
            'not_found'             => __( 'No CSS rules found.', 'scoped-css' ),
            'not_found_in_trash'    => __( 'No CSS rules found in Trash.', 'scoped-css' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'themes.php',
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => array( 'scoped_css', 'scoped_css' ),
            'capabilities'        => array(
                'edit_post'          => 'edit_scoped_css',
                'read_post'          => 'read_scoped_css',
                'delete_post'        => 'delete_scoped_css',
                'edit_posts'         => 'edit_scoped_css',
                'edit_others_posts'  => 'edit_others_scoped_css',
                'publish_posts'      => 'publish_scoped_css',
                'read_private_posts' => 'read_private_scoped_css',
                'create_posts'       => 'create_scoped_css',
            ),
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-admin-customizer',
            'supports'            => array( 'title' ),
            'show_in_rest'        => false,
        );

        register_post_type( 'scoped_css', $args );
    }

    /**
     * Add settings link to plugins page
     *
     * @param array $links Existing links.
     * @return array
     */
    public function settings_link( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'edit.php?post_type=scoped_css' ) ),
            esc_html__( 'Manage CSS', 'scoped-css' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Clear rules cache
     */
    public static function clear_cache() {
        delete_transient( 'scoped_css_rules' );
        wp_cache_delete( 'scoped_css_rules', 'scoped_css' );
    }
}
