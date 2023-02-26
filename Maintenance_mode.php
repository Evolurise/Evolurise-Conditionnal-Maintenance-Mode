<?php
/*
Plugin Name: Conditional Maintenance Mode for WordPress
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

add_action( 'admin_menu', 'maintenance_add_settings_page' );
function maintenance_add_settings_page() {
    add_options_page( esc_html__( 'Maintenance Mode', 'evolurise-maintenance-mode' ), esc_html__( 'Maintenance Mode', 'evolurise-maintenance-mode' ), 'manage_options', 'maintenance-mode', 'maintenance_settings_page' );
}

// Display the settings page
function maintenance_settings_page() {
    wp_enqueue_style( 'basic-auth-for-wp-admin-style', plugin_dir_url( __FILE__ ) . 'styles_admin.css' );
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'evolurise-maintenance-mode' ) );
    }
    ?>
    <div class="wrap">
    <img width="20%" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '/img/evolurise_logo.png' ); ?>" alt="<?php esc_attr_e( 'Evolurise logo', 'evolurise-maintenance-mode' ); ?>">
        <h1><?php esc_html_e( 'Conditional Maintenance Mode based on WordPress User Roles', 'evolurise-maintenance-mode' ); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'maintenance_settings' );
                do_settings_sections( 'maintenance-mode' );
                submit_button();
            ?>
        </form>
        <p><?php esc_html_e( 'Thank you for using our plugin, please rate it and visit our website', 'evolurise-maintenance-mode' ); ?> <a href="<?php echo esc_url( 'https://www.evolurise.com' ); ?>"><?php esc_html_e( 'evolurise.com', 'evolurise-maintenance-mode' ); ?></a></p>
    </div>
    <?php
}

// Register settings for the plugin
add_action( 'admin_init', 'maintenance_register_settings' );
function maintenance_register_settings() {
    register_setting( 'maintenance_settings', 'maintenance_settings' );
    add_settings_section( 'maintenance_maintenance_section', esc_html__('Conditional Maintenance Mode Settings Page', 'evolurise-maintenance-mode'), 'maintenance_maintenance_section_callback', 'maintenance-mode' );
    add_settings_field( 'maintenance_status', esc_html__('Activate the maintenance mode?', 'evolurise-maintenance-mode'), 'maintenance_status_callback', 'maintenance-mode', 'maintenance_maintenance_section' );
    add_settings_field( 'maintenance_roles', esc_html__('User Roles', 'evolurise-maintenance-mode'), 'maintenance_roles_callback', 'maintenance-mode', 'maintenance_maintenance_section' );
    add_settings_field( 'maintenance_message', esc_html__('Maintenance Message', 'evolurise-maintenance-mode'), 'maintenance_message_callback', 'maintenance-mode', 'maintenance_maintenance_section' );
    }
    
    // Callback function for the maintenance mode section
    function maintenance_maintenance_section_callback() {
    echo esc_html__('Welcome to this settings page of the conditional maintenance mode for WordPress. If you have any questions about the usage of our plugin, please feel free to contact us at hello@evolurise.com', 'evolurise-maintenance-mode');
    }
    
    // Callback function for the status field
    function maintenance_status_callback() {
    $options = get_option( 'maintenance_settings' );
    $status = isset( $options['status'] ) ? $options['status'] : 'off';
    ?>
    <select name="maintenance_settings[status]">
    <option value="off" <?php selected( $status, 'off' ); ?>><?php esc_html_e('Off', 'evolurise-maintenance-mode'); ?></option>
    <option value="on" <?php selected( $status, 'on' ); ?>><?php esc_html_e('On', 'evolurise-maintenance-mode'); ?></option>
    </select>
    <?php
    }
    
    function show_maintenance_warning() {
    $options = get_option( 'maintenance_settings' );
    $status = isset( $options['status'] ) ? $options['status'] : 'off';
    if ($status == 'on') {
    echo '<div class="notice notice-error is-dismissible">';
    echo '<p>' . esc_html__('Maintenance mode is currently', 'evolurise-maintenance-mode') . '<span style="color:green;font-weight:800;"> ' . esc_html__('active', 'evolurise-maintenance-mode') . '</span>. ' . esc_html__('Only users with the appropriate roles will be able to access the site.', 'evolurise-maintenance-mode') . '</p>';
    echo '</div>';
    }
    }
    add_action( 'admin_notices', 'show_maintenance_warning' );
    
    // Callback function for the roles field
    function maintenance_roles_callback() {
    $options = get_option( 'maintenance_settings' );
    $roles = isset( $options['roles'] ) ? $options['roles'] : array();
    global $wp_roles;
    foreach ( $wp_roles->roles as $role => $details ) {
    $name = translate_user_role( $details['name'] );
    ?>
    <input type="checkbox" name="maintenance_settings[roles][]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $roles ) ); ?>> <?php echo esc_html( $name ); ?><br>
    <?php
    }
    }
    
// Callback function for the message field
function maintenance_message_callback() {
    $options = get_option( 'maintenance_settings' );
    $message = isset( $options['message'] ) ? $options['message'] : '';
    ?>
    <textarea name="maintenance_settings[message]" rows="5" cols="50"><?php echo esc_textarea( sanitize_textarea_field( $message ) ); ?></textarea>
    <?php
}

    
// Redirect users to the maintenance page if maintenance mode is on
add_action( 'template_redirect', 'maintenance_maintenance_redirect' );
function maintenance_maintenance_redirect() {
$options = get_option( 'maintenance_settings' );
$status = isset( $options['status'] ) ? esc_attr( $options['status'] ) : 'off';
$roles = isset( $options['roles'] ) ? array_map( 'esc_attr', $options['roles'] ) : array();
$message = isset( $options['message'] ) ? esc_html( $options['message'] ) : 'Sorry, we are currently undergoing maintenance. Please check back later.';
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
add_action( 'admin_bar_menu', 'maintenance_admin_bar_menu', 999 );
function maintenance_admin_bar_menu( $wp_admin_bar ) {
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }

    $options = get_option( 'maintenance_settings' );
    $status = isset( $options['status'] ) ? $options['status'] : 'off';

    if ( $status == 'on' ) {
        $class = 'maintenance-on';
        $title = 'Maintenance Mode: <span style="background-color:Green;color:white;border-radius:30%;padding:2px 5px;font-weight:600;">On</span>';
        $href = add_query_arg( 'maintenance-status', 'off' );
    } else {
        $class = 'maintenance-off';
        $title = 'Maintenance Mode: <span style="background-color:gray;color:white;border-radius:30%;padding:2px 5px;font-weight:600;">Off</span>';
        $href = add_query_arg( 'maintenance-status', 'on' );
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
add_action( 'admin_init', 'maintenance_admin_bar_toggle' );
function maintenance_admin_bar_toggle() {
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( !isset( $_GET['maintenance-status'] ) ) {
        return;
    }

    $options = get_option( 'maintenance_settings' );

    if ( $_GET['maintenance-status'] == 'on' ) {
        $options['status'] = 'on';
    } else {
        $options['status'] = 'off';
    }

    update_option( 'maintenance_settings', $options );
}

