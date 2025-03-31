# RA Revisions

**Version:** 2.1  
**Author:** Amin Rahnama  
**Plugin URI:** [https://mypixellab.com]

---

RA Revisions is a lightweight WordPress plugin that helps you manage post revisions by limiting how many are stored and providing tools to clean them up manually or on a schedule. Keep your database optimized and under control with a clean, simple interface.

---

## 🚀 Features

- Limit how many revisions are kept per post
- Schedule automatic cleanup (daily, weekly, or monthly)
- Manual cleanup option with one click
- Displays total revisions currently in the database
- Simple admin settings page
- Cron-based scheduled cleanup using native WordPress events

---

## ⚙️ Plugin Settings

- **Revisions Limit** – Enter how many revisions to keep for each post.
- **Cleanup Frequency** – Choose how often to run the automatic cleanup (daily, weekly, or monthly).
- **Manual Cleanup** – Use the button to instantly delete excess revisions across all posts.

---

## 📦 Installation

1. Download the plugin or clone the repository into your `/wp-content/plugins/` directory.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Go to **RA Revisions** in the WordPress admin menu to configure settings.

---

## 🧼 Why Use RA Revisions?

WordPress stores a revision every time a post or page is updated. Over time, this can add up to thousands of extra database entries. RA Revisions helps keep your site lean by automatically removing excess revisions while preserving the latest few per post.

---

## 📅 Scheduled Cleanup

- Uses WordPress cron jobs to run cleanup tasks
- Frequency options: daily, weekly, or monthly
- Custom cron interval for monthly cleanups is included

---

## 🛠️ Developers

- Filters:
  - `wp_revisions_to_keep` – Filter to control revision limit (used internally)
- Hooks:
  - `ra_daily_revision_cleanup` – Hook triggered by scheduled cleanup

---

## 🧪 Requirements

- WordPress 5.0+
- PHP 7.0+

---

## 📝 Changelog

### 2.1
- Added admin notices for manual and scheduled cleanup
- Improved safety checks and sanitization
- Fixed scheduling issue when changing frequency

### 2.0
- Added scheduling options
- Rewritten cleanup logic with better query performance

### 1.0
- Initial release with revision limit and manual cleanup

---

## 📖 License

Licensed under the GPL2 license.

---

## 👤 Author

Amin Rahnama  
Website: [https://mypixellab.com](https://mypixellab.com)
