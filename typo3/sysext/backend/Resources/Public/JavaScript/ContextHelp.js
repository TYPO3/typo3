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
define(["require", "exports", "jquery", "./Popover", "bootstrap"], function (require, exports, $, Popover) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/ContextHelp
     * API for context help.
     * @exports TYPO3/CMS/Backend/ContextHelp
     */
    var ContextHelp = (function () {
        function ContextHelp() {
            this.ajaxUrl = TYPO3.settings.ajaxUrls.context_help;
            this.trigger = 'click';
            this.placement = 'auto';
            this.selector = '.t3-help-link';
            this.initialize();
        }
        /**
         * @return {Window}
         */
        ContextHelp.resolveBackend = function () {
            if (typeof window.opener !== 'undefined' && window.opener !== null) {
                return window.opener.top;
            }
            else {
                return top;
            }
        };
        ContextHelp.prototype.initialize = function () {
            var _this = this;
            var backendWindow = ContextHelp.resolveBackend();
            if (typeof backendWindow.TYPO3.settings.ContextHelp !== 'undefined') {
                this.helpModuleUrl = backendWindow.TYPO3.settings.ContextHelp.moduleUrl;
            }
            if (typeof TYPO3.ShortcutMenu === 'undefined' && typeof backendWindow.TYPO3.ShortcutMenu === 'undefined') {
                // @FIXME: if we are in the popup... remove the bookmark / shortcut button
                // @TODO: make it possible to use the bookmark button also in popup mode
                $('.icon-actions-system-shortcut-new').closest('.btn').hide();
            }
            var title = '&nbsp;';
            if (typeof backendWindow.TYPO3.lang !== 'undefined') {
                title = backendWindow.TYPO3.lang.csh_tooltip_loading;
            }
            var $element = $(this.selector);
            $element
                .attr('data-loaded', 'false')
                .attr('data-html', 'true')
                .attr('data-original-title', title)
                .attr('data-placement', this.placement)
                .attr('data-trigger', this.trigger);
            Popover.popover($element);
            $(document).on('show.bs.popover', this.selector, function (e) {
                var $me = $(e.currentTarget);
                var description = $me.data('description');
                if (typeof description !== 'undefined' && description !== '') {
                    Popover.setOptions($me, {
                        title: $me.data('title'),
                        content: description
                    });
                }
                else if ($me.attr('data-loaded') === 'false' && $me.data('table')) {
                    _this.loadHelp($me);
                }
                // if help icon is in DocHeader, force open to bottom
                if ($me.closest('.t3js-module-docheader').length) {
                    Popover.setOption($me, 'placement', 'bottom');
                }
            }).on('shown.bs.popover', this.selector, function (e) {
                var $popover = $(e.target).data('bs.popover').$tip;
                if (!$popover.find('.popover-title').is(':visible')) {
                    $popover.addClass('no-title');
                }
            }).on('click', '.tipIsLinked', function (e) {
                $('.popover').each(function (index, popover) {
                    var $popover = $(popover);
                    if ($popover.has(e.target).length) {
                        console.log($popover.data('bs.popover'));
                        _this.showHelpPopup($popover.data('bs.popover').$element);
                    }
                });
            }).on('click', 'body', function (e) {
                $(_this.selector).each(function (index, triggerElement) {
                    var $triggerElement = $(triggerElement);
                    // the 'is' for buttons that trigger popups
                    // the 'has' for icons within a button that triggers a popup
                    if (!$triggerElement.is(e.target)
                        && $triggerElement.has(e.target).length === 0
                        && $('.popover').has(e.target).length === 0) {
                        Popover.hide($triggerElement);
                    }
                });
            });
        };
        /**
         * Open the help popup
         *
         * @param {JQuery} $trigger
         */
        ContextHelp.prototype.showHelpPopup = function ($trigger) {
            try {
                var cshWindow = window.open(this.helpModuleUrl +
                    '&tx_documentation_help_documentationcshmanual[table]=' + $trigger.data('table') +
                    '&tx_documentation_help_documentationcshmanual[field]=' + $trigger.data('field'), 'ContextHelpWindow', 'height=400,width=600,status=0,menubar=0,scrollbars=1');
                cshWindow.focus();
                Popover.hide($trigger);
                return cshWindow;
            }
            catch (e) {
                // do nothing
            }
        };
        /**
         * Load help data
         *
         * @param {JQuery} $trigger
         */
        ContextHelp.prototype.loadHelp = function ($trigger) {
            var table = $trigger.data('table');
            var field = $trigger.data('field');
            // If a table is defined, use ajax call to get the tooltip's content
            if (table) {
                // Load content
                $.getJSON(this.ajaxUrl, {
                    params: {
                        action: 'getContextHelp',
                        table: table,
                        field: field
                    }
                }).done(function (data) {
                    var title = data.title || '';
                    var content = data.content || '<p></p>';
                    Popover.setOptions($trigger, {
                        title: title,
                        content: content
                    });
                    $trigger
                        .attr('data-loaded', 'true')
                        .one('hidden.bs.popover', function () {
                        Popover.show($trigger);
                    });
                    Popover.hide($trigger);
                });
            }
        };
        return ContextHelp;
    }());
    return new ContextHelp();
});
