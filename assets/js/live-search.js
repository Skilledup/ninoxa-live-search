jQuery(document).ready(function ($) {
    let searchTimer;
    let activeSearchInput = null;
    let selectedResultIndex = -1;
    const MAX_RETRY_ATTEMPTS = 2;
    const shortcutDefinition = parseShortcut(
        liveSearchData.settings && liveSearchData.settings.keyboardShortcut ?
            liveSearchData.settings.keyboardShortcut :
            ''
    );
    const shortcutLabel = liveSearchData.settings && liveSearchData.settings.keyboardShortcutLabel ?
        liveSearchData.settings.keyboardShortcutLabel :
        '';

    // Loading indicator settings
    const loadingSettings = liveSearchData.settings || {};
    const spinnerEnabled  = loadingSettings.loadingSpinnerEnabled  !== false;
    const spinnerPosition = loadingSettings.loadingSpinnerPosition || 'right';
    const sweepEnabled    = loadingSettings.loadingSweepEnabled    !== false;

    // Result matching settings (native WordPress search modes).
    const matchingSettings = loadingSettings.matching || {};
    const matchingModes    = matchingSettings.modes || {};
    const matchingEnabled  = matchingSettings.enabled === true && Object.keys(matchingModes).length > 1;
    let currentMatchMode   = matchingSettings.defaultMode && matchingModes[matchingSettings.defaultMode] ?
        matchingSettings.defaultMode :
        (Object.keys(matchingModes)[0] || 'keyword');

    // Generate unique IDs for ARIA relationships
    function generateUniqueId(prefix) {
        return prefix + '-' + Math.random().toString(36).substr(2, 9);
    }

    // Escape a string for safe insertion into HTML attributes/text.
    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Build the matching-mode control bar markup.
    //
    // Implemented as an ARIA radiogroup: exactly one mode is selected at a
    // time, so each control is a role="radio" with aria-checked. A roving
    // tabindex (only the active radio is in the tab order) plus arrow-key
    // navigation provides full keyboard support per the WAI-ARIA radio pattern.
    function buildMatchingBar() {
        if (!matchingEnabled) {
            return '';
        }

        const label = (liveSearchData.i18n && liveSearchData.i18n.matching_label) || 'Matching';
        let html = '<div class="ninoxa-live-search-modes" role="radiogroup" aria-label="' + escapeHtml(label) + '">';

        Object.keys(matchingModes).forEach(function (mode) {
            const isActive = mode === currentMatchMode;
            html += '<button type="button" class="ninoxa-live-search-mode' + (isActive ? ' is-active' : '') +
                '" data-mode="' + escapeHtml(mode) + '" role="radio"' +
                ' aria-checked="' + (isActive ? 'true' : 'false') + '"' +
                ' tabindex="' + (isActive ? '0' : '-1') + '">' +
                escapeHtml(matchingModes[mode]) + '</button>';
        });

        html += '</div>';

        return html;
    }

    // Prepend the matching-mode bar to a results container.
    function decorateResults($results) {
        if (!matchingEnabled) {
            return;
        }

        // If focus is currently inside the (about to be removed) control bar,
        // restore it to the active radio after the bar is rebuilt so keyboard
        // navigation is not interrupted by the results re-render.
        const focusWasInGroup = $results.find('.ninoxa-live-search-mode').is(document.activeElement);

        $results.find('.ninoxa-live-search-modes').remove();
        $results.prepend(buildMatchingBar());

        if (focusWasInGroup) {
            $results.find('.ninoxa-live-search-mode[aria-checked="true"]').trigger('focus');
        }
    }

    // Reflect the current mode on every rendered control bar, keeping the
    // roving tabindex and aria-checked state in sync with currentMatchMode.
    function syncMatchingBars() {
        $('.ninoxa-live-search-mode').each(function () {
            const isActive = String($(this).data('mode')) === currentMatchMode;
            $(this)
                .toggleClass('is-active', isActive)
                .attr('aria-checked', isActive ? 'true' : 'false')
                .attr('tabindex', isActive ? '0' : '-1');
        });
    }

    // Apply a matching mode and re-run the active search.
    //
    // @param {string}  mode             Mode key to activate.
    // @param {boolean} returnFocusToInput Whether to move focus back to the
    //   search input (true for pointer activation) or keep it on the radio
    //   group (false for keyboard navigation within the group).
    function applyMatchMode(mode, returnFocusToInput) {
        if (!mode || !matchingModes[mode] || mode === currentMatchMode) {
            return;
        }

        currentMatchMode = mode;
        syncMatchingBars();

        if (activeSearchInput && activeSearchInput.length) {
            clearTimeout(searchTimer);
            executeLiveSearch(activeSearchInput);

            if (returnFocusToInput) {
                activeSearchInput.trigger('focus');
            }
        }
    }

    function normalizeShortcutKey(key) {
        const normalizedKey = String(key || '').toLowerCase();
        const aliases = {
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

        return aliases[normalizedKey] || normalizedKey;
    }

    function parseShortcut(shortcutValue) {
        const normalizedValue = String(shortcutValue || '').trim().toLowerCase();
        const modifierAliases = {
            control: 'ctrl',
            ctrl: 'ctrl',
            alt: 'alt',
            option: 'alt',
            shift: 'shift',
            cmd: 'meta',
            command: 'meta',
            meta: 'meta'
        };

        if (!normalizedValue) {
            return null;
        }

        return normalizedValue
            .split('+')
            .map(function (part) {
                return part.trim();
            })
            .filter(Boolean)
            .reduce(function (definition, part) {
                if (!definition) {
                    return null;
                }

                if (modifierAliases[part]) {
                    definition[modifierAliases[part]] = true;
                    return definition;
                }

                if (definition.key) {
                    return null;
                }

                definition.key = normalizeShortcutKey(part);

                return definition;
            }, {
                ctrl: false,
                alt: false,
                shift: false,
                meta: false,
                key: ''
            });
    }

    function shortcutMatchesEvent(event, definition) {
        if (!definition || !definition.key) {
            return false;
        }

        return Boolean(definition.ctrl) === Boolean(event.ctrlKey) &&
            Boolean(definition.alt) === Boolean(event.altKey) &&
            Boolean(definition.shift) === Boolean(event.shiftKey) &&
            Boolean(definition.meta) === Boolean(event.metaKey) &&
            normalizeShortcutKey(event.key) === definition.key;
    }

    function applyShortcutHint($wrapper) {
        if (!shortcutLabel) {
            $wrapper.removeAttr('data-ninoxa-shortcut');
            return;
        }

        $wrapper.attr('data-ninoxa-shortcut', shortcutLabel);
    }

    // Refresh nonce via AJAX (used when a search fails due to stale/cached nonce)
    function refreshNonce() {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: liveSearchData.ajaxurl,
                type: 'POST',
                data: {
                    action: liveSearchData.refresh_nonce_action
                },
                timeout: 5000,
                success: function(response) {
                    if (response.success && response.data.nonce) {
                        liveSearchData.nonce = response.data.nonce;
                        resolve(response.data.nonce);
                    } else {
                        reject(new Error(liveSearchData.i18n.nonce_refresh_failed));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(liveSearchData.i18n.nonce_refresh_failed + ': ' + error));
                }
            });
        });
    }

    // AJAX search with automatic nonce refresh on failure (handles cached pages)
    function performLiveSearch(searchQuery, $input, $results, $loadingIndicator, retryCount) {
        retryCount = retryCount || 0;
        
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: liveSearchData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'live_search',
                    s: searchQuery,
                    nonce: liveSearchData.nonce,
                    match_mode: currentMatchMode
                },
                timeout: 10000,
                success: function(response) {
                    // WordPress nonce errors return HTTP 200 with {success: false},
                    // so we must check for them here, not in the error callback
                    if (typeof response === 'object' && response.success === false) {
                        var isNonceError = response.data &&
                            (response.data.code === 'invalid_nonce' || response.data.code === 'missing_nonce');
                        
                        if (isNonceError && retryCount < MAX_RETRY_ATTEMPTS) {
                            // Transparently refresh nonce and retry the search
                            refreshNonce().then(function() {
                                return performLiveSearch(searchQuery, $input, $results, $loadingIndicator, retryCount + 1);
                            }).then(resolve).catch(function() {
                                reject(new Error(liveSearchData.i18n.search_failed));
                            });
                            return;
                        }
                        reject(new Error(liveSearchData.i18n.search_failed));
                        return;
                    }
                    resolve(response);
                },
                error: function(xhr, status, error) {
                    reject(new Error(liveSearchData.i18n.search_failed + ': ' + error));
                }
            });
        });
    }

    // Update ARIA attributes based on results state
    function updateARIAAttributes($input, $results, hasResults, isExpanded) {
        const resultsId = $results.attr('id');
        
        // Set basic attributes
        $input.attr('aria-expanded', isExpanded ? 'true' : 'false');
        
        // Only set aria-owns when we actually have results
        if (hasResults && isExpanded) {
            $input.attr('aria-owns', resultsId);
        } else {
            $input.removeAttr('aria-owns');
        }
        
        // Only set aria-activedescendant when we have a valid selection
        if (selectedResultIndex >= 0 && hasResults && isExpanded) {
            const selectedId = $results.find('[data-result-index="' + (selectedResultIndex + 1) + '"]').attr('id');
            if (selectedId) {
                $input.attr('aria-activedescendant', selectedId);
            } else {
                $input.removeAttr('aria-activedescendant');
            }
        } else {
            $input.removeAttr('aria-activedescendant');
        }
        
        // Set aria-live on results container when needed
        if (hasResults && isExpanded) {
            $results.attr('aria-live', 'polite');
        } else {
            $results.removeAttr('aria-live');
        }
    }

    // Navigate through search results with keyboard
    function navigateResults($input, $results, direction) {
        const $resultItems = $results.find('[role="option"]');
        const totalResults = $resultItems.length;
        
        if (totalResults === 0) return;

        // Remove previous selection
        $resultItems.removeClass('live-search-selected').attr('aria-selected', 'false');

        if (direction === 'down') {
            selectedResultIndex = selectedResultIndex >= totalResults - 1 ? 0 : selectedResultIndex + 1;
        } else if (direction === 'up') {
            selectedResultIndex = selectedResultIndex <= 0 ? totalResults - 1 : selectedResultIndex - 1;
        }

        // Apply selection to new item
        const $selectedItem = $resultItems.eq(selectedResultIndex);
        $selectedItem.addClass('live-search-selected').attr('aria-selected', 'true');

        // Update aria-activedescendant
        const selectedId = $selectedItem.attr('id') || 'result-' + (selectedResultIndex + 1);
        $selectedItem.attr('id', selectedId);
        $input.attr('aria-activedescendant', selectedId);

        // Scroll selected item into view
        if ($selectedItem.length) {
            const resultsContainer = $results[0];
            const selectedElement = $selectedItem[0];
            
            if (selectedElement.offsetTop < resultsContainer.scrollTop) {
                resultsContainer.scrollTop = selectedElement.offsetTop;
            } else if (selectedElement.offsetTop + selectedElement.offsetHeight > 
                      resultsContainer.scrollTop + resultsContainer.offsetHeight) {
                resultsContainer.scrollTop = selectedElement.offsetTop + selectedElement.offsetHeight - resultsContainer.offsetHeight;
            }
        }
    }

    // Activate selected result (navigate to link)
    function activateSelectedResult($results) {
        const $selectedItem = $results.find('.live-search-selected');
        if ($selectedItem.length) {
            const $link = $selectedItem.find('a');
            if ($link.length) {
                // Trigger click on the link
                window.location.href = $link.attr('href');
            }
        }
    }

    // Close results and reset state
    function closeResults($input, $results) {
        $results.hide().empty();
        selectedResultIndex = -1;
        updateARIAAttributes($input, $results, false, false);
        activeSearchInput = null;
    }

    // Run the AJAX search for an input and render the results. Shared by the
    // debounced input handler and the matching-mode switch so both stay in sync.
    function executeLiveSearch($input) {
        const $wrapper = $input.parent('.search-input-wrapper');
        const $form = $input.closest('form');
        const $results = $form.find('.live-search-results');
        const searchQuery = $input.val();

        if (searchQuery.length < 3) {
            closeResults($input, $results);
            $wrapper.find('.live-search-loading').hide();
            $wrapper.removeClass('ninoxa-live-search-sweeping');
            return;
        }

        if (spinnerEnabled && !$wrapper.find('.live-search-loading').length) {
            $wrapper.append('<div class="live-search-loading" aria-hidden="true"></div>');
        }

        const $loadingIndicator = $wrapper.find('.live-search-loading');

        if (spinnerEnabled) {
            $loadingIndicator.show();
        }
        if (sweepEnabled) {
            $wrapper.addClass('ninoxa-live-search-sweeping');
        }

        performLiveSearch(searchQuery, $input, $results, $loadingIndicator)
            .then(function (response) {
                $results.html(response);
                decorateResults($results);
                const hasResults = $results.find('[role="option"]').length > 0;

                if (hasResults) {
                    $results.show();
                    updateARIAAttributes($input, $results, true, true);

                    // Announce results to screen readers
                    const resultCount = $results.find('[role="option"]').length;
                    const announcement = resultCount === 1 ?
                        liveSearchData.i18n.one_suggestion :
                        liveSearchData.i18n.suggestions_available.replace('%d', resultCount);

                    // Create or update announcement element
                    let $announcement = $('#live-search-announcement');
                    if (!$announcement.length) {
                        $announcement = $('<div id="live-search-announcement" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>');
                        $('body').append($announcement);
                    }
                    $announcement.text(announcement);
                } else {
                    updateARIAAttributes($input, $results, false, false);
                    $results.show(); // Still show "no results" message
                }
            })
            .catch(function (error) {
                console.error('Live Search: Search failed:', error);
                $results.html('<div class="live-search-error" role="status" aria-live="polite">' + liveSearchData.i18n.search_unavailable + '</div>');
                $results.show();
            })
            .finally(function () {
                $loadingIndicator.hide();
                $wrapper.removeClass('ninoxa-live-search-sweeping');
            });
    }

    // Switch the active matching mode and re-run the current search.
    $(document).on('click', '.ninoxa-live-search-mode', function (e) {
        e.preventDefault();
        e.stopPropagation();

        applyMatchMode(String($(this).data('mode')), true);
    });

    // Keyboard support for the matching radiogroup (WAI-ARIA radio pattern):
    // arrow keys move focus to and select the adjacent radio (wrapping around),
    // Home/End jump to the first/last, and Space/Enter selects the focused one.
    $(document).on('keydown', '.ninoxa-live-search-mode', function (e) {
        const $group = $(this).closest('.ninoxa-live-search-modes');
        const $radios = $group.find('.ninoxa-live-search-mode');
        const total = $radios.length;

        if (total === 0) {
            return;
        }

        const currentIndex = $radios.index(this);
        let targetIndex = -1;

        switch (e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                targetIndex = (currentIndex + 1) % total;
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                targetIndex = (currentIndex - 1 + total) % total;
                break;
            case 'Home':
                targetIndex = 0;
                break;
            case 'End':
                targetIndex = total - 1;
                break;
            case ' ':
            case 'Spacebar':
            case 'Enter':
                e.preventDefault();
                e.stopPropagation();
                applyMatchMode(String($(this).data('mode')), false);
                return;
            case 'Escape':
                e.preventDefault();
                e.stopPropagation();
                if (activeSearchInput && activeSearchInput.length) {
                    activeSearchInput.trigger('focus');
                }
                return;
            default:
                return;
        }

        e.preventDefault();
        e.stopPropagation();

        const $target = $radios.eq(targetIndex);
        $target.trigger('focus');
        applyMatchMode(String($target.data('mode')), false);
    });

    // Select and process search forms
    $('form[role="search"], .search-form, form.search').each(function () {
        let $form = $(this);
        let $input = $form.find('input[type="search"], input[name="s"]');

        // Add results container if not exists
        if (!$form.find('.live-search-results').length) {
            $form.append('<div class="live-search-results"></div>');
        }

        const $results = $form.find('.live-search-results');
        const resultsId = generateUniqueId('live-search-results');
        const inputId = $input.attr('id') || generateUniqueId('live-search-input');
        
        // Set up ARIA attributes
        $input.attr('id', inputId);
        $results.attr({
            'id': resultsId,
            'role': 'listbox',
            'aria-label': liveSearchData.i18n.search_suggestions
        });

        // Prevent form submission for live search
        $form.on('submit', function (e) {
            // If a result is selected, activate it instead of submitting
            if (selectedResultIndex >= 0 && $results.is(':visible')) {
                e.preventDefault();
                activateSelectedResult($results);
                return;
            }
            // Hide live search results before submitting
            closeResults($input, $results);
        });

        // Process input
        $input.not('.search-input-processed')
            .addClass('search-input-processed ninoxa-live-search-input')
            .attr({
                'autocomplete': 'off',
                'role': 'combobox',
                'aria-autocomplete': 'list',
                'aria-expanded': 'false',
                'aria-haspopup': 'listbox'
            })
            .each(function () {
                if (!$(this).parent().hasClass('search-input-wrapper')) {
                    $(this).wrap('<div class="search-input-wrapper"></div>');
                }

                const $wrapper = $(this).parent('.search-input-wrapper');
                $wrapper.attr('data-ninoxa-spinner-position', spinnerPosition);
                applyShortcutHint($wrapper);
            })
            .on('input', function () {
                clearTimeout(searchTimer);
                const $input = $(this);
                const $wrapper = $input.parent('.search-input-wrapper');
                const $form = $input.closest('form');
                const $results = $form.find('.live-search-results');
                const searchQuery = $input.val();

                activeSearchInput = $input;
                selectedResultIndex = -1;

                // Add loading indicator if not exists (only when spinner is enabled)
                if (spinnerEnabled && !$wrapper.find('.live-search-loading').length) {
                    $wrapper.append('<div class="live-search-loading" aria-hidden="true"></div>');
                }

                if (searchQuery.length < 3) {
                    closeResults($input, $results);
                    $wrapper.find('.live-search-loading').hide();
                    $wrapper.removeClass('ninoxa-live-search-sweeping');
                    return;
                }

                $results.hide();

                searchTimer = setTimeout(function () {
                    executeLiveSearch($input);
                }, 500);
            })
            .on('keydown', function (e) {
                const $input = $(this);
                const $form = $input.closest('form');
                const $results = $form.find('.live-search-results');

                if (!$results.is(':visible') || $results.find('[role="option"]').length === 0) {
                    return;
                }

                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        navigateResults($input, $results, 'down');
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        navigateResults($input, $results, 'up');
                        break;
                    case 'Enter':
                        if (selectedResultIndex >= 0) {
                            e.preventDefault();
                            activateSelectedResult($results);
                        }
                        break;
                    case 'Escape':
                        e.preventDefault();
                        closeResults($input, $results);
                        break;
                    case 'Tab':
                        // Close results when tabbing away — but keep them open
                        // when focus moves into the results region (e.g. the
                        // matching radiogroup controls).
                        setTimeout(function () {
                            if (!$input.is(':focus') && !$.contains($results[0], document.activeElement)) {
                                closeResults($input, $results);
                            }
                        }, 0);
                        break;
                }
            })
            .on('focus', function () {
                activeSearchInput = $(this);
            });
    });

    // Handle clicks on search results
    $(document).on('click', '.live-search-result, .live-search-more-results', function (e) {
        e.preventDefault();
        const $link = $(this).find('a');
        if ($link.length) {
            window.location.href = $link.attr('href');
        }
    });

    // Close results when clicking outside
    $(document).on('click', function (event) {
        if (!$(event.target).closest('form').length && !$(event.target).closest('.live-search-results').length) {
            $('.live-search-results').hide();
            selectedResultIndex = -1;
            activeSearchInput = null;
        }
    });
    
    // Global keyboard shortcut: configurable from plugin settings.
    $(document).on('keydown', function(e) {
        if (!shortcutMatchesEvent(e, shortcutDefinition)) {
            return;
        }

        e.preventDefault();

        // Find the first visible Ninoxa Live Search input (only inputs processed by this plugin)
        const $searchInput = $('.ninoxa-live-search-input').filter(function() {
            const $input = $(this);

            return $input.is(':visible') && !$input.prop('disabled') && !$input.prop('readonly');
        }).first();

        if ($searchInput.length > 0) {
            $searchInput.focus();

            $searchInput[0].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            $searchInput.select();
        }
    });
    
    // Clean up timers when page unloads
    $(window).on('beforeunload', function() {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
    });
});