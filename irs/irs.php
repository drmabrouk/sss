<?php
/*
Plugin Name: IRS
Description: نظام متكامل يشمل الفروع، الأسئلة الشائعة، ملفات الأعضاء، إدارة الحسابات، والخدمات.
Version: 1.0.0
Author: Jules
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Define IRS constants
define( 'IRS_PATH', plugin_dir_path( __FILE__ ) );
define( 'IRS_URL', plugin_dir_url( __FILE__ ) );

// Include sub-plugins
require_once IRS_PATH . 'branches/branches.php';
require_once IRS_PATH . 'faq/faq.php';
require_once IRS_PATH . 'member-files/member-files.php';
require_once IRS_PATH . 'registration/registration.php';
require_once IRS_PATH . 'services/services.php';

/**
 * Combined Activation Hook
 */
register_activation_hook( __FILE__, 'irs_plugin_activate' );
function irs_plugin_activate() {
    // 1. Branches Activation
    if ( function_exists( 'branches_register_post_types' ) ) {
        branches_register_post_types();
    }

    // 2. FAQ Activation
    if ( function_exists( 'faq_pro_install' ) ) {
        faq_pro_install();
    }

    // 3. Registration Activation
    if ( class_exists( 'Member_Files' ) ) {
        Member_Files::activate();
    }

    // 4. Services Activation
    if ( class_exists( 'Services_DB' ) ) {
        $services_db = new Services_DB();
        $services_db->register_post_type();
    }

    flush_rewrite_rules();
}

/**
 * Combined Deactivation Hook
 */
register_deactivation_hook( __FILE__, 'irs_plugin_deactivate' );
function irs_plugin_deactivate() {
    flush_rewrite_rules();
}
