=== Smart Task Manager ===
Contributors:      jalal02
Tags:              tasks, task manager, to-do, productivity, admin
Requires at least: 5.9
Tested up to:      7.0
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A lightweight task manager to create and track tasks with statuses directly from the WordPress admin dashboard.

== Description ==

**Smart Task Manager** adds a clean, focused task management page to your WordPress admin panel. No bloat, no external dependencies — just WordPress core functions doing what they do best.

= Features =

* Top-level **Task Manager** admin menu with the WordPress clipboard dashicon.
* Create tasks with a **Title** and a **Status** (Pending or Completed).
* Edit existing tasks — title and status — from the same admin form.
* View all tasks in a sortable `WP_List_Table`-styled table.
* Edit and delete tasks via **dashicon** buttons in the Actions column (with delete confirmation).
* Status displayed as colour-coded badges for at-a-glance readability.
* Fully internationalised — every string is wrapped in i18n functions and a `smart-task-manager` text domain.
* No options or external HTTP calls — no data leaves your server.

== How Tasks Are Stored ==

Tasks are stored as a **Custom Post Type** (`stm_task`) rather than WordPress options for the following reasons:

1. **Scalability** — `wp_options` is designed for scalar configuration values. A list of user-generated records belongs in posts, where WordPress already has indexing, querying (`WP_Query`), and meta support built in.
2. **Integrity** — CPT records participate in the WordPress data lifecycle (revisions disabled, trash support optional) and are properly removed on uninstall via `uninstall.php`.
3. **Future extensibility** — adding taxonomy-based filtering, REST API exposure, or a WP-CLI command is trivial with a CPT.

Each task stores one piece of post meta:

| Meta Key            | Values                  |
|---------------------|-------------------------|
| `_stm_task_status`  | `pending` / `completed` |

The leading underscore (`_`) hides the key from the native Custom Fields UI.

== Installation ==

= Via the WordPress Dashboard (recommended) =

1. Download `smart-task-manager.zip`.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.
5. Navigate to the new **Task Manager** item in the admin sidebar.

= Via FTP =

1. Unzip `smart-task-manager.zip`.
2. Upload the `smart-task-manager` folder to `/wp-content/plugins/`.
3. In the WordPress dashboard go to **Plugins** and activate **Smart Task Manager**.

= Minimum Requirements =

* WordPress 5.9 or higher
* PHP 7.4 or higher

== Usage ==

1. Open **Task Manager** from the admin sidebar.
2. Fill in a **Task Title** and choose a **Status** (`Pending` or `Completed`).
3. Click **Add Task** — the task appears in the table below immediately.
4. To edit a task, click the pencil icon in the Actions column, update the form, and click **Update Task**.
5. To remove a task, click the trash icon in the Actions column and confirm.

== AI Tools Used ==

This plugin was planned and built with assistance from **Claude (Anthropic)**. Claude was used to:

* Draft and review the file/folder structure against WordPress Plugin Directory guidelines.
* Write and cross-check PHP, ensuring WordPress Coding Standards (WPCS) compliance.
* Generate inline DocBlocks and the README.

All generated code was reviewed and tested by a human developer. No AI-generated content is served to end users.

== Frequently Asked Questions ==

= Does the plugin add any database tables? =

No. It uses WordPress's native `wp_posts` and `wp_postmeta` tables through the standard CPT and meta APIs.

= Will my tasks be removed when I uninstall the plugin? =

Yes. `uninstall.php` permanently deletes all `stm_task` posts and their meta when you remove the plugin via the dashboard.

= Can I translate the plugin? =

Yes. All user-facing strings use the `smart-task-manager` text domain and are compatible with `translate.wordpress.org`.

== Screenshots ==

1. The Task Manager admin page showing the creation form and task table.

== Changelog ==

= 1.0.0 =
* Initial release.
* Create, edit, and delete tasks with Pending or Completed status.
* Icon-based edit and delete actions in the task table.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
