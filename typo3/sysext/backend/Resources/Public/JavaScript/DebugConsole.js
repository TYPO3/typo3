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
define(["require", "exports", "jquery"], function (require, exports, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/DebugConsole
     * The debug console shown at the bottom of the backend
     * @exports TYPO3/CMS/Backend/DebugConsole
     */
    var DebugConsole = (function () {
        function DebugConsole() {
            var _this = this;
            this.settings = {
                autoscroll: true,
            };
            $(function () {
                _this.createDom();
            });
        }
        /**
         * Increment the counter of unread messages in the given tab
         *
         * @param {JQuery} $tab
         */
        DebugConsole.incrementInactiveTabCounter = function ($tab) {
            if (!$tab.hasClass('active')) {
                var $badge = $tab.find('.badge');
                var value = parseInt($badge.text(), 10);
                if (isNaN(value)) {
                    value = 0;
                }
                $badge.text(++value);
            }
        };
        /**
         * Add the debug message to the console
         *
         * @param {String} message
         * @param {String} header
         * @param {String} [group=Debug]
         */
        DebugConsole.prototype.add = function (message, header, group) {
            this.attachToViewport();
            var $line = $('<p />').html(message);
            if (typeof header !== 'undefined' && header.length > 0) {
                $line.prepend($('<strong />').text(header));
            }
            if (typeof group === 'undefined' || group.length === 0) {
                group = 'Debug';
            }
            var tabIdentifier = 'debugtab-' + group.toLowerCase().replace(/\W+/g, '-');
            var $debugTabs = this.$consoleDom.find('.t3js-debuggroups');
            var $tabContent = this.$consoleDom.find('.t3js-debugcontent');
            var $tab = this.$consoleDom.find('.t3js-debuggroups li[data-identifier=' + tabIdentifier + ']');
            // check if group tab exists
            if ($tab.length === 0) {
                // create new tab
                $tab =
                    $('<li />', { role: 'presentation', 'data-identifier': tabIdentifier }).append($('<a />', {
                        'aria-controls': tabIdentifier,
                        'data-toggle': 'tab',
                        href: '#' + tabIdentifier,
                        role: 'tab'
                    }).text(group + ' ').append($('<span />', { 'class': 'badge' }))).on('shown.bs.tab', function (e) {
                        $(e.currentTarget).find('.badge').text('');
                    });
                $debugTabs.append($tab);
                $tabContent.append($('<div />', { role: 'tabpanel', 'class': 'tab-pane', id: tabIdentifier }).append($('<div />', { 'class': 't3js-messages messages' })));
            }
            // activate the first tab if no one is active
            if ($debugTabs.find('.active').length === 0) {
                $debugTabs.find('a:first').tab('show');
            }
            DebugConsole.incrementInactiveTabCounter($tab);
            this.incrementUnreadMessagesIfCollapsed();
            var $messageBox = $('#' + tabIdentifier + ' .t3js-messages');
            var isMessageBoxActive = $messageBox.parent().hasClass('active');
            $messageBox.append($line);
            if (this.settings.autoscroll && isMessageBoxActive) {
                $messageBox.scrollTop($messageBox.prop('scrollHeight'));
            }
        };
        DebugConsole.prototype.createDom = function () {
            var _this = this;
            if (typeof this.$consoleDom !== 'undefined') {
                return;
            }
            this.$consoleDom =
                $('<div />', { id: 'typo3-debug-console' }).append($('<div />', { 'class': 't3js-topbar topbar' }).append($('<p />', { 'class': 'pull-left' }).text(' TYPO3 Debug Console').prepend($('<span />', { 'class': 'fa fa-terminal topbar-icon' })).append($('<span />', { 'class': 'badge' })), $('<div />', { 'class': 't3js-buttons btn-group pull-right' })), $('<div />').append($('<div />', { role: 'tabpanel' }).append($('<ul />', { 'class': 'nav nav-tabs t3js-debuggroups', role: 'tablist' })), $('<div />', { 'class': 'tab-content t3js-debugcontent' })));
            this.addButton($('<button />', {
                'class': 'btn btn-default btn-sm ' + (this.settings.autoscroll ? 'active' : ''),
                title: TYPO3.lang['debuggerconsole.autoscroll']
            }).append($('<span />', { 'class': 't3-icon fa fa-magnet' })), function () {
                $(_this).button('toggle');
                _this.settings.autoscroll = !_this.settings.autoscroll;
            }).addButton($('<button />', {
                'class': 'btn btn-default btn-sm',
                title: TYPO3.lang['debuggerconsole.toggle.collapse']
            }).append($('<span />', { 'class': 't3-icon fa fa-chevron-down' })), function (e) {
                var $button = $(e.currentTarget);
                var $icon = $button.find('.t3-icon');
                var $innerContainer = _this.$consoleDom.find('.t3js-topbar').next();
                $innerContainer.toggle();
                if ($innerContainer.is(':visible')) {
                    $button.attr('title', TYPO3.lang['debuggerconsole.toggle.collapse']);
                    $icon.toggleClass('fa-chevron-down', true).toggleClass('fa-chevron-up', false);
                    _this.resetGlobalUnreadCounter();
                }
                else {
                    $button.attr('title', TYPO3.lang['debuggerconsole.toggle.expand']);
                    $icon.toggleClass('fa-chevron-down', false).toggleClass('fa-chevron-up', true);
                }
            }).addButton($('<button />', {
                'class': 'btn btn-default btn-sm',
                title: TYPO3.lang['debuggerconsole.clear']
            }).append($('<span />', { class: 't3-icon fa fa-undo' })), function () {
                _this.flush();
            }).addButton($('<button />', {
                'class': 'btn btn-default btn-sm',
                title: TYPO3.lang['debuggerconsole.close']
            }).append($('<span />', { 'class': 't3-icon fa fa-times' })), function () {
                _this.destroy();
                _this.createDom();
            });
        };
        /**
         * Adds a button and it's callback to the console's toolbar
         *
         * @param {JQuery} $button
         * @param callback
         * @returns {DebugConsole}
         */
        DebugConsole.prototype.addButton = function ($button, callback) {
            $button.on('click', callback);
            this.$consoleDom.find('.t3js-buttons').append($button);
            return this;
        };
        /**
         * Attach the Debugger Console to the viewport
         */
        DebugConsole.prototype.attachToViewport = function () {
            var $viewport = $('.t3js-scaffold-content');
            if ($viewport.has(this.$consoleDom).length === 0) {
                $viewport.append(this.$consoleDom);
            }
        };
        /**
         * Increment the counter of unread messages in the tabbar
         */
        DebugConsole.prototype.incrementUnreadMessagesIfCollapsed = function () {
            var $topbar = this.$consoleDom.find('.t3js-topbar');
            var $innerContainer = $topbar.next();
            if ($innerContainer.is(':hidden')) {
                var $badge = $topbar.find('.badge');
                var value = parseInt($badge.text(), 10);
                if (isNaN(value)) {
                    value = 0;
                }
                $badge.text(++value);
            }
        };
        /**
         * Reset global unread counter
         */
        DebugConsole.prototype.resetGlobalUnreadCounter = function () {
            this.$consoleDom.find('.t3js-topbar').find('.badge').text('');
        };
        /**
         * Reset the console
         */
        DebugConsole.prototype.flush = function () {
            var $debugTabs = this.$consoleDom.find('.t3js-debuggroups');
            var $tabContent = this.$consoleDom.find('.t3js-debugcontent');
            $debugTabs.children().remove();
            $tabContent.children().remove();
        };
        /**
         * Destroy everything of the console
         */
        DebugConsole.prototype.destroy = function () {
            this.$consoleDom.remove();
            this.$consoleDom = undefined;
        };
        return DebugConsole;
    }());
    var debugConsole = new DebugConsole();
    // expose as global object
    TYPO3.DebugConsole = debugConsole;
    return debugConsole;
});
