<?php
/**
 * Plugin Name:       Smart Task Manager
 * Plugin URI:        https://github.com/mdjalalsk/smart-task-manager
 * Description:       A lightweight task manager that lets you create and track tasks with statuses directly from the WordPress admin dashboard.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Jalal Sk
 * Author URI:        https://github.com/mdjalalsk
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smart-task-manager
 * Domain Path:       /languages
 *
 * @package SmartTaskManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'STM_VERSION', '1.0.0' );
define( 'STM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STM_PLUGIN_FILE', __FILE__ );

// Load required files.
require_once STM_PLUGIN_DIR . 'includes/class-stm-post-type.php';
require_once STM_PLUGIN_DIR . 'includes/class-stm-admin.php';

/**
 * Initialise the plugin.
 *
 * @return void
 */
function stm_init(): void {
	// Register the Custom Post Type.
	$post_type = new STM_Post_Type();
	$post_type->register();

	// Boot the admin UI (admin-only).
	if ( is_admin() ) {
		$admin = new STM_Admin();
		$admin->init();
	}
}
add_action( 'plugins_loaded', 'stm_init' );

/**
 * Activation hook – flush rewrite rules so the CPT slug works immediately.
 *
 * @return void
 */
function stm_activate(): void {
	$post_type = new STM_Post_Type();
	$post_type->register();
	flush_rewrite_rules();
}
register_activation_hook( STM_PLUGIN_FILE, 'stm_activate' );

/**
 * Deactivation hook – flush rewrite rules on deactivation.
 *
 * @return void
 */
function stm_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( STM_PLUGIN_FILE, 'stm_deactivate' );
