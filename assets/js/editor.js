/**
 * Scoped CSS Editor - CodeMirror initialization
 * 
 * @package Scoped_CSS
 * @version 1.0.0
 */
(function($) {
    'use strict';

    // Global namespace for editor instance
    window.ScopedCSSEditor = {
        instance: null,
        $textarea: null,

        /**
         * Initialize CodeMirror editor
         */
        init: function() {
            var self = this;
            self.$textarea = $('#scoped-css-editor');
            
            if (!self.$textarea.length) {
                return;
            }

            // Check if CodeMirror settings are available
            if (typeof scopedCssEditor === 'undefined' || !scopedCssEditor.settings) {
                return;
            }

            // Initialize CodeMirror with WordPress settings
            var settings = $.extend(true, {}, scopedCssEditor.settings, {
                codemirror: {
                    styleActiveLine: true,
                    matchBrackets: true,
                    autoCloseBrackets: true
                }
            });

            // Initialize and store the instance
            var editor = wp.codeEditor.initialize(self.$textarea, settings);
            
            // WordPress returns { codemirror: CodeMirror instance }
            if (editor && editor.codemirror) {
                self.instance = editor.codemirror;
            }
        },

        /**
         * Get CSS content from editor
         * @return {string}
         */
        getValue: function() {
            // Method 1: Use stored instance
            if (this.instance) {
                return this.instance.getValue();
            }
            
            // Method 2: Try to get from textarea's CodeMirror property
            if (this.$textarea && this.$textarea.length) {
                var textarea = this.$textarea[0];
                if (textarea.CodeMirror) {
                    return textarea.CodeMirror.getValue();
                }
                // Method 3: Check next sibling (CodeMirror wraps textarea)
                var wrapper = this.$textarea.next('.CodeMirror');
                if (wrapper.length && wrapper[0].CodeMirror) {
                    return wrapper[0].CodeMirror.getValue();
                }
            }
            
            // Fallback to textarea value
            return this.$textarea ? this.$textarea.val() : '';
        },

        /**
         * Set CSS content in editor
         * @param {string} content
         */
        setValue: function(content) {
            // Method 1: Use stored instance
            if (this.instance) {
                this.instance.setValue(content);
                return;
            }
            
            // Method 2: Try textarea's CodeMirror property
            if (this.$textarea && this.$textarea.length) {
                var textarea = this.$textarea[0];
                if (textarea.CodeMirror) {
                    textarea.CodeMirror.setValue(content);
                    return;
                }
                // Method 3: Check next sibling
                var wrapper = this.$textarea.next('.CodeMirror');
                if (wrapper.length && wrapper[0].CodeMirror) {
                    wrapper[0].CodeMirror.setValue(content);
                    return;
                }
            }
            
            // Fallback to textarea
            if (this.$textarea) {
                this.$textarea.val(content);
            }
        }
    };

    $(document).ready(function() {
        window.ScopedCSSEditor.init();
    });

})(jQuery);
