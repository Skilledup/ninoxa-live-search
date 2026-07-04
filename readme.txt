=== Ninoxa Live Search ===
Contributors: macse2
Tags: search, live search, ajax search, real-time search
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.3
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A plugin to add live search functionality to your WordPress site.

== Description ==

Ninoxa Live Search adds an accessible, real-time AJAX-powered live search to your WordPress site. It supports multilingual sites via Polylang and WPML.

* Use a standard WordPress search form; results appear below the input.
* Min query: 3 chars. Shows configurable number of results (defaults to 10) + a "More results..." link.
* Manage plugin options from the **Ninoxa Live Search** admin menu.
* The default shortcut is **Ctrl + /**, and you can replace it with your own key combination or disable it completely.
* Optional **type-to-search**: when enabled, typing two characters outside any input focuses the search field and inserts the text.

Features:
* Real-time search results as you type
* Frontend matching modes (keyword, any word, exact phrase, whole word, fuzzy) using the native WordPress search engine; fuzzy mode tolerates one-character typos via edit-distance-1 LIKE patterns
* AJAX-powered with nonce security
* Polylang and WPML compatible
* Accessible (ARIA attributes)
* Configurable keyboard shortcut
* Optional type-to-search (focus search when typing two characters outside any input; disabled by default)
* Lightweight and fast

== Installation ==

1. Upload the `ninoxa-live-search` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. The live search will be automatically enabled on your site.
4. Configure options from the Ninoxa Live Search admin menu.

== Keyboard Shortcuts ==

Global:

* **Ctrl + /** by default: Focus the Ninoxa Live Search input
* Keyboard shortcut can be customized or disabled from the Ninoxa Live Search admin menu

Type-to-search (opt-in):

* When enabled in settings, typing **two printable characters in quick succession** anywhere on the page (outside inputs, textareas, and contenteditable regions) focuses the search field and inserts the typed text
* A lone keypress is ignored, so accidental single-key bumps do not hijack focus
* Works alongside the keyboard shortcut above; disabled by default
* Inactive on touch-first devices

Search results:

* **Arrow Up/Down**: Navigate through search results
* **Enter**: Select highlighted result
* **Escape**: Close search results
* **Tab**: Move focus into the matching mode bar (keeps results open)

Matching mode bar:

* **Arrow Left/Right**: Move between matching modes
* **Home/End**: Jump to first / last mode
* **Enter / Space**: Activate the focused mode
* **Escape**: Return focus to the search input

== Frequently Asked Questions ==

= Does this plugin support multilingual sites? =

Yes. It supports both Polylang and WPML.

= Does this plugin support both Classic and Block themes? =

Yes. It works out of the box with standard WordPress search forms across all themes.

== Changelog ==

= 1.3.3 =
* Added optional type-to-search: typing two characters outside any input focuses the search field and inserts the typed text. Enable it from the General settings tab.
* Improved WordPress Playground live preview with a dedicated front page and search block for easier demos.

= 1.3.2 =
* Added WordPress Playground live preview blueprint so visitors can try the plugin before installing.

= 1.3.1 =
* Fixed settings checkbox saving so unchecked toggles are correctly stored as off.
* Improved keyboard focus behavior so live-search results stay open while tabbing inside the search form and close reliably once focus leaves the search UI.

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

= 1.3.3 =
Adds optional type-to-search for keyboard-first visitors. Disabled by default.

= 1.3.2 =
Adds WordPress Playground live preview support on the plugin directory.

= 1.3.1 =
Includes bug fixes for settings toggle persistence and keyboard focus handling in live search.

= 1.3.0 =
Adds frontend search matching modes (keyword, whole word, fuzzy, and more) and Redesigned settings UI. Update recommended.