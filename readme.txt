=== Post Lockdown ===
Contributors: andyexeter
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=BRET43XLNLZCJ&lc=GB&item_name=Post%20Lockdown&currency_code=GBP&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: protect, lock, permissions, user roles, delete
Requires at least: 4.6
Tested up to: 7.0
Stable tag: 4.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Stop non-admin users trashing, deleting or unpublishing your site-critical posts and pages. Lock content down in seconds - no code required.

== Description ==

Your homepage. Your contact page. The landing page that took three weeks to sign off. One accidental click from a contributor
or a well-meaning editor and it's in the trash - or worse, gone for good.

Post Lockdown puts your site-critical content out of reach. Choose which posts and pages matter, and Post Lockdown stops
non-admin users from trashing or deleting them - no custom code, no role editor, no rebuilding permissions from scratch.

**Two levels of safety**

* **Protected** - the post can still be edited, but it can't be trashed, deleted, or unpublished. Editors keep working; the page stays live.
* **Locked** - the post can't be edited, trashed or deleted at all. Use it for content that must not change.

**Set it up in under a minute**

Post Lockdown adds a single settings page under Settings > Post Lockdown. Start typing to search every post, page, attachment
and custom post type on your site, click an item to add it to the Locked or Protected box, then hit Save Changes. That's it.

**Built to stay out of the way**

* Locked and protected items are clearly flagged in a status column on your post and page lists, so nobody wonders why an action is missing.
* Works with both the block editor and the classic editor. If someone tries to unpublish a protected post, the change is reverted and a notice explains why.
* Optional bulk actions let you lock or protect items straight from the post list.
* Manage everything from the command line with WP-CLI - run `wp postlockdown` to see the available commands.
* A full set of filters lets developers change which capability counts as "admin", set locked and protected posts programmatically, and customise the status column. See the FAQ for the complete list.

See the screenshots for exactly what an Editor sees when they open a list of locked and protected posts.

== Installation ==

1. Upload the `post-lockdown` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Select your locked/protected posts under Settings > Post Lockdown

== Frequently Asked Questions ==

= What is a "non-admin user"? =

By default, the plugin classes a non-admin as a user who does not have the `manage_options` capability e.g an Editor.
The capability can be filtered using the `postlockdown_admin_capability` filter.

= Are there any other filters I can use? =

The following filters are used throughout the plugin:

* `postlockdown_admin_capability` - The capability a user must have to bypass locked/protected posts restrictions. Default is `manage_options`.
* `postlockdown_capabilities` - Array of capabilities to restrict.
* `postlockdown_excluded_post_types` - Array of post types to exclude from search.
* `postlockdown_get_posts` - Array of args to pass to get_posts().
* `postlockdown_locked_posts` - Array of locked post IDs. Allows you to programmatically add or remove post IDs. Both the key AND value must be set to the post ID.
* `postlockdown_protected_posts` - Array of protected post IDs. Allows you to programmatically add or remove post IDs. Both the key AND value must be set to the post ID.
* `postlockdown_column_hidden_default` - Boolean which dictates whether the status column should appear by default on post lists. Defaults to false.
* `postlockdown_column_html` - String of HTML showing the locked or protected status of a post in the status column on post lists.
* `postlockdown_column_label` - String containing the heading/label for the status column on post lists.

== Screenshots ==

1. The Post Lockdown settings page. Add posts and pages of any type to the Locked or Protected boxes.
2. Locked and protected items are flagged in a status column on the posts and pages lists.
3. An Editor's view of the page list: a locked page cannot be edited, selected or trashed, while a protected page can still be edited but not trashed.
4. A protected post opened in the block editor by an Editor - the Move to Trash action is removed.
5. When a non-admin tries to unpublish a protected post the change is reverted and a notice explains why.

== Changelog ==

= 4.1.1 =

* Fixed a fatal error on activation caused by the BlockEditorNotice class file being omitted from the 4.1.0 release package

= 4.1.0 =

* New feature: when a non-admin tries to unpublish a protected post in the block editor, the change is reverted and a notice is shown - matching the existing classic editor behaviour

= 4.0.5 =

