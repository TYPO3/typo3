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

import $ from 'jquery';

/**
 * Module: @typo3/backend/debug-console
 * The debug console shown at the bottom of the backend
 * @exports @typo3/backend/debug-console
 */
class DebugConsole {
  private $consoleDom: any;
  private settings: any = {
    autoscroll: true,
  };

  /**
   * Increment the counter of unread messages in the given tab
   *
   * @param {JQuery} $tab
   */
  private static incrementInactiveTabCounter($tab: JQuery): void {
    if (!$tab.hasClass('active')) {
      const $badge = $tab.find('.badge');
      let value = parseInt($badge.text(), 10);

      if (isNaN(value)) {
        value = 0;
      }
      $badge.text(++value);
    }
  }

  constructor() {
    $((): void => {
      this.createDom();
    });
  }

  /**
   * Add the debug message to the console
   *
   * @param {String} message
   * @param {String} header
   * @param {String} [group=Debug]
   */
  public add(message: string, header: string, group: string): void {
    this.attachToViewport();

    const $line = $('<p />').html(message);
    if (typeof header !== 'undefined' && header.length > 0) {
      $line.prepend($('<strong />').text(header));
    }

    if (typeof group === 'undefined' || group.length === 0) {
      group = 'Debug';
    }

    const tabIdentifier = 'debugtab-' + group.toLowerCase().replace(/\W+/g, '-');
    const $debugTabs = this.$consoleDom.find('.t3js-debuggroups');
    const $tabContent = this.$consoleDom.find('.t3js-debugcontent');
    let $tab = this.$consoleDom.find('.t3js-debuggroups li[data-identifier=' + tabIdentifier + ']');

    // check if group tab exists
    if ($tab.length === 0) {
      // create new tab
      $tab =
        $('<li />', {role: 'presentation', class: 'nav-item', 'data-identifier': tabIdentifier}).append(
          $('<a />', {
            'aria-controls': tabIdentifier,
            'data-bs-toggle': 'tab',
            class: 'nav-link',
            href: '#' + tabIdentifier,
            role: 'tab',
          }).text(group + ' ').append(
            $('<span />', {'class': 'badge'}),
          ),
        ).on('shown.bs.tab', (e: Event) => {
          $(e.currentTarget).find('.badge').text('');
        });
      $debugTabs.append($tab);
      $tabContent.append(
        $('<div />', {role: 'tabpanel', 'class': 'tab-pane', id: tabIdentifier}).append(
          $('<div />', {'class': 't3js-messages messages'}),
        ),
      );
    }

    // activate the first tab if no one is active
    if ($debugTabs.find('.active').length === 0) {
      $debugTabs.find('a:first').tab('show');
    }

    DebugConsole.incrementInactiveTabCounter($tab);
    this.incrementUnreadMessagesIfCollapsed();

    const $messageBox = $('#' + tabIdentifier + ' .t3js-messages');
    const isMessageBoxActive = $messageBox.parent().hasClass('active');

    $messageBox.append($line);
    if (this.settings.autoscroll && isMessageBoxActive) {
      $messageBox.scrollTop($messageBox.prop('scrollHeight'));
    }
  }

