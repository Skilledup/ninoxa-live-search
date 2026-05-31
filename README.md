# Ninoxa Live Search

AJAX-powered, instant search results for your WordPress search forms. Works out of the box.

## Features

- Real-time results (AJAX)
- Multilingual: Polylang, WPML, or fallback to default
- Accessible: ARIA + keyboard navigation
- Configurable keyboard shortcut with a dedicated settings screen
- Frontend matching modes: All words, Any word, Exact phrase, Whole word, Fuzzy — switchable by the visitor directly from the search field
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

### Search results

- **Arrow Up / Arrow Down**: Navigate through search results
- **Enter**: Open highlighted result
- **Escape**: Close search results
- **Tab**: Move focus into the matching mode bar (keeps results open)

### Matching mode bar

- **Arrow Left / Arrow Right**: Move between matching modes
- **Home / End**: Jump to first / last mode
- **Enter / Space**: Activate the focused mode
- **Escape**: Return focus to the search input

## Matching Modes

A pill-button radiogroup appears inside the results dropdown, letting visitors switch modes without reloading the page. Controls are fully keyboard-accessible (arrow keys to move, Enter/Space to select, Escape to return to the input).

| Mode | Behaviour |
|---|---|
| All words | Every typed word must appear (standard WordPress behaviour) |
| Any word | Posts matching any typed word (broader results) |
| Exact phrase | The full query treated as one phrase |
| Whole word | Only whole-word matches, no partials |
| Fuzzy | Tolerates one-character typos via edit-distance-1 `LIKE` patterns |

## Wordpress Directory

[https://wordpress.org/plugins/ninoxa-live-search/](https://wordpress.org/plugins/ninoxa-live-search/)
