<?php
/**
 * Admin UI: custom menu page, task creation form, and task table.
 *
 * @package SmartTaskManager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class STM_Admin
 *
 * Handles all wp-admin interactions.
 */
class STM_Admin {

	/**
	 * The admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'smart-task-manager';

	/**
	 * The nonce action for task form submissions.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'stm_create_task';

	/**
	 * The nonce field name.
	 *
	 * @var string
	 */
	const NONCE_FIELD = 'stm_task_nonce';

	/**
	 * The nonce action for task update submissions.
	 *
	 * @var string
	 */
	const NONCE_ACTION_UPDATE = 'stm_update_task';

	/**
	 * The nonce field name for task updates.
	 *
	 * @var string
	 */
	const NONCE_FIELD_UPDATE = 'stm_update_nonce';

	/**
	 * The nonce action for the edit-task screen link.
	 *
	 * @var string
	 */
	const NONCE_ACTION_EDIT = 'stm_edit_task';

	/**
	 * Wire up WordPress hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_stm_create_task', array( $this, 'handle_create_task' ) );
		add_action( 'admin_post_stm_update_task', array( $this, 'handle_update_task' ) );
		add_action( 'admin_post_stm_delete_task', array( $this, 'handle_delete_task' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the top-level admin menu item.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Task Manager', 'smart-task-manager' ),   // Page title.
			__( 'Task Manager', 'smart-task-manager' ),   // Menu label.
			'manage_options',                              // Capability.
			self::PAGE_SLUG,                               // Menu slug.
			array( $this, 'render_page' ),                 // Callback.
			'dashicons-clipboard',                         // Icon.
			25                                             // Position.
		);
	}

	/**
	 * Enqueue admin styles and scripts only on our page.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'stm-admin',
			STM_PLUGIN_URL . 'assets/admin/css/stm-admin.css',
			array(),
			STM_VERSION
		);
	}

	/**
	 * Render the main admin page (form + task table).
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'smart-task-manager' ) );
		}

		$tasks   = $this->get_tasks();
		$message = $this->get_admin_notice();
		$editing = $this->get_editing_task();
		?>
		<div class="wrap stm-wrap">
			<h1><?php esc_html_e( 'Task Manager', 'smart-task-manager' ); ?></h1>

			<?php if ( $message ) : ?>
				<div class="notice notice-<?php echo esc_attr( $message['type'] ); ?> is-dismissible">
					<p><?php echo esc_html( $message['text'] ); ?></p>
				</div>
			<?php endif; ?>

			<!-- ── Create / Edit Task Form ── -->
			<div class="stm-card">
				<?php if ( $editing ) :
					$edit_status = get_post_meta( $editing->ID, STM_Post_Type::STATUS_META_KEY, true );
					$edit_status = STM_Post_Type::is_valid_status( $edit_status ) ? $edit_status : 'pending';
					?>
				<h2><?php esc_html_e( 'Edit Task', 'smart-task-manager' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( self::NONCE_ACTION_UPDATE, self::NONCE_FIELD_UPDATE ); ?>
					<input type="hidden" name="action" value="stm_update_task">
					<input type="hidden" name="stm_task_id" value="<?php echo esc_attr( (string) $editing->ID ); ?>">

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="stm_task_title"><?php esc_html_e( 'Task Title', 'smart-task-manager' ); ?> <span class="required" aria-hidden="true">*</span></label>
								</th>
								<td>
									<input
										type="text"
										id="stm_task_title"
										name="stm_task_title"
										class="regular-text"
										maxlength="200"
										value="<?php echo esc_attr( $editing->post_title ); ?>"
										required
										autocomplete="off"
									>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="stm_task_status"><?php esc_html_e( 'Task Status', 'smart-task-manager' ); ?></label>
								</th>
								<td>
									<select id="stm_task_status" name="stm_task_status">
										<?php foreach ( STM_Post_Type::get_statuses() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $edit_status, $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>

					<?php
					submit_button( __( 'Update Task', 'smart-task-manager' ), 'primary', 'stm_submit' );
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'smart-task-manager' ); ?>
					</a>
				</form>
				<?php else : ?>
				<h2><?php esc_html_e( 'Create New Task', 'smart-task-manager' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
					<input type="hidden" name="action" value="stm_create_task">

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="stm_task_title"><?php esc_html_e( 'Task Title', 'smart-task-manager' ); ?> <span class="required" aria-hidden="true">*</span></label>
								</th>
								<td>
									<input
										type="text"
										id="stm_task_title"
										name="stm_task_title"
										class="regular-text"
										maxlength="200"
										required
										autocomplete="off"
									>
									<p class="description"><?php esc_html_e( 'Enter a short, descriptive title for the task.', 'smart-task-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="stm_task_status"><?php esc_html_e( 'Task Status', 'smart-task-manager' ); ?></label>
								</th>
								<td>
									<select id="stm_task_status" name="stm_task_status">
										<?php foreach ( STM_Post_Type::get_statuses() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>">
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>

					<?php submit_button( __( 'Add Task', 'smart-task-manager' ), 'primary', 'stm_submit' ); ?>
				</form>
				<?php endif; ?>
			</div>

			<!-- ── Task Table ── -->
			<div class="stm-card">
				<h2><?php esc_html_e( 'All Tasks', 'smart-task-manager' ); ?></h2>

				<?php if ( empty( $tasks ) ) : ?>
					<p class="stm-no-tasks"><?php esc_html_e( 'No tasks yet. Create one above!', 'smart-task-manager' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped stm-table">
						<thead>
							<tr>
								<th scope="col" class="column-title"><?php esc_html_e( 'Task Title', 'smart-task-manager' ); ?></th>
								<th scope="col" class="column-status"><?php esc_html_e( 'Status', 'smart-task-manager' ); ?></th>
								<th scope="col" class="column-date"><?php esc_html_e( 'Created', 'smart-task-manager' ); ?></th>
								<th scope="col" class="column-actions"><?php esc_html_e( 'Actions', 'smart-task-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $tasks as $task ) :
								$status_slug  = get_post_meta( $task->ID, STM_Post_Type::STATUS_META_KEY, true );
								$status_slug  = STM_Post_Type::is_valid_status( $status_slug ) ? $status_slug : 'pending';
								$statuses     = STM_Post_Type::get_statuses();
								$status_label = $statuses[ $status_slug ];
								$delete_url   = wp_nonce_url(
									add_query_arg(
										array(
											'action'  => 'stm_delete_task',
											'task_id' => $task->ID,
										),
										admin_url( 'admin-post.php' )
									),
									'stm_delete_task_' . $task->ID
								);
								$edit_url     = wp_nonce_url(
									admin_url(
										'admin.php?page=' . self::PAGE_SLUG . '&edit_task=' . $task->ID
									),
									self::NONCE_ACTION_EDIT
								);
							?>
							<tr>
								<td class="column-title">
									<strong><?php echo esc_html( $task->post_title ); ?></strong>
								</td>
								<td class="column-status">
									<span class="stm-badge stm-badge--<?php echo esc_attr( $status_slug ); ?>">
										<?php echo esc_html( $status_label ); ?>
									</span>
								</td>
								<td class="column-date">
									<?php echo esc_html( get_the_date( get_option( 'date_format' ), $task->ID ) ); ?>
								</td>
								<td class="column-actions">
									<div class="stm-actions">
										<a
											href="<?php echo esc_url( $edit_url ); ?>"
											class="stm-action-link stm-action-link--edit"
											aria-label="<?php esc_attr_e( 'Edit task', 'smart-task-manager' ); ?>"
										>
											<span class="dashicons dashicons-edit" aria-hidden="true"></span>
										</a>
										<a
											href="<?php echo esc_url( $delete_url ); ?>"
											class="stm-action-link stm-action-link--delete"
											aria-label="<?php esc_attr_e( 'Delete task', 'smart-task-manager' ); ?>"
											onclick="return confirm( '<?php echo esc_js( __( 'Are you sure you want to delete this task?', 'smart-task-manager' ) ); ?>' );"
										>
											<span class="dashicons dashicons-trash" aria-hidden="true"></span>
										</a>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle the task creation form submission via admin-post.php.
	 *
	 * @return void
	 */
	public function handle_create_task(): void {
		// 1. Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'smart-task-manager' ) );
		}

