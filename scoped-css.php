<?php

/**
 * Plugin Name: Scoped CSS
 * Plugin URI: https://github.com/Sulaiman-Dauda/Scoped-CSS
 * Description: Load custom CSS based on page, post, or URL conditions. A lightweight conditional CSS manager.
 * Version: 1.0.0
 * Author: Sulaiman Dauda
 * Author URI: https://github.com/Sulaiman-Dauda/Scoped-CSS
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: scoped-css
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.2
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Plugin constants
define('SCOPED_CSS_VERSION', '1.0.0');
define('SCOPED_CSS_FILE', __FILE__);
define('SCOPED_CSS_PATH', plugin_dir_path(__FILE__));
define('SCOPED_CSS_URL', plugin_dir_url(__FILE__));
define('SCOPED_CSS_BASENAME', plugin_basename(__FILE__));

// Minimum requirements check
if (version_compare(get_bloginfo('version'), '5.3', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        esc_html_e('Scoped CSS requires WordPress 5.3 or higher.', 'scoped-css');
        echo '</p></div>';
    });
    return;
}

if (version_compare(PHP_VERSION, '7.2', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        esc_html_e('Scoped CSS requires PHP 7.2 or higher.', 'scoped-css');
        echo '</p></div>';
    });
    return;
}

/**
 * Autoloader for plugin classes
 */
spl_autoload_register(function ($class) {
    // Only handle our classes
    $prefix = 'Scoped_CSS_';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Convert class name to file name
    $class_name = str_replace($prefix, '', $class);
    $class_name = strtolower(str_replace('_', '-', $class_name));
    $file = SCOPED_CSS_PATH . 'includes/class-' . $class_name . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Initialize the plugin
 */
function scoped_css_init()
{
    // Grant capabilities to admin role dynamically
    add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) {
        // If user is admin, grant all scoped_css capabilities
        if (isset($user->caps['administrator']) && $user->caps['administrator']) {
            $scoped_css_caps = array(
                'edit_scoped_css',
                'read_scoped_css',
                'delete_scoped_css',
                'edit_others_scoped_css',
                'publish_scoped_css',
                'read_private_scoped_css',
                'create_scoped_css',
            );
            foreach ($scoped_css_caps as $cap) {
                $allcaps[$cap] = true;
            }
        }
        return $allcaps;
    }, 10, 4);

    // Initialize loader
    $loader = Scoped_CSS_Loader::get_instance();
    $loader->init();
}
add_action('plugins_loaded', 'scoped_css_init');

/**
 * Activation hook
 */
function scoped_css_activate()
{
    // Grant capabilities to admin role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('edit_scoped_css');
        $admin_role->add_cap('read_scoped_css');
        $admin_role->add_cap('delete_scoped_css');
        $admin_role->add_cap('edit_others_scoped_css');
        $admin_role->add_cap('publish_scoped_css');
        $admin_role->add_cap('read_private_scoped_css');
        $admin_role->add_cap('create_scoped_css');
    }

    // Migrate old Simple Custom CSS data if exists
    $old_option = get_option('sccss_settings');
    if ($old_option && ! empty($old_option['sccss-content'])) {
        // Create a migrated rule
        $post_id = wp_insert_post(array(
            'post_type'    => 'scoped_css',
            'post_title'   => __('Migrated Global CSS', 'scoped-css'),
            'post_content' => wp_strip_all_tags($old_option['sccss-content']),
            'post_status'  => 'publish',
        ));

        if ($post_id && ! is_wp_error($post_id)) {
            // Set default conditions (entire site)
            update_post_meta($post_id, '_scoped_css_conditions', array(
                'match_type' => 'any',
                'rules' => array(
                    array('type' => 'entire_site', 'value' => '')
                )
            ));

            // Delete old option after successful migration
            delete_option('sccss_settings');
        }
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(SCOPED_CSS_FILE, 'scoped_css_activate');

/**
 * Deactivation hook
 */
function scoped_css_deactivate()
{
    // Clear transients
    delete_transient('scoped_css_rules');
    flush_rewrite_rules();
}
register_deactivation_hook(SCOPED_CSS_FILE, 'scoped_css_deactivate');
