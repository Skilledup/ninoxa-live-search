# Ninoxa Live Search

AJAX-powered, instant search results for your WordPress search forms. Works out of the box.

## Features

- Real-time results (AJAX)
- Multilingual: Polylang, WPML, or fallback to default
- Accessible: ARIA + keyboard navigation
- Configurable keyboard shortcut with a dedicated settings screen
- Optional type-to-search: start typing outside any input to focus the search field (works in Firefox)
- Optional plugin focus outline on the search field (can be turned off to keep theme styles)
- Frontend matching modes: All words, Any word, Exact phrase, Whole word, Fuzzy — switchable from the search field, including Alt+1, Alt+2, … shortcuts
- Fuzzy mode tolerates one-character typos via edit-distance-1 MySQL `LIKE` patterns (e.g. "helo" matches "hello") — no external dependencies
- Secure: nonce + sanitized input
- Translation-ready

## Usage

- Use a standard WordPress search form; results appear below the input.
- Min query: 3 chars. Shows configurable number of results (defaults to 10) + a "More results..." link.
- Manage plugin options from the **Ninoxa Live Search** admin menu.
- The default shortcut is **Ctrl + /**, and you can replace it with your own key combination or disable it completely.

## Keyboard Shortcuts

### Global

- **Ctrl + /** by default: Focus the first available Ninoxa Live Search input on the page
- Shortcut value is configurable from the **Ninoxa Live Search** admin menu

### Type-to-search (opt-in)

When enabled in settings, typing **two printable characters in quick succession** anywhere on the page (outside inputs, textareas, and contenteditable regions) focuses the search field and inserts the typed text. Keys are captured before the browser, so this also works in Firefox when find-as-you-type is enabled. A lone keypress is ignored, so accidental single-key bumps do not hijack focus. Works alongside the keyboard shortcut above. Disabled by default; leave off if your theme or plugins rely on single-key shortcuts. Inactive on touch-first devices.

### Search results

- **Arrow Up / Arrow Down**: Navigate through search results
- **Enter**: Open highlighted result
- **Escape**: Close search results
- **Tab**: Move focus into the matching mode bar (keeps results open)

### Matching modes

While the search field or results are focused:

- **Alt+1, Alt+2, …**: Select the matching mode in that position (the number is shown on each button)
- **Arrow Left / Arrow Right**: Move between matching modes when a mode button is focused
- **Home / End**: Jump to first / last mode
- **Enter / Space**: Activate the focused mode
- **Escape**: Return focus to the search input

## Matching Modes

A pill-button radiogroup appears inside the results dropdown, letting visitors switch modes without reloading the page. The reliable keyboard path is **Alt+1, Alt+2, …** while the search field is focused (numbers are shown on the buttons). Clicking and the radio-group arrow keys still work as a fallback.

| Mode | Behaviour |
|---|---|
| All words | Every typed word must appear (standard WordPress behaviour) |
| Any word | Posts matching any typed word (broader results) |
| Exact phrase | The full query treated as one phrase |
| Whole word | Only whole-word matches, no partials |
| Fuzzy | Tolerates one-character typos via edit-distance-1 `LIKE` patterns |

## Wordpress Directory

[https://wordpress.org/plugins/ninoxa-live-search/](https://wordpress.org/plugins/ninoxa-live-search/)
