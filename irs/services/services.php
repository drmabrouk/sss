<?php
/**
 * Sub-Plugin Name: Services
 * Description: نظام إدارة الخدمات والطلبات.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: services
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SERVICES_VERSION', '1.0.0' );
define( 'SERVICES_PATH', plugin_dir_path( __FILE__ ) );
define( 'SERVICES_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class.
 */
class Services_Plugin {

	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once SERVICES_PATH . 'includes/class-services-db.php';
		require_once SERVICES_PATH . 'includes/class-services-shortcode.php';
		require_once SERVICES_PATH . 'admin/class-services-admin.php';
	}

	private function init_hooks() {
		// register_activation_hook( __FILE__, array( $this, 'activate' ) );
		
		add_action( 'init', array( $this, 'initialize' ) );
	}

	public function initialize() {
		new Services_DB();
		new Services_Shortcode();
		if ( is_admin() ) {
			new Services_Admin();
		}
	}

	public function activate() {
		// Ensure CPT is registered during activation for flush_rewrite_rules
		$db = new Services_DB();
		$db->register_post_type();
		flush_rewrite_rules();
	}
}

new Services_Plugin();