		// 2. Nonce verification.
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			wp_die( esc_html__( 'Security check failed.', 'smart-task-manager' ) );
		}

		// 3. Sanitize inputs.
		$title  = isset( $_POST['stm_task_title'] )
			? sanitize_text_field( wp_unslash( $_POST['stm_task_title'] ) )
			: '';
		$status = isset( $_POST['stm_task_status'] )
			? sanitize_key( wp_unslash( $_POST['stm_task_status'] ) )
			: 'pending';

		// 4. Validate inputs.
		if ( empty( $title ) ) {
			$this->redirect_with_notice( 'error', __( 'Task title cannot be empty.', 'smart-task-manager' ) );
			return;
		}

		if ( ! STM_Post_Type::is_valid_status( $status ) ) {
			$status = 'pending';
		}

		// 5. Insert the post.
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => STM_Post_Type::POST_TYPE,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->redirect_with_notice( 'error', __( 'Failed to create task. Please try again.', 'smart-task-manager' ) );
			return;
		}

		// 6. Save the status as post meta.
		update_post_meta( $post_id, STM_Post_Type::STATUS_META_KEY, $status );

		$this->redirect_with_notice( 'success', __( 'Task created successfully!', 'smart-task-manager' ) );
	}

	/**
	 * Handle task update via admin-post.php.
	 *
	 * @return void
	 */
	public function handle_update_task(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'smart-task-manager' ) );
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD_UPDATE ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD_UPDATE ] ) ), self::NONCE_ACTION_UPDATE )
		) {
			wp_die( esc_html__( 'Security check failed.', 'smart-task-manager' ) );
		}

		$task_id = isset( $_POST['stm_task_id'] ) ? absint( $_POST['stm_task_id'] ) : 0;

		if ( ! $task_id || STM_Post_Type::POST_TYPE !== get_post_type( $task_id ) ) {
			$this->redirect_with_notice( 'error', __( 'Invalid task.', 'smart-task-manager' ) );
			return;
		}

		$title  = isset( $_POST['stm_task_title'] )
			? sanitize_text_field( wp_unslash( $_POST['stm_task_title'] ) )
			: '';
		$status = isset( $_POST['stm_task_status'] )
			? sanitize_key( wp_unslash( $_POST['stm_task_status'] ) )
			: 'pending';

		if ( empty( $title ) ) {
			$this->redirect_with_notice( 'error', __( 'Task title cannot be empty.', 'smart-task-manager' ) );
			return;
		}

		if ( ! STM_Post_Type::is_valid_status( $status ) ) {
			$status = 'pending';
		}

		$result = wp_update_post(
			array(
				'ID'         => $task_id,
				'post_title' => $title,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( 'error', __( 'Failed to update task. Please try again.', 'smart-task-manager' ) );
			return;
		}

		update_post_meta( $task_id, STM_Post_Type::STATUS_META_KEY, $status );

		$this->redirect_with_notice( 'success', __( 'Task updated successfully!', 'smart-task-manager' ) );
	}

	/**
	 * Handle task deletion.
	 *
	 * @return void
	 */
	public function handle_delete_task(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'smart-task-manager' ) );
		}

		$task_id = isset( $_GET['task_id'] ) ? absint( $_GET['task_id'] ) : 0;

		if ( ! $task_id ) {
			$this->redirect_with_notice( 'error', __( 'Invalid task.', 'smart-task-manager' ) );
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'stm_delete_task_' . $task_id )
		) {
			wp_die( esc_html__( 'Security check failed.', 'smart-task-manager' ) );
		}

		// Confirm it's our post type before deleting.
		if ( STM_Post_Type::POST_TYPE !== get_post_type( $task_id ) ) {
			wp_die( esc_html__( 'Invalid task type.', 'smart-task-manager' ) );
		}

		wp_delete_post( $task_id, true ); // true = force delete, bypass trash.

		$this->redirect_with_notice( 'success', __( 'Task deleted.', 'smart-task-manager' ) );
	}

	// ──────────────────────────────────────────────
	// Private helpers
	// ──────────────────────────────────────────────

	/**
	 * Retrieve all tasks ordered newest first.
	 *
	 * @return WP_Post[]
	 */
	private function get_tasks(): array {
		$query = new WP_Query(
			array(
				'post_type'      => STM_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true, // Skip pagination count for performance.
			)
		);

		return $query->posts;
	}

	/**
	 * Redirect back to the plugin page with a transient notice.
	 *
	 * @param string $type 'success' or 'error'.
	 * @param string $text Human-readable message.
	 * @return void
	 */
	private function redirect_with_notice( string $type, string $text ): void {
		$user_id = get_current_user_id();
		set_transient( 'stm_notice_' . $user_id, array( 'type' => $type, 'text' => $text ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Retrieve and clear the one-time admin notice for the current user.
	 *
	 * @return array{type:string,text:string}|null
	 */
	private function get_admin_notice(): ?array {
		$user_id = get_current_user_id();
		$key     = 'stm_notice_' . $user_id;
		$notice  = get_transient( $key );

		if ( $notice ) {
			delete_transient( $key );
			return $notice;
		}

		return null;
	}

	/**
	 * Resolve the task being edited from the query string, if valid.
	 *
	 * @return WP_Post|null
	 */
	private function get_editing_task(): ?WP_Post {
		if ( ! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE_ACTION_EDIT )
		) {
			return null;
		}

		if ( ! isset( $_GET['edit_task'] ) ) {
			return null;
		}

		$task_id = absint( wp_unslash( $_GET['edit_task'] ) );

		if ( ! $task_id || STM_Post_Type::POST_TYPE !== get_post_type( $task_id ) ) {
			return null;
		}

		$task = get_post( $task_id );

		return ( $task instanceof WP_Post ) ? $task : null;
	}
}
