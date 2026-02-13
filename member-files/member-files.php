<?php
/**
 * Plugin Name: ملفات الأعضاء (Member Files)
 * Description: نظام متكامل لإدارة العضويات، التراخيص المهنية، وتراخيص المؤسسات مع محرك بحث متقدم.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: member-files
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('MEMBER_FILES_VERSION', '1.0.0');
define('MEMBER_FILES_PATH', plugin_dir_path(__FILE__));
define('MEMBER_FILES_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once MEMBER_FILES_PATH . 'includes/post-types.php';
require_once MEMBER_FILES_PATH . 'includes/meta-boxes.php';
require_once MEMBER_FILES_PATH . 'includes/admin-dashboard.php';
require_once MEMBER_FILES_PATH . 'includes/shortcodes.php';
require_once MEMBER_FILES_PATH . 'includes/functions.php';

// Initialize the plugin
function member_files_init() {
    load_plugin_textdomain('member-files', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'member_files_init');

// Enqueue assets
function member_files_enqueue_assets() {
    wp_enqueue_style('member-files-style', MEMBER_FILES_URL . 'assets/css/style.css', array(), MEMBER_FILES_VERSION);
    wp_enqueue_script('member-files-payment', MEMBER_FILES_URL . 'assets/js/payment.js', array(), MEMBER_FILES_VERSION, true);
}
add_action('wp_enqueue_scripts', 'member_files_enqueue_assets');

function member_files_admin_assets() {
    wp_enqueue_style('member-files-admin-style', MEMBER_FILES_URL . 'assets/css/admin-style.css', array(), MEMBER_FILES_VERSION);
}
add_action('admin_enqueue_scripts', 'member_files_admin_assets');
