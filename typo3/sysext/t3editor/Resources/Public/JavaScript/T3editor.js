/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
define(["require", "exports", "cm/lib/codemirror", "jquery"], function (require, exports, CodeMirror, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/T3editor/T3editor
     * Renders CodeMirror into FormEngine
     * @exports TYPO3/CMS/T3editor/T3editor
     */
    var T3editor = (function () {
        /**
         * The constructor, set the class properties default values
         */
        function T3editor() {
            this.initialize();
        }
        /**
         * @param {string} position
         * @param {string} label
         * @returns {HTMLElement}
         */
        T3editor.createPanelNode = function (position, label) {
            var $panelNode = $('<div />', {
                class: 'CodeMirror-panel CodeMirror-panel-' + position,
                id: 'panel-' + position,
            }).append($('<span />').text(label));
            return $panelNode.get(0);
        };
        /**
         * Initializes CodeMirror on available texteditors
         */
        T3editor.prototype.findAndInitializeEditors = function () {
            $(document).find('textarea.t3editor').each(function () {
                var $textarea = $(this);
                if (!$textarea.prop('is_t3editor')) {
                    var config = $textarea.data('codemirror-config');
                    var modeParts_1 = config.mode.split('/');
                    var addons = $.merge([modeParts_1.join('/')], JSON.parse(config.addons));
                    var options_1 = JSON.parse(config.options);
                    // load mode + registered addons
                    require(addons, function () {
                        var cm = CodeMirror.fromTextArea($textarea.get(0), {
                            extraKeys: {
                                'Ctrl-Alt-F': function (codemirror) {
                                    codemirror.setOption('fullScreen', !codemirror.getOption('fullScreen'));
                                },
                                'Ctrl-Space': 'autocomplete',
                                'Esc': function (codemirror) {
                                    if (codemirror.getOption('fullScreen')) {
                                        codemirror.setOption('fullScreen', false);
                                    }
                                },
                            },
                            fullScreen: false,
                            lineNumbers: true,
                            lineWrapping: true,
                            mode: modeParts_1[modeParts_1.length - 1],
                        });
                        // set options
                        $.each(options_1, function (key, value) {
                            cm.setOption(key, value);
                        });
                        cm.addPanel(T3editor.createPanelNode('bottom', $textarea.attr('alt')), {
                            position: 'bottom',
                            stable: true,
                        });
                    });
                    $textarea.prop('is_t3editor', true);
                }
            });
        };
        /**
         * Initialize the events
         */
        T3editor.prototype.initialize = function () {
            var _this = this;
            $(function () {
                _this.findAndInitializeEditors();
            });
        };
        return T3editor;
    }());
    return new T3editor();
});
