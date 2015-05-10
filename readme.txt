=== Post Lockdown ===
Contributors: andyexeter
Donate link: http://bit.ly/1b2f6OL
Tags: posts, lock, protect, capabilities, trash, delete
Requires at least: 3.6
Tested up to: 4.2.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows admins to lock selected posts and pages so they cannot be edited or deleted by non-admin users.

= Description ==

Post Lockdown locks your site-critical pages and posts by disabling all non-admin users' ability to edit or delete them.
It can also protect pages/posts, which will allow editing of them but still disable trashing and deleting.

The plugin adds a new options page under the Settings menu in WordPress admin panel. The page contains separate lists of posts for
all post types (except nav_menu_item). Within this page you can select which posts should be locked and which should be protected.

See the screenshots for an example of what an Editor would see when they view a list of posts with some locked and protected

== Installation ==

1. Upload `post-lockdown` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Choose your locked/protected pages under Settings > Post Lockdown

== FAQ ==

= What is a "non-admin user"? =

The plugin classes a non-admin as a user who does not have the `manage_options` capability, like an Editor.

== Screenshots ==

1. A page list showing one locked page, one protected page and one normal page.
2. The Publish metabox for a protected page logged in as an Editor. See how the plugin removes the Move to Trash link.
3. The WordPress error message when a non-admin trys to delete a protected page via the bulk options Move to Trash option.

== Changelog ==

= 1.0.0 =
Initial release