  private createDom(): void {
    if (typeof this.$consoleDom !== 'undefined') {
      return;
    }

    this.$consoleDom =
      $('<div />', {id: 'typo3-debug-console'}).append(
        $('<div />', {'class': 't3js-topbar topbar'}).append(
          $('<p />', {'class': 'pull-left'}).text(' TYPO3 Debug Console').prepend(
            $('<span />', {'class': 'fa fa-terminal topbar-icon'}),
          ).append(
            $('<span />', {'class': 'badge'}),
          ),
          $('<div />', {'class': 't3js-buttons btn-group pull-right'}),
        ),
        $('<div />').append(
          $('<div />', {role: 'tabpanel'}).append(
            $('<ul />', {'class': 'nav nav-tabs t3js-debuggroups', role: 'tablist'}),
          ),
          $('<div />', {'class': 'tab-content t3js-debugcontent'}),
        ),
      );

    this.addButton(
      $('<button />', {
        'class': 'btn btn-default btn-sm ' + (this.settings.autoscroll ? 'active' : ''),
        title: TYPO3.lang['debuggerconsole.autoscroll'],
      }).append($('<span />', {'class': 't3-icon fa fa-magnet'})),
      (): void => {
        $(this).button('toggle');
        this.settings.autoscroll = !this.settings.autoscroll;
      },
    ).addButton(
      $('<button />', {
        'class': 'btn btn-default btn-sm',
        title: TYPO3.lang['debuggerconsole.toggle.collapse'],
      }).append($('<span />', {'class': 't3-icon fa fa-chevron-down'})),
      (e: Event): void => {
        let $button = $(e.currentTarget);
        let $icon = $button.find('.t3-icon');
        let $innerContainer = this.$consoleDom.find('.t3js-topbar').next();
        $innerContainer.toggle();
        if ($innerContainer.is(':visible')) {
          $button.attr('title', TYPO3.lang['debuggerconsole.toggle.collapse']);
          $icon.toggleClass('fa-chevron-down', true).toggleClass('fa-chevron-up', false);
          this.resetGlobalUnreadCounter();
        } else {
          $button.attr('title', TYPO3.lang['debuggerconsole.toggle.expand']);
          $icon.toggleClass('fa-chevron-down', false).toggleClass('fa-chevron-up', true);
        }
      },
    ).addButton(
      $('<button />', {
        'class': 'btn btn-default btn-sm',
        title: TYPO3.lang['debuggerconsole.clear']}).append($('<span />', {class: 't3-icon fa fa-undo'})),
      (): void => {
        this.flush();
      },
    ).addButton(
      $('<button />', {
        'class': 'btn btn-default btn-sm',
        title: TYPO3.lang['debuggerconsole.close']}).append($('<span />', {'class': 't3-icon fa fa-times'})),
      (): void => {
        this.destroy();
        this.createDom();
      },
    );
  }

  /**
   * Adds a button and it's callback to the console's toolbar
   *
   * @param {JQuery} $button
   * @param callback
   * @returns {DebugConsole}
   */
  private addButton($button: JQuery, callback: any): this {
    $button.on('click', callback);
    this.$consoleDom.find('.t3js-buttons').append($button);

    return this;
  }

  /**
   * Attach the Debugger Console to the viewport
   */
  private attachToViewport(): void {
    const $viewport = $('.t3js-scaffold-content');
    if ($viewport.has(this.$consoleDom).length === 0) {
      $viewport.append(this.$consoleDom);
    }
  }

  /**
   * Increment the counter of unread messages in the tabbar
   */
  private incrementUnreadMessagesIfCollapsed(): void {
    const $topbar = this.$consoleDom.find('.t3js-topbar');
    const $innerContainer = $topbar.next();

    if ($innerContainer.is(':hidden')) {
      const $badge = $topbar.find('.badge');
      let value = parseInt($badge.text(), 10);

      if (isNaN(value)) {
        value = 0;
      }
      $badge.text(++value);
    }
  }

  /**
   * Reset global unread counter
   */
  private resetGlobalUnreadCounter(): void {
    this.$consoleDom.find('.t3js-topbar').find('.badge').text('');
  }

  /**
   * Reset the console
   */
  private flush(): void {
    const $debugTabs = this.$consoleDom.find('.t3js-debuggroups');
    const $tabContent = this.$consoleDom.find('.t3js-debugcontent');

    $debugTabs.children().remove();
    $tabContent.children().remove();
  }

  /**
   * Destroy everything of the console
   */
  private destroy(): void {
    this.$consoleDom.remove();
    this.$consoleDom = undefined;
  }
}

const debugConsole = new DebugConsole();

// expose as global object
TYPO3.DebugConsole = debugConsole;
export default debugConsole;
