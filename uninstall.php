<?php
/**
 * Fired when the plugin is uninstalled (deleted via the dashboard).
 *
 * Permanently removes all stm_task posts and their meta so no orphaned
 * data is left behind in the database.
 *
 * @package SmartTaskManager
 */

// Guard: only WordPress itself may call this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all posts of type 'stm_task' and their associated meta.
 */
function stm_uninstall_cleanup(): void {
	$task_ids = get_posts(
		array(
			'post_type'      => 'stm_task',
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $task_ids as $task_id ) {
		wp_delete_post( (int) $task_id, true );
	}
}

stm_uninstall_cleanup();
