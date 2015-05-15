=== Post Lockdown ===
Contributors: andyexeter
Donate link: http://bit.ly/1b2f6OL
Tags: posts, lock, protect, capabilities, capability, trash, delete
Requires at least: 3.8
Tested up to: 4.2.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows admins to lock selected posts and pages so they cannot be edited or deleted by non-admin users.

= Description ==

Post Lockdown locks your site-critical pages and posts by disabling all non-admin users' ability to edit or delete them.
It can also protect pages and posts, which will allow editing of them but still disable trashing and deleting.

The plugin adds a new options page under the Settings menu in WordPress admin panel which allows you to quickly search and
select for all pages and posts of any post type. When you find the item you want to select, simply click it to move it to the right
box and click Save Changes.

See the screenshots for an example of what an Editor would see when they view a list of posts with some locked and protected

== Installation ==

1. Upload the `post-lockdown` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Select your locked/protected posts under Settings > Post Lockdown

== FAQ ==

= What is a "non-admin user"? =

By default, the plugin classes a non-admin as a user who does not have the `manage_options` capability e.g an Editor.
The capability can be filtered using the `postlockdown_admin_capability` filter.

= Are there any other filters I can use? =

Yep. Check out the Developers section for a list of all available filters.

== Screenshots ==

1. A page list showing one locked page, one protected page and one regular page.
2. The Publish metabox for a protected page logged in as an Editor. See how the plugin removes the Move to Trash link.
3. The Post Lockdown administration page.

== Developers ==

The following filters are used throughout the plugin:

* `postlockdown_admin_capability` - The capability a user must have to bypass locked/protected posts restrictions. Default is `manage_options`.
* `postlockdown_capabilities` - Array of capabilities to restrict.
* `postlockdown_locked_posts` - Array of locked post IDs. Allows you to programatically add or remove post IDs. Both the key AND value must be set to the post ID
* `postlockdown_protected_posts` - Array of protected post IDs. Allows you to programatically add or remove post IDs. Both the key AND value must be set to the post ID
* `postlockdown_get_posts` - Array of args to pass to get_posts().

== Changelog ==

= 1.0.0 =
Initial release
