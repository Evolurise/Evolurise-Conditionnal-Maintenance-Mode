<?php
/*
Plugin Name: Conditionnal Maintenance Mode for WordPress
Plugin URI: https://www.evolurise.com/
Description: Allows the administrator to enable or disable maintenance mode for selected user roles and customize the maintenance message.
Version: 1.0.0
Author: Evolurise - Walid SADFI
text-domain: evolurise-maintenance-mode
License: GPL2
*/

/*  Copyright 2023 Evolurise  (email : hello@evolurise.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


// Hook the function to the 'wp_dashboard_setup' action
add_action('wp_dashboard_setup', 'hide_dashboard_messages');


add_action('wp_dashboard_setup', 'hide_dashboard_messages');

add_action( 'admin_menu', 'mm_add_settings_page' );
function mm_add_settings_page() {
    add_options_page( 'Maintenance Mode', 'Maintenance Mode', 'manage_options', 'maintenance-mode', 'mm_settings_page' );
}

// Display the settings page
function mm_settings_page() {
    wp_enqueue_style( 'basic-auth-for-wp-admin-style', plugin_dir_url( __FILE__ ) . 'styles_admin.css' );
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    ?>
    <div class="wrap">
    <img width="20%" src="<?php echo plugin_dir_url( __FILE__ ) . '/img/evolurise_logo.png'; ?>" alt="Evolurise logo">
        <h1>Conditionnal Maintenance Mode based on Wordpress User roles </h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'mm_settings' );
                do_settings_sections( 'maintenance-mode' );
                submit_button();
            ?>
        </form>
        <p>Thank you for using our plugin, please rate it and visit our website <a href="https://www.evolurise.com">evolurise.com</a></p>
    </div>
    <?php
}

// Register settings for the plugin
add_action( 'admin_init', 'mm_register_settings' );
function mm_register_settings() {
    register_setting( 'mm_settings', 'mm_settings' );
    add_settings_section( 'mm_maintenance_section', 'Conditionnal Maintenance Mode Settings Page ', 'mm_maintenance_section_callback', 'maintenance-mode' );
    add_settings_field( 'mm_status', 'Activate the maintenance mode ?', 'mm_status_callback', 'maintenance-mode', 'mm_maintenance_section' );
    add_settings_field( 'mm_roles', 'User Roles', 'mm_roles_callback', 'maintenance-mode', 'mm_maintenance_section' );
    add_settings_field( 'mm_message', 'Maintenance Message', 'mm_message_callback', 'maintenance-mode', 'mm_maintenance_section' );
}

// Callback function for the maintenance mode section
function mm_maintenance_section_callback() {
    echo 'Welcome to this settings page of the conditionnal maintenance mode for Wordpress, if you have any question about the usage of our plugin please feel free to contact us at hello@evolurise.com';
}

// Callback function for the status field
function mm_status_callback() {
    $options = get_option( 'mm_settings' );
    $status = isset( $options['status'] ) ? $options['status'] : 'off';
    ?>
    <select name="mm_settings[status]">
        <option value="off" <?php selected( $status, 'off' ); ?>>Off</option>
        <option value="on" <?php selected( $status, 'on' ); ?>>On</option>
    </select>
    <?php
}
function show_maintenance_warning() {
        $options = get_option( 'mm_settings' );
        $status = isset( $options['status'] ) ? $options['status'] : 'off';
        if ($status == 'on') {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>Maintenance mode is currently <span style="color:green;font-weight:800;">active</span>. Only users with the appropriate roles will be able to access the site.</p>';
            echo '</div>';
        }
    }
    add_action( 'admin_notices', 'show_maintenance_warning' );


// Callback function for the roles field
function mm_roles_callback() {
    $options = get_option( 'mm_settings' );
    $roles=isset( $options['roles'] ) ? $options['roles'] : array();
    global $wp_roles;
    foreach ( $wp_roles->roles as $role => $details ) {
    $name = translate_user_role($details['name'] );
    ?>
    <input type="checkbox" name="mm_settings[roles][]" value="<?php echo $role; ?>" <?php checked( in_array( $role, $roles ) ); ?>> <?php echo $name; ?><br>
    <?php
    }
    // adding guest checkbox
    ?>
    <input type="checkbox" name="mm_settings[roles][]" value="guest" <?php checked( in_array( 'guest', $roles ) ); ?>> Invité<br>
    <?php
}
    
    // Callback function for the message field
    function mm_message_callback() {
    $options = get_option( 'mm_settings' );
    $message = isset( $options['message'] ) ? $options['message'] : 'Sorry, we are currently undergoing maintenance. Please check back later.';
    ?>
    <textarea name="mm_settings[message]" rows="5" cols="50"><?php echo $message; ?></textarea>
    <?php
    }
    
// Redirect users to the maintenance page if maintenance mode is on
add_action( 'template_redirect', 'mm_maintenance_redirect' );
function mm_maintenance_redirect() {
$options = get_option( 'mm_settings' );
$status = isset( $options['status'] ) ? $options['status'] : 'off';
$roles = isset( $options['roles'] ) ? $options['roles'] : array();
$message = isset( $options['message'] ) ? $options['message'] : 'Sorry, we are currently undergoing maintenance. Please check back later.';
if ( $status == 'on' && !empty( $roles ) ) {
$current_user = wp_get_current_user();
$user_role = $current_user->roles[0];
if ( in_array( $user_role, $roles ) ) {
wp_die( $message );
}
if (in_array('guest', $roles) && !is_user_logged_in()) {
    wp_die($message);
}
}
}

// Add a toggle link to the top admin bar
add_action( 'admin_bar_menu', 'mm_admin_bar_menu', 999 );
function mm_admin_bar_menu( $wp_admin_bar ) {
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }

    $options = get_option( 'mm_settings' );
    $status = isset( $options['status'] ) ? $options['status'] : 'off';

    if ( $status == 'on' ) {
        $class = 'mm-on';
        $title = 'Maintenance Mode: <span style="background-color:Green;color:white;border-radius:30%;padding:2px 5px;font-weight:600;">On</span>';
        $href = add_query_arg( 'mm-status', 'off' );
    } else {
        $class = 'mm-off';
        $title = 'Maintenance Mode: <span style="background-color:gray;color:white;border-radius:30%;padding:2px 5px;font-weight:600;">Off</span>';
        $href = add_query_arg( 'mm-status', 'on' );
    }

    $args = array(
        'id'    => 'maintenance-mode',
        'title' => $title,
        'href'  => $href,
        'meta'  => array( 'class' => $class )
    );
    $wp_admin_bar->add_node( $args );
}

// Handle the toggle link from the admin bar
add_action( 'admin_init', 'mm_admin_bar_toggle' );
function mm_admin_bar_toggle() {
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( !isset( $_GET['mm-status'] ) ) {
        return;
    }

    $options = get_option( 'mm_settings' );

    if ( $_GET['mm-status'] == 'on' ) {
        $options['status'] = 'on';
    } else {
        $options['status'] = 'off';
    }

    update_option( 'mm_settings', $options );
}
