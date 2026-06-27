<?php
/**
 * Registers the 'stm_task' Custom Post Type.
 *
 * @package SmartTaskManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class STM_Post_Type
 *
 * Encapsulates CPT registration logic.
 */
class STM_Post_Type {

	/**
	 * The CPT slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'stm_task';

	/**
	 * The meta key used to store task status.
	 *
	 * @var string
	 */
	const STATUS_META_KEY = '_stm_task_status';

	/**
	 * Register the Custom Post Type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'               => _x( 'Tasks', 'post type general name', 'smart-task-manager' ),
			'singular_name'      => _x( 'Task', 'post type singular name', 'smart-task-manager' ),
			'menu_name'          => _x( 'Tasks', 'admin menu', 'smart-task-manager' ),
			'add_new'            => __( 'Add New', 'smart-task-manager' ),
			'add_new_item'       => __( 'Add New Task', 'smart-task-manager' ),
			'edit_item'          => __( 'Edit Task', 'smart-task-manager' ),
			'new_item'           => __( 'New Task', 'smart-task-manager' ),
			'view_item'          => __( 'View Task', 'smart-task-manager' ),
			'search_items'       => __( 'Search Tasks', 'smart-task-manager' ),
			'not_found'          => __( 'No tasks found.', 'smart-task-manager' ),
			'not_found_in_trash' => __( 'No tasks found in Trash.', 'smart-task-manager' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false, // Managed entirely by our custom admin page.
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Allowed task status values.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses(): array {
		return array(
			'pending'   => __( 'Pending', 'smart-task-manager' ),
			'completed' => __( 'Completed', 'smart-task-manager' ),
		);
	}

	/**
	 * Validate that a given status slug is allowed.
	 *
	 * @param string $status Raw status value.
	 * @return bool
	 */
	public static function is_valid_status( string $status ): bool {
		return array_key_exists( $status, self::get_statuses() );
	}
}
