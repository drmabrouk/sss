<?php
/**
 * Sub-Plugin Name: الفروع (Branches)
 * Description: إضافة احترافية لإدارة وعرض فروع المؤسسة مع صفحات تفصيلية ونموذج اتصال لكل فرع.
 * Version: 1.1
 * Author: Jules
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// تعاريف المسارات
define( 'BRANCHES_PATH', plugin_dir_path( __FILE__ ) );
define( 'BRANCHES_URL', plugin_dir_url( __FILE__ ) );

// تضمين الملفات الأساسية
require_once BRANCHES_PATH . 'includes/post-types.php';
require_once BRANCHES_PATH . 'includes/meta-boxes.php';
require_once BRANCHES_PATH . 'includes/shortcodes.php';
require_once BRANCHES_PATH . 'includes/routing.php';
require_once BRANCHES_PATH . 'includes/form-handler.php';

// تفعيل وتعطيل الإضافة (Moved to main irs.php)
// register_activation_hook( __FILE__, 'branches_plugin_activate' );
function branches_plugin_activate() {
    branches_register_post_types();
    flush_rewrite_rules();
}

// register_deactivation_hook( __FILE__, 'branches_plugin_deactivate' );
function branches_plugin_deactivate() {
    flush_rewrite_rules();
}
