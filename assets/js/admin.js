/**
 * Scoped CSS Admin JavaScript
 * Handles condition builder UI and AJAX search
 */
(function($) {
    'use strict';

    var ScopedCSSAdmin = {
        /**
         * Rule index counter
         */
        ruleIndex: 0,

        /**
         * Search timeout for debouncing
         */
        searchTimeout: null,

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initExistingRules();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Minify button
            $(document).on('click', '#scoped-css-minify-btn', function(e) {
                e.preventDefault();
                self.minifyCss();
            });

            // Unminify button
            $(document).on('click', '#scoped-css-unminify-btn', function(e) {
                e.preventDefault();
                self.unminifyCss();
            });

            // Add new rule
            $(document).on('click', '#scoped-css-add-rule', function(e) {
                e.preventDefault();
                self.addRule();
            });

            // Remove rule
            $(document).on('click', '.scoped-css-remove-rule', function(e) {
                e.preventDefault();
                self.removeRule($(this).closest('.scoped-css-rule'));
            });

            // Rule type change
            $(document).on('change', '.scoped-css-rule-type', function() {
                self.updateRuleValueField($(this).closest('.scoped-css-rule'));
            });

            // Search input (debounced)
            $(document).on('input', '.scoped-css-search', function() {
                self.handleSearchInput($(this));
            });

            // Search result click
            $(document).on('click', '.scoped-css-search-result', function(e) {
                e.preventDefault();
                self.selectSearchResult($(this));
            });

            // Close search results on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.scoped-css-search-wrap').length) {
                    $('.scoped-css-search-results').hide();
                }
            });

            // Status toggle (AJAX)
            $(document).on('click', '.scoped-css-status-toggle', function(e) {
                // Allow default link behavior for now
                // Could be enhanced to use AJAX without page reload
            });
        },

        /**
         * Initialize existing rules (set correct index)
         */
        initExistingRules: function() {
            var self = this;
            $('#scoped-css-rules .scoped-css-rule').each(function() {
                var index = parseInt($(this).data('index'), 10);
                if (index >= self.ruleIndex) {
                    self.ruleIndex = index + 1;
                }
            });
        },

        /**
         * Add a new rule row
         */
        addRule: function() {
            var template = $('#scoped-css-rule-template').html();
            var newRule = template.replace(/\{\{INDEX\}\}/g, this.ruleIndex);
            
            $('#scoped-css-rules').append(newRule);
            this.updateRuleValueField($('#scoped-css-rules .scoped-css-rule').last());
            
            this.ruleIndex++;
        },

        /**
         * Remove a rule row
         * @param {jQuery} $rule Rule element
         */
        removeRule: function($rule) {
            // Don't remove if it's the last rule
            if ($('#scoped-css-rules .scoped-css-rule').length <= 1) {
                alert('At least one condition is required.');
                return;
            }
            $rule.remove();
        },

        /**
         * Update value field based on rule type
         * @param {jQuery} $rule Rule element
         */
        updateRuleValueField: function($rule) {
            var type = $rule.find('.scoped-css-rule-type').val();
            var $valueWrap = $rule.find('.scoped-css-rule-value');
            var $searchWrap = $rule.find('.scoped-css-search-wrap');
            var $postTypeSelect = $rule.find('.scoped-css-post-type-select');
            var $urlInput = $rule.find('.scoped-css-url-input');

            // Hide all value fields first
            $valueWrap.hide();
            $searchWrap.hide().find('input, select').prop('disabled', true);
            $postTypeSelect.hide().prop('disabled', true);
            $urlInput.hide().prop('disabled', true);

            // Show appropriate field based on type
            switch (type) {
                case 'page':
                case 'post':
                    $valueWrap.show();
                    $searchWrap.show().find('input').prop('disabled', false);
                    $searchWrap.find('.scoped-css-search').data('post-type', type);
                    break;

                case 'post_type':
                    $valueWrap.show();
                    $postTypeSelect.show().prop('disabled', false);
                    break;

                case 'url_exact':
                case 'url_contains':
                case 'url_starts':
                    $valueWrap.show();
                    $urlInput.show().prop('disabled', false);
                    break;

                // These types don't need a value
                case 'entire_site':
                case 'front_page':
                case 'blog_page':
                case '404':
                default:
                    // Value field stays hidden
                    break;
            }
        },

        /**
         * Handle search input with debouncing
         * @param {jQuery} $input Search input element
         */
        handleSearchInput: function($input) {
            var self = this;
            var search = $input.val();
            var $wrap = $input.closest('.scoped-css-search-wrap');
            var $results = $wrap.find('.scoped-css-search-results');
            var $valueInput = $wrap.find('.scoped-css-search-value');
            var postType = $input.data('post-type') || 'page';

            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // Clear value if input is empty
            if (search.length < 2) {
                $results.hide().empty();
                $valueInput.val('');
                return;
            }

            // Show loading
            $results.html('<div class="scoped-css-search-loading">' + 
                scopedCssAdmin.i18n.searching + '</div>').show();

            // Debounce the search
            this.searchTimeout = setTimeout(function() {
                self.performSearch(search, postType, $results, $valueInput);
            }, 300);
        },

        /**
         * Perform AJAX search
         */
        performSearch: function(search, postType, $results, $valueInput) {
            var self = this;
            $.ajax({
                url: scopedCssAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'scoped_css_search_posts',
                    nonce: scopedCssAdmin.searchNonce,
                    search: search,
                    post_type: postType
                },
                success: function(response) {
                    if (response.success && response.data.results.length > 0) {
                        var html = '';
                        $.each(response.data.results, function(i, result) {
                            html += '<div class="scoped-css-search-result" ' +
                                    'data-id="' + result.id + '" ' +
                                    'data-title="' + self.escapeHtml(result.title) + '">' +
                                    '<strong>' + self.escapeHtml(result.title) + '</strong>' +
                                    '<span class="type">' + result.type + ' #' + result.id + '</span>' +
                                    '</div>';
                        });
                        $results.html(html).show();
                    } else {
                        $results.html('<div class="scoped-css-no-results">' + 
                            scopedCssAdmin.i18n.noResults + '</div>').show();
                    }
                },
                error: function() {
                    $results.html('<div class="scoped-css-search-error">' + 
                        scopedCssAdmin.i18n.error + '</div>').show();
                }
            });
        },

        /**
         * Select a search result
         * @param {jQuery} $result Result element
         */
        selectSearchResult: function($result) {
            var id = $result.data('id');
            var title = $result.data('title');
            var $wrap = $result.closest('.scoped-css-search-wrap');

            $wrap.find('.scoped-css-search').val(title);
            $wrap.find('.scoped-css-search-value').val(id);
            $wrap.find('.scoped-css-search-results').hide();
        },

        /**
         * Escape HTML entities
         * @param {string} str String to escape
         * @return {string}
         */
        escapeHtml: function(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Minify CSS
         */
        minifyCss: function() {
            var self = this;
            var css = self.getCssContent();

            if (!css.trim()) {
                self.showMessage('CSS content is empty', 'error');
                return;
            }

            var $btn = $('#scoped-css-minify-btn');
            var nonce = $('input[name="scoped_css_minify_nonce"]').val();
            
            if (!nonce) {
                self.showMessage('Security token not found. Please refresh.', 'error');
                return;
            }

            $btn.prop('disabled', true);
            self.showMessage('Minifying...', 'loading');

            $.post(scopedCssAdmin.ajaxUrl, {
                action: 'scoped_css_minify',
                nonce: nonce,
                css: css
            }, function(response) {
                if (response.success) {
                    self.setCssContent(response.data.minified);
                    self.showMessage(response.data.message, 'success');
                } else {
                    self.showMessage(response.data.message || 'Minify failed', 'error');
                }
            }).fail(function(xhr) {
                self.showMessage('Request failed: ' + xhr.status, 'error');
            }).always(function() {
                $btn.prop('disabled', false);
            });
        },

        /**
         * Unminify CSS
         */
        unminifyCss: function() {
            var self = this;
            var css = self.getCssContent();

            if (!css.trim()) {
                self.showMessage('CSS content is empty', 'error');
                return;
            }

            var $btn = $('#scoped-css-unminify-btn');
            var nonce = $('input[name="scoped_css_minify_nonce"]').val();
            
            if (!nonce) {
                self.showMessage('Security token not found. Please refresh.', 'error');
                return;
            }
            
            $btn.prop('disabled', true);
            self.showMessage('Formatting...', 'loading');

            $.post(scopedCssAdmin.ajaxUrl, {
                action: 'scoped_css_unminify',
                nonce: nonce,
                css: css
            }, function(response) {
                if (response.success) {
                    self.setCssContent(response.data.unminified);
                    self.showMessage(response.data.message, 'success');
                } else {
                    self.showMessage(response.data.message || 'Format failed', 'error');
                }
            }).fail(function(xhr) {
                self.showMessage('Request failed: ' + xhr.status, 'error');
            }).always(function() {
                $btn.prop('disabled', false);
            });
        },

        /**
         * Show status message
         * @param {string} message
         * @param {string} type - 'success', 'error', or 'loading'
         */
        showMessage: function(message, type) {
            var $msg = $('#scoped-css-minify-message');
            var color = type === 'success' ? '#0a8619' : type === 'error' ? '#d32f2f' : '#666';
            var prefix = type === 'success' ? '✓ ' : type === 'error' ? '✗ ' : '';
            
            $msg.text(prefix + message).css('color', color).show();
            
            if (type === 'success') {
                setTimeout(function() {
                    $msg.fadeOut();
                }, 3000);
            }
        },

        /**
         * Get CSS content from editor
         * @return {string}
         */
        getCssContent: function() {
            // Method 1: Use global ScopedCSSEditor if available
            if (window.ScopedCSSEditor && window.ScopedCSSEditor.instance) {
                return window.ScopedCSSEditor.getValue();
            }
            
            // Method 2: Find CodeMirror instance directly
            var $textarea = $('#scoped-css-editor');
            if ($textarea.length) {
                // Check the .CodeMirror sibling (how WP wraps it)
                var $cmWrapper = $textarea.next('.CodeMirror');
                if ($cmWrapper.length && $cmWrapper[0].CodeMirror) {
                    return $cmWrapper[0].CodeMirror.getValue();
                }
                
                // Check textarea's own CodeMirror property
                if ($textarea[0].CodeMirror) {
                    return $textarea[0].CodeMirror.getValue();
                }
            }
            
            // Fallback: textarea value
            return $textarea.val() || '';
        },

        /**
         * Set CSS content in editor
         * @param {string} content
         */
        setCssContent: function(content) {
            // Method 1: Use global ScopedCSSEditor if available
            if (window.ScopedCSSEditor && window.ScopedCSSEditor.instance) {
                window.ScopedCSSEditor.setValue(content);
                return;
            }
            
            // Method 2: Find CodeMirror instance directly
            var $textarea = $('#scoped-css-editor');
            if ($textarea.length) {
                // Check the .CodeMirror sibling (how WP wraps it)
                var $cmWrapper = $textarea.next('.CodeMirror');
                if ($cmWrapper.length && $cmWrapper[0].CodeMirror) {
                    $cmWrapper[0].CodeMirror.setValue(content);
                    return;
                }
                
                // Check textarea's own CodeMirror property
                if ($textarea[0].CodeMirror) {
                    $textarea[0].CodeMirror.setValue(content);
                    return;
                }
            }
            
            // Fallback: textarea value
            $textarea.val(content);
        }
    };

    // Store reference for search callback
    var self = ScopedCSSAdmin;

    // Initialize when document is ready
    $(document).ready(function() {
        ScopedCSSAdmin.init();
    });

})(jQuery);
