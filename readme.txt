=== Post Lockdown ===
Contributors: andyexeter
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=BRET43XLNLZCJ&lc=GB&item_name=Post%20Lockdown&currency_code=GBP&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: posts, lock, protect, capabilities, capability, trash, delete
Requires at least: 3.8
Tested up to: 4.5
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows admins to protect selected posts and pages so they cannot be trashed or deleted by non-admin users.

== Description ==

Post Lockdown protects your site-critical pages and posts by disabling all non-admin users' ability to trash or delete them.
It can also lock pages and posts, which will disable editing of the post as well as disabling trashing/deleting.

The plugin adds a new options page under the Settings menu in your WordPress admin panel which allows you to quickly search and
select for all pages and posts of any post type. When you find the item you want to select, simply click it to move it to the right
box and click Save Changes.

See the screenshots for an example of what an Editor would see when they view a list of posts with some locked and protected.

== Installation ==

1. Upload the `post-lockdown` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Select your locked/protected posts under Settings > Post Lockdown

== Frequently Asked Questions ==

= Are there any major changes in v2.0? =

The plugin now stores an instance of the class in a global variable (`$postlockdown`) instead of using static class methods.
If you use any of the static methods like `PostLockdown::is_post_protected( $post_id )` in your theme then
you'll need to update your code to the following to be able to use v2.0:

`
global $postlockdown;
$postlockdown->is_post_protected( $post_id );
`

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

1. A page list showing one regular page, one locked page and one protected page with the plugin's status column visible.
2. The Publish metabox for a protected page logged in as an Editor. See how the plugin removes the Move to Trash link.
3. The Post Lockdown administration page.

== Changelog ==

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

= 2.0 =
This is a major version release. Please read the changelog before updating.
