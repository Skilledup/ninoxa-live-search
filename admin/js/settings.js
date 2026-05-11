jQuery(function ($) {
	const $input = $('#ninoxa-live-search-keyboard-shortcut');
	const $preview = $('[data-ninoxa-shortcut-preview]');
	const $status = $('[data-ninoxa-shortcut-status]');
	const $clear = $('[data-ninoxa-shortcut-clear]');
	const modifierKeys = ['control', 'shift', 'alt', 'meta'];

	// Maps event.code to the canonical key character for symbol keys.
	// This prevents Shift from mutating the captured key (e.g. Shift+/ would
	// otherwise produce "?" from event.key instead of the intended "/").
	const codeToKey = {
		Slash: '/',
		Period: '.',
		Comma: ',',
		Semicolon: ';',
		Quote: "'",
		BracketLeft: '[',
		BracketRight: ']',
		Backquote: '`',
		Minus: '-',
		Equal: '='
	};

	const keyAliases = {
		esc: 'escape',
		return: 'enter',
		spacebar: 'space',
		' ': 'space',
		slash: '/',
		up: 'arrowup',
		down: 'arrowdown',
		left: 'arrowleft',
		right: 'arrowright'
	};
	const keyLabels = {
		escape: 'Escape',
		enter: 'Enter',
		space: 'Space',
		backspace: 'Backspace',
		tab: 'Tab',
		delete: 'Delete',
		insert: 'Insert',
		home: 'Home',
		end: 'End',
		pageup: 'Page Up',
		pagedown: 'Page Down',
		arrowup: 'Arrow Up',
		arrowdown: 'Arrow Down',
		arrowleft: 'Arrow Left',
		arrowright: 'Arrow Right'
	};

	if (!$input.length || !$preview.length || !$status.length || !$clear.length) {
		return;
	}

	/**
	 * Resolve the physical key character from a keyboard event.
	 * Uses event.code for symbol keys so that Shift does not change the
	 * character (e.g. Shift+/ stays "/", not "?").
	 */
	function getCanonicalKey(event) {
		if (codeToKey[event.code] !== undefined) {
			return codeToKey[event.code];
		}

		if (/^Key[A-Z]$/.test(event.code)) {
			return event.code.slice(3).toLowerCase();
		}

		if (/^Digit[0-9]$/.test(event.code)) {
			return event.code.slice(5);
		}

		return event.key.toLowerCase();
	}

	function normalizeKey(key) {
		const normalizedKey = String(key || '').trim().toLowerCase();

		return keyAliases[normalizedKey] || normalizedKey;
	}

	function formatKeyLabel(key) {
		const normalizedKey = normalizeKey(key);

		if (keyLabels[normalizedKey]) {
			return keyLabels[normalizedKey];
		}

		if (1 === normalizedKey.length) {
			return normalizedKey.toUpperCase();
		}

		return normalizedKey.replace(/[-_]/g, ' ').replace(/\b\w/g, function (character) {
			return character.toUpperCase();
		});
	}

	function setStatus(message, state) {
		$status.text(message);
		$status.attr('data-state', state || 'idle');
	}

	function setShortcutValue(value) {
		const normalizedValue = String(value || '').trim();

		if ('' === normalizedValue) {
			$input.val('');
			$preview.text(ninoxaLiveSearchSettings.disabledLabel);
			$preview.attr('data-state', 'disabled');
			return;
		}

		$input.val(normalizedValue);
		$preview.text(normalizedValue);
		$preview.attr('data-state', 'active');
	}

	function buildShortcut(event) {
		const canonicalKey = getCanonicalKey(event);
		const normalizedKey = normalizeKey(canonicalKey);

		if (!normalizedKey || modifierKeys.indexOf(normalizedKey) !== -1) {
			return '';
		}

		const parts = [];

		if (event.ctrlKey) {
			parts.push('Ctrl');
		}

		if (event.altKey) {
			parts.push('Alt');
		}

		if (event.shiftKey) {
			parts.push('Shift');
		}

		if (event.metaKey) {
			parts.push('Cmd');
		}

		parts.push(formatKeyLabel(normalizedKey));

		return parts.join(' + ');
	}

	function clearShortcut() {
		setShortcutValue('');
		setStatus(ninoxaLiveSearchSettings.captureCleared, 'idle');
	}

	// Tracks non-modifier keys currently held so we can detect invalid combos
	// like Enter+S where neither key is a recognised modifier.
	const pressedNonModifiers = new Set();

	$input.on('focus click', function () {
		setStatus(ninoxaLiveSearchSettings.captureReady, 'listening');
		$input.trigger('select');
	});

	$input.on('blur', function () {
		pressedNonModifiers.clear();
		setStatus(ninoxaLiveSearchSettings.capturePrompt, 'idle');
	});

	$input.on('keyup', function (event) {
		const normalizedKey = normalizeKey(getCanonicalKey(event));

		if (modifierKeys.indexOf(normalizedKey) === -1) {
			pressedNonModifiers.delete(event.code || normalizedKey);
		}
	});

	$input.on('keydown', function (event) {
		const canonicalKey = getCanonicalKey(event);
		const normalizedKey = normalizeKey(canonicalKey);
		const isClearKey = ('backspace' === normalizedKey || 'delete' === normalizedKey) && !event.ctrlKey && !event.altKey && !event.shiftKey && !event.metaKey;
		const isModifier = modifierKeys.indexOf(normalizedKey) !== -1;

		if (isClearKey) {
			event.preventDefault();
			pressedNonModifiers.clear();
			clearShortcut();
			return;
		}

		if (isModifier) {
			event.preventDefault();
			setStatus(ninoxaLiveSearchSettings.captureNeedKey, 'listening');
			return;
		}

		// Reject invalid combos where a non-modifier key is already held
		// (e.g. user holds Enter then presses S — Enter is not a modifier).
		if (pressedNonModifiers.size > 0) {
			event.preventDefault();
			setStatus(ninoxaLiveSearchSettings.captureInvalidCombo, 'error');
			return;
		}

		const shortcut = buildShortcut(event);

		if ('' === shortcut) {
			return;
		}

		event.preventDefault();
		pressedNonModifiers.add(event.code || canonicalKey);
		setShortcutValue(shortcut);
		setStatus(ninoxaLiveSearchSettings.captureSaved, 'listening');
	});

	$clear.on('click', function () {
		clearShortcut();
		$input.trigger('focus');
	});

	setShortcutValue($input.val());
	setStatus(ninoxaLiveSearchSettings.capturePrompt, 'idle');
});