<?php
/**
 * Plugin Name: Account Management
 * Plugin URI: https://example.com/registration
 * Description: نظام متقدم لإدارة الحسابات والعضويات يعتمد على الرقم القومي والتحقق عبر البريد الإلكتروني.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: registration
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REGISTRATION_VERSION', '1.0.0' );
define( 'REGISTRATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'REGISTRATION_URL', plugin_dir_url( __FILE__ ) );

// Load components
require_once REGISTRATION_PATH . 'includes/class-logger.php';
require_once REGISTRATION_PATH . 'includes/class-member-files.php';

function run_registration() {
	$plugin = new Member_Files();
	$plugin->run();
}

run_registration();

// Email filters for professional domain-based sending
add_filter( 'wp_mail_from', function( $email ) {
    $domain = parse_url( get_site_url(), PHP_URL_HOST );
    if ( $domain ) {
        return 'no-reply@' . $domain;
    }
    return $email;
});

add_filter( 'wp_mail_from_name', function( $name ) {
    return get_bloginfo( 'name' );
});

register_activation_hook( __FILE__, array( 'Member_Files', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Member_Files', 'deactivate' ) );
