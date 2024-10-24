<?php
/*
Plugin Name: WP User Switcher
Description: A simple plugin to allow administrators to switch between users via a top bar option, including under the profile/sign-out menu.
Version: 1.6
Author: Arafat Rahman
Author URI: https://rrrplus.co.uk/
*/

// Hook to initialize the plugin and add admin bar menu
add_action('admin_bar_menu', 'us_add_admin_bar_item', 100);
add_action('init', 'us_handle_user_switch');

// Function to add an option to the admin bar
function us_add_admin_bar_item($wp_admin_bar) {
    if (current_user_can('manage_options')) {
        // Fetch users ordered by display name
        $users = get_users(array('orderby' => 'display_name'));
        $current_user = wp_get_current_user();

        // Add the top-level node for switching users, using HTML entity for icon
        $wp_admin_bar->add_node(array(
            'id'    => 'us_user_switch',
            'title' => '<span style="margin-right: 5px;">&#9853;</span>' . esc_html__('Switch User', 'wp-user-switcher'),
            'href'  => false,
            'meta'  => array('html' => true), // Allow HTML to render the icon
        ));

        // Add each user to the dropdown, excluding the current user
        foreach ($users as $user) {
            if ($user->ID !== $current_user->ID) {
                $wp_admin_bar->add_node(array(
                    'parent' => 'us_user_switch',
                    'id'     => 'us_switch_' . $user->ID,
                    'title'  => esc_html($user->display_name),
                    'href'   => esc_url(wp_nonce_url(add_query_arg('switch_user', $user->ID), 'us_switch_user_' . $user->ID)),
                ));
            }
        }

        // **Adding Switch User under the Profile Menu**
        $wp_admin_bar->add_node(array(
            'parent' => 'user-actions', // This is the ID for the user profile/sign-out section
            'id'     => 'us_user_switch_profile',
            'title'  => '<span style="margin-right: 5px;">&#9853;</span>' . esc_html__('Switch User', 'wp-user-switcher'),
            'href'   => false,
            'meta'   => array('html' => true), // Allow HTML to render the icon
        ));

        // Add each user to the dropdown under the profile
        foreach ($users as $user) {
            if ($user->ID !== $current_user->ID) {
                $wp_admin_bar->add_node(array(
                    'parent' => 'us_user_switch_profile',
                    'id'     => 'us_profile_switch_' . $user->ID,
                    'title'  => esc_html($user->display_name),
                    'href'   => esc_url(wp_nonce_url(add_query_arg('switch_user', $user->ID), 'us_switch_user_' . $user->ID)),
                ));
            }
        }
    }
}

// Function to handle user switching
function us_handle_user_switch() {
    if (isset($_GET['switch_user']) && current_user_can('manage_options')) {
        // Validate and sanitize the user ID
        $user_id = intval($_GET['switch_user']);
        $nonce   = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';

        // Verify the nonce and check if the user ID is valid
        if (wp_verify_nonce($nonce, 'us_switch_user_' . $user_id) && get_userdata($user_id)) {
            // Sanitize and validate user ID before switching
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            wp_redirect(admin_url());
            exit;
        } else {
            // If nonce or user ID is invalid, redirect to the dashboard with an error
            wp_redirect(admin_url('?us_error=invalid_user_or_nonce'));
            exit;
        }
    }
}

// Hook to display admin notice for invalid user switch attempt
add_action('admin_notices', 'us_display_error_notice');

function us_display_error_notice() {
    if (isset($_GET['us_error']) && $_GET['us_error'] === 'invalid_user_or_nonce') {
        echo '<div class="notice notice-error"><p>' . esc_html__('Invalid user or nonce. User switch failed.', 'wp-user-switcher') . '</p></div>';
    }
}
