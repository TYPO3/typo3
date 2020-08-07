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

import 'bootstrap';
import $ from 'jquery';
import BrowserSession = require('./Storage/BrowserSession');
import Client = require('./Storage/Client');

/**
 * Module: TYPO3/CMS/Backend/Tabs
 * @exports TYPO3/CMS/Backend/Tabs
 */
class Tabs {
  protected storeLastActiveTab: boolean = true;

  /**
   * Resolve timestamp
   */
  public static getTimestamp(): number {
    return Math.round((new Date()).getTime() / 1000);
  }

  constructor() {
    const that = this;
    $((): void => {
      $('.t3js-tabs').each(function(this: Element): void {
        const $tabContainer: JQuery = $(this);
        that.storeLastActiveTab = $tabContainer.data('storeLastTab') === 1;
        const currentActiveTab = that.receiveActiveTab($tabContainer.attr('id'));
        if (currentActiveTab) {
          $tabContainer.find('a[href="' + currentActiveTab + '"]').tab('show');
        }
        $tabContainer.on('show.bs.tab', (e: any) => {
          if (that.storeLastActiveTab) {
            const id = e.currentTarget.id;
            const target = e.target.hash;
            that.storeActiveTab(id, target);
          }
        });
      });
    });

    // Remove legacy values from localStorage
    Client.unsetByPrefix('tabs-');
  }

  /**
   * Receive active tab from storage
   *
   * @param {string} id
   * @returns {string}
   */
  public receiveActiveTab(id: string): string {
    return BrowserSession.get(id) || '';
  }

  /**
   * Set active tab to storage
   *
   * @param {string} id
   * @param {string} target
   */
  public storeActiveTab(id: string, target: string): void {
    BrowserSession.set(id, target);
  }
}

export = new Tabs();
