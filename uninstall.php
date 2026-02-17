<?php

/**
 * Scoped CSS Uninstall
 *
 * Removes all plugin data when uninstalled.
 *
 * @package Scoped_CSS
 */

// If uninstall not called from WordPress, exit
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all Scoped CSS data
 */
function scoped_css_uninstall()
{
    global $wpdb;

    // Delete all scoped_css posts
    $posts = get_posts(array(
        'post_type'      => 'scoped_css',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));

    foreach ($posts as $post_id) {
        wp_delete_post($post_id, true); // Force delete (bypass trash)
    }

    // Delete post meta (cleanup any orphaned meta)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for cleanup, no caching needed during uninstall.
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_scoped_css_%'");

    // Delete transients
    delete_transient('scoped_css_rules');

    // Delete old Simple Custom CSS option (if migration didn't happen)
    delete_option('sccss_settings');

    // Clear any cached data
    wp_cache_flush();
}

scoped_css_uninstall();