* Removed a couple of development files erroneously included in previous release

= 4.0.4 =
This is a security release. Please update as soon as possible.

* Added a capability check and nonce to the autocomplete AJAX request to prevent unauthorised access to the post list (Thanks to Krzysztof Zając)
* Added sanitization to autocomplete search term
* Added sanitization to the plugin's settings
* Added version string to the plugin's enqueued CSS and JS files to prevent caching issues
* Added missing text domain to the plugin's settings page footer text
* Added wp_kses to the Post Lockdown status column to only allow certain HTML tags

= 4.0.2 =
* Fixed a warning in WordPress 6.7 related to loading translations too early

= 4.0 =
This is a major version release. Please read the following notes carefully before updating.

* Fixed a bug which caused the plugin to not work correctly with the new block editor (Gutenberg). This is fixed by loading the plugin for all requests (including REST requests) rather than just the admin area
* Fixed a bug which caused the uninstall hook to not be called when the plugin was deleted

= 3.0.13 =
* Updated minimum required WordPress version to 4.6 so translations are loaded from translate.wordpress.org (Thanks to @huubl)

= 3.0.8 =
* Updated text domain to match plugin slug for localization (Thanks to @huubl)

= 3.0.7 =
* Added internationalization support to post list status column (Thanks to @huubl)

= 3.0.6 =
* Fixed a bug which allowed non-admins access to Post Lockdown's bulk actions

= 3.0.5 =
* New feature: Added bulk actions to post list screens. This is an opt-in feature which must be enabled on the Post Lockdown settings page. (h/t @khaliel for the idea)

= 3.0.4 =
* Fixed a bug that caused authors to be able to edit and delete other's posts (Thanks @kumar314)
* Fixed a PHP warning that appeared when creating a new post

= 3.0.3 =
* Improved performance whilst fetching posts (Thanks to joshuadavidnelson)

= 3.0 =
This is a major version release. Please read the following notes carefully before updating.

* Added WP-CLI integration. You can now edit locked and protected posts via the WordPress CLI! Run `wp postlockdown` to see the list of available commands
* Bumped PHP version requirement to 5.6 and refactored codebase to use namespaces and PSR-2 coding standards
* Moved get_posts wrapper method from OptionsPage to PostLockdown so it can be used by the CLI
* Added `add_locked_post`, `add_protected_post`, `remove_locked_post` and `remove_protected_post` methods to main class

= 2.1 =
* Added the ability to lock and protect attachments.

= 2.0.3 =
* Added private posts to the list of available posts to protect or lock.

= 2.0.2 =
* Fixed missing call to get_post_types() when retrieving posts.
* Removed unnecessary files

= 2.0.1 =
* Added private posts to the list of available posts to protect or lock.

= 2.0 =
This is a major version release. Please read the following notes carefully before updating.

* Major refactor of code base for performance and future scalability. If you are a developer using any of the plugin class static methods read the FAQ before updating.
* Added a column to post lists to show the locked or protected status of each post.
* Added new filters: `postlockdown_column_hidden_default`, `postlockdown_column_html` and `postlockdown_column_label`.
* Lots of optimisations and general improvements.

= 1.1.1 =
* Fixed PHP warning about missing admin notices file.

= 1.1 =

* Added functionality to prevent non-admins changing the post status of a protected published post to something which could remove it from the front end e.g Draft, Private or Scheduled.
* Fixed an issue which caused a PHP warning when a non-admin used the Quick Edit box for a protected post.
* Added new version of multi select plugin.
* Lots of optimisations and general improvements.

= 1.0.1 =

* Fixed an issue where post IDs could not be filtered if none were set on the options page.
* Added revisions and the WooCommerce product_variation post type to the excluded post types list.
* Added escaping to placeholder attributes for search fields.
* Added a new filter: `postlockdown_excluded_post_types`.

= 1.0.0 =

* Initial release

== Upgrade Notice ==

= 4.0 =
This is a major version release. Please read the changelog before updating.

= 3.0 =
This is a major version release. Please read the changelog before updating.

= 2.0 =
This is a major version release. Please read the changelog before updating.
