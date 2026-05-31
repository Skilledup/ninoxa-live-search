=== Ninoxa Live Search ===
Contributors: macse2
Tags: search, live search, ajax search, real-time search
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A plugin to add live search functionality to your WordPress site.

== Description ==

Ninoxa Live Search adds an accessible, real-time AJAX-powered live search to your WordPress site. It supports multilingual sites via Polylang and WPML.

* Use a standard WordPress search form; results appear below the input.
* Min query: 3 chars. Shows configurable number of results (defaults to 10) + a "More results..." link.
* Manage plugin options from the **Ninoxa Live Search** admin menu.
* The default shortcut is **Ctrl + /**, and you can replace it with your own key combination or disable it completely.

Features:
* Real-time search results as you type
* Frontend matching modes (keyword, any word, exact phrase, whole word, fuzzy) using the native WordPress search engine; fuzzy mode tolerates one-character typos via edit-distance-1 LIKE patterns
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

= Does this plugin support both Classic and Block themes? =

Yes. It works out of the box with standard WordPress search forms across all themes.

== Changelog ==

= 1.3.0 =
* Added result matching modes (All words, Any word, Exact phrase, Whole word, and Fuzzy) powered entirely by the native WordPress and MySQL search engine; fuzzy mode uses edit-distance-1 LIKE patterns so "helo" matches "hello".
* Visitors can switch the matching mode directly from the search field; results refresh instantly.
* New "Matching" settings tab to enable the controls, choose available modes, and set the default mode.
* Redesigned settings UI with card-based layout and dark blue color palette.
* Three-tab settings navigation: General, Loading, and About.
* CSS toggle switches for spinner and light sweep options.
* Collapsible sections for loading indicator sub-settings.
* Sticky sidebar with contextual tips on the settings page.
* About tab with plugin info.
* Configurable number of results setting.

= 1.1.1 =
* Light sweep loading animation
* Loading indicators settings
* Loading spinner wasn't display properly, is fixed.

= 1.1.0 =
* Admin menu and settings for Keyboard shortcut customization.


= 1.0.8 =
* Fixed text domain to match plugin slug.
* Improved input sanitization.
* Removed deprecated load_plugin_textdomain() calls.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.3.0 =
Adds frontend search matching modes (keyword, whole word, fuzzy, and more) and Redesigned settings UI. Update recommended.
