# Smart Task Manager – Development Plan

> Written before a single line of plugin code was typed.

---

## 1. What the Plugin Does and How It Works

**Smart Task Manager** is a lightweight WordPress admin plugin that lets site administrators create, view, edit, and delete tasks without leaving the dashboard. It is intentionally minimal: no front-end output, no REST endpoints exposed to the public, no third-party dependencies.

### User journey

1. Admin visits the **Task Manager** top-level menu page.
2. A form at the top lets them enter a **Task Title** and choose a **Status** (`Pending` or `Completed`).
3. Submitting the form POSTs to `admin-post.php` (WordPress's canonical handler for admin form actions).
4. The handler validates, sanitizes, and inserts a new `stm_task` CPT post.
5. The page reloads and the new task appears in the table below.
6. Each row has **Edit** and **Delete** icon actions (WordPress dashicons) in the Actions column.
7. **Edit** opens the same form pre-filled with the task's title and status; submitting POSTs to `admin-post.php` to update the record.
8. **Delete** is a GET request with a per-task nonce and confirmation prompt to permanently remove the record.

Create and update use POST-then-redirect (PRG pattern) with transient-based notices so page refresh never re-submits the form.

---

## 2. File and Folder Structure

```
smart-task-manager/
├── smart-task-manager.php          # Main plugin file; header, constants, bootstrap, activation hooks
├── readme.txt                      # WordPress Plugin Directory readme (SVN format)
├── uninstall.php                   # Cleanup on plugin deletion
├── PLAN.md                         # This document
├── assets/
│   └── admin/
│       └── css/
│           └── stm-admin.css       # Admin-only stylesheet; table badges and icon action buttons
├── includes/
│   ├── class-stm-post-type.php     # CPT registration + shared constants & helpers
│   └── class-stm-admin.php         # Admin menu, create/edit form, task table, POST/GET handlers
└── languages/                      # (optional) .pot / translation files — Domain Path in plugin header
```

### Why this structure?

* **`includes/`** keeps class files away from the root, matching the pattern used by popular plugins (WooCommerce, Yoast SEO) and the WordPress Plugin Handbook.
* **`assets/admin/`** separates presentation assets from logic; if JS is added later it goes in `assets/admin/js/`.
* **`languages/`** holds translation templates when the plugin is submitted to translate.wordpress.org (declared via `Domain Path` in the plugin header).
* The root contains only the plugin entry point, the directory readme, and the uninstall script — exactly what the Plugin Directory expects.

---

## 3. How Tasks Are Stored — CPT vs `wp_options`

### Decision: Custom Post Type (`stm_task`)

| Criterion | `wp_options` | Custom Post Type |
|-----------|-------------|-----------------|
| Designed for | Scalar config values | Collections of user records |
| Queryable with `WP_Query` | ✗ | ✓ |
| Per-record meta support | ✗ | ✓ (post meta) |
| Participates in trash / uninstall lifecycle | ✗ | ✓ |
| Scales to hundreds of records | Poor (single serialised blob) | ✓ (indexed rows) |
| Future: REST, CLI, taxonomy | Hard | Trivial |

`wp_options` makes sense for plugin settings (on/off switches, API keys). A list of variable-length user-created records is a classic relational data problem — exactly what posts + post meta solve.

### Schema

| Storage | Key | Values |
|---------|-----|--------|
| `wp_posts` | `post_type` | `stm_task` |
| `wp_posts` | `post_title` | Task title text |
| `wp_posts` | `post_status` | `publish` |
| `wp_postmeta` | `_stm_task_status` | `pending` \| `completed` |

The `_` prefix on `_stm_task_status` is a WordPress convention that hides the key from the native Custom Fields UI, preventing accidental manual edits.

---

## 4. Plugin Directory Readiness

### Coding Standards

* **WordPress Coding Standards (WPCS)** — spacing, braces, naming, and file layout follow the [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
* **Type declarations** — PHP 7.4+ return types and parameter types used throughout.
* **DocBlocks** — every class, method, constant, and property is documented.

### Naming Conventions

| Scope | Convention | Example |
|-------|-----------|---------|
| Functions | `stm_` prefix | `stm_init()` |
| Classes | `STM_` prefix, PascalCase | `STM_Admin` |
| Constants | `STM_` prefix, SCREAMING_SNAKE | `STM_VERSION` |
| CPT slug | `stm_task` | — |
| Meta keys | `_stm_` prefix | `_stm_task_status` |
| Menu slug | `smart-task-manager` | — |
| Text domain | `smart-task-manager` | — |
| CSS classes | `stm-` prefix | `.stm-card` |

The `stm_` / `STM_` namespace is short but specific enough to avoid collisions with other plugins.

### Security Practices

| Vector | Defence |
|--------|---------|
| CSRF on create/update forms | `wp_nonce_field()` + `wp_verify_nonce()` |
| CSRF on delete link | `wp_nonce_url()` (per-record nonce) + `wp_verify_nonce()` |
| Capability gating | `current_user_can('manage_options')` on every handler and render |
| Output escaping | `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()` at every echo point |
| Input sanitization | `sanitize_text_field()`, `sanitize_key()`, `absint()` before any use |
| Direct file access | `if ( ! defined( 'ABSPATH' ) ) exit;` in every PHP file |
| Uninstall guard | `if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;` in `uninstall.php` |
| Wrong CPT update/delete | Type-check (`get_post_type()`) before `wp_update_post()` / `wp_delete_post()` |
| Status whitelist | `STM_Post_Type::is_valid_status()` rejects unexpected values |

### Additional Directory Checklist

- [x] GPL v2 or later licence declared in header and `readme.txt`
- [x] `readme.txt` in WordPress SVN format (Tested up to, Stable tag, etc.)
- [x] `uninstall.php` removes all plugin data on deletion
- [x] Assets enqueued only on the plugin's own admin page (no global enqueue)
- [x] No `eval()`, no `base64_decode()` of executable code, no obfuscation
- [x] All i18n strings use the `smart-task-manager` text domain
- [x] No hardcoded table prefixes (`$wpdb->prefix` would be used if raw SQL were needed)
- [x] PRG (Post–Redirect–Get) pattern prevents duplicate form submissions
