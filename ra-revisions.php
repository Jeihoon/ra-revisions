<?php
/*
Plugin Name: RA Revisions 
Plugin URI:  https://mypixellab.com
Description: A plugin to limit and clear post revisions in WordPress with scheduling options.
Version: 2.1
Author: Amin Rahnama
Author URI: https://mypixellab.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

// ========== ADMIN MENU ==========
function ra_revisions_menu() {
    add_menu_page(
        'RA Revisions Settings',       // Page title
        'RA Revisions',                // Menu title
        'manage_options',              // Capability
        'ra-revisions',                // Menu slug
        'ra_revisions_settings_page',  // Callback function
        'dashicons-update',           // Icon (WordPress Dashicon)
        25                             // Position in the menu
    );
}
add_action('admin_menu', 'ra_revisions_menu');

function ra_revisions_settings_page() {
    ?>
    <div class="wrap">
        <h2>RA Revisions Settings</h2>

        <?php ra_display_revision_stats(); ?>

        <form method="post" action="options.php">
            <?php
            settings_fields('ra_revisions_settings_group');
            do_settings_sections('ra-revisions');
            submit_button();
            ?>
        </form>

        <h3>Clear Old Revisions Manually</h3>
        <form method="post">
            <?php
            wp_nonce_field('ra_clear_revisions', 'ra_clear_revisions_nonce');
            submit_button('Clear Revisions Now', 'delete', 'ra_clear_revisions_submit');
            ?>
        </form>
    </div>
    <?php
}

// ========== SETTINGS ==========
function ra_register_settings() {
    register_setting('ra_revisions_settings_group', 'ra_revision_limit');
    register_setting('ra_revisions_settings_group', 'ra_cleanup_frequency');

    add_settings_section(
        'ra_revisions_section',
        'Revisions Limit and Schedule',
        'ra_revisions_section_callback',
        'ra-revisions'
    );

    add_settings_field(
        'ra_revision_limit',
        'Number of Revisions to Keep:',
        'ra_revision_limit_callback',
        'ra-revisions',
        'ra_revisions_section'
    );

    add_settings_field(
        'ra_cleanup_frequency',
        'Scheduled Cleanup Frequency:',
        'ra_cleanup_frequency_callback',
        'ra-revisions',
        'ra_revisions_section'
    );
}
add_action('admin_init', 'ra_register_settings');

function ra_revisions_section_callback() {
    echo '<p>Set how many revisions to keep, and how often to run automatic cleanup.</p>';
}

function ra_revision_limit_callback() {
    $limit = get_option('ra_revision_limit', 5);
    echo "<input type='number' name='ra_revision_limit' value='" . esc_attr($limit) . "' min='1' />";
}

function ra_cleanup_frequency_callback() {
    $value = get_option('ra_cleanup_frequency', 'daily');
    ?>
    <select name="ra_cleanup_frequency">
        <option value="daily" <?php selected($value, 'daily'); ?>>Daily</option>
        <option value="weekly" <?php selected($value, 'weekly'); ?>>Weekly</option>
        <option value="monthly" <?php selected($value, 'monthly'); ?>>Monthly</option>
    </select>
    <p class="description">How often should automatic revision cleanup run?</p>
    <?php
}

// ========== STATS ==========
function ra_display_revision_stats() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
    echo "<p><strong>Total revisions in database:</strong> $count</p>";
}

// ========== LIMIT FILTER ==========
function ra_limit_revisions($num, $post) {
    $limit = get_option('ra_revision_limit', 5);
    return is_numeric($limit) ? max(1, intval($limit)) : 5;
}
add_filter('wp_revisions_to_keep', 'ra_limit_revisions', 10, 2);

// ========== MANUAL CLEANUP ==========
function ra_clear_old_revisions() {
    if (
        !empty($_POST['ra_clear_revisions_submit']) &&
        isset($_POST['ra_clear_revisions_nonce']) &&
        wp_verify_nonce($_POST['ra_clear_revisions_nonce'], 'ra_clear_revisions')
    ) {
        ra_run_revision_cleanup();
        wp_redirect(admin_url('options-general.php?page=ra-revisions&cleared=true'));
        exit;
    }
}
add_action('admin_init', 'ra_clear_old_revisions');

// ========== CLEANUP LOGIC ==========
function ra_run_revision_cleanup() {
    global $wpdb;
    $limit = get_option('ra_revision_limit', 5);
    $limit = max(1, intval($limit));

    $posts_with_revisions = $wpdb->get_results("
        SELECT post_parent, COUNT(*) as revision_count
        FROM {$wpdb->posts}
        WHERE post_type = 'revision'
        GROUP BY post_parent
        HAVING revision_count > $limit
    ");

    foreach ($posts_with_revisions as $post) {
        $post_id = intval($post->post_parent);
        $to_delete = intval($post->revision_count) - $limit;

        $revisions_to_delete = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'revision' AND post_parent = %d
            ORDER BY post_date ASC
            LIMIT %d
        ", $post_id, $to_delete));

        if (!empty($revisions_to_delete)) {
            $revisions_ids = implode(',', array_map('intval', $revisions_to_delete));
            $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ($revisions_ids)");
        }
    }

    set_transient('ra_cleanup_notice', true, 60);
}

// ========== CRON SETUP ==========
function ra_schedule_revision_cleanup() {
    $frequency = get_option('ra_cleanup_frequency', 'daily');
    if (!wp_next_scheduled('ra_daily_revision_cleanup')) {
        wp_schedule_event(time(), $frequency, 'ra_daily_revision_cleanup');
    }
}
register_activation_hook(__FILE__, 'ra_schedule_revision_cleanup');

function ra_clear_revision_cleanup_schedule() {
    wp_clear_scheduled_hook('ra_daily_revision_cleanup');
}
register_deactivation_hook(__FILE__, 'ra_clear_revision_cleanup_schedule');

add_action('ra_daily_revision_cleanup', 'ra_run_revision_cleanup');

// Add 'monthly' to cron schedules
function ra_add_custom_cron_schedules($schedules) {
    $schedules['monthly'] = [
        'interval' => 2592000,
        'display'  => __('Once Monthly')
    ];
    return $schedules;
}
add_filter('cron_schedules', 'ra_add_custom_cron_schedules');

// Reschedule when frequency changes
function ra_reschedule_cleanup_if_needed($old_value, $new_value) {
    $old_freq = get_option('ra_cleanup_frequency', 'daily');
    if ($old_freq !== $new_value) {
        wp_clear_scheduled_hook('ra_daily_revision_cleanup');
        wp_schedule_event(time(), $new_value, 'ra_daily_revision_cleanup');
    }
}
add_action('update_option_ra_cleanup_frequency', 'ra_reschedule_cleanup_if_needed', 10, 2);

// ========== ADMIN NOTICES ==========
function ra_admin_notices() {
    if (isset($_GET['cleared']) && $_GET['cleared'] === 'true') {
        echo '<div class="updated notice is-dismissible"><p>Manual revision cleanup complete!</p></div>';
    }

    if (get_transient('ra_cleanup_notice')) {
        echo '<div class="updated notice is-dismissible"><p>Scheduled revision cleanup complete!</p></div>';
        delete_transient('ra_cleanup_notice');
    }
}
add_action('admin_notices', 'ra_admin_notices');
