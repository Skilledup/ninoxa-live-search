=== Ninoxa Live Search ===
Contributors: macse2
Tags: search, live search, ajax search, real-time search
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A plugin to add live search functionality to your WordPress site.

== Description ==

Ninoxa Live Search adds an accessible, real-time AJAX-powered live search to your WordPress site. It supports multilingual sites via Polylang and WPML.

* Use a standard WordPress search form; results appear below the input.
* Min query: 3 chars. Shows up to 10 results + a "More results..." link.
* Manage plugin options from the **Ninoxa Live Search** admin menu.
* The default shortcut is **Ctrl + /**, and you can replace it with your own key combination or disable it completely.

Features:
* Real-time search results as you type
* AJAX-powered with nonce security
* Polylang and WPML compatible
* Accessible (ARIA attributes)
* Configurable keyboard shortcut
* Lightweight and fast

== Installation ==

1. Upload the `ninoxa-live-search` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. The live search will be automatically enabled on your site.
4. Configure options from the Ninoxa Live Search admin menu.

== Keyboard Shortcuts ==

* **Ctrl + /** by default: Focus the Ninoxa Live Search input
* Keyboard shortcut can be customized or disabled from the Ninoxa Live Search admin menu
* **Arrow Up/Down**: Navigate through search results
* **Enter**: Select highlighted result
* **Escape**: Close search results
* **Tab**: Close search results and move focus

== Frequently Asked Questions ==

= Does this plugin support multilingual sites? =

Yes. It supports both Polylang and WPML.

== Changelog ==

= 1.1.0 =
* Admin menu and settings for Keyboard shortcut customization.


= 1.0.8 =
* Fixed text domain to match plugin slug.
* Improved input sanitization.
* Removed deprecated load_plugin_textdomain() calls.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Improved functionalities. Update recommended.
