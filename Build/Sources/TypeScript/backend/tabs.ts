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

import {Tab} from 'bootstrap';
import BrowserSession from './storage/browser-session';
import Client from './storage/client';
import DocumentService from '@typo3/core/document-service';

/**
 * Module: @typo3/backend/tabs
 * @exports @typo3/backend/tabs
 */
class Tabs {
  /**
   * Receive active tab from storage
   *
   * @param {string} id
   * @returns {string}
   */
  private static receiveActiveTab(id: string): string {
    return BrowserSession.get(id) || '';
  }

  /**
   * Set active tab to storage
   *
   * @param {string} id
   * @param {string} target
   */
  private static storeActiveTab(id: string, target: string): void {
    BrowserSession.set(id, target);
  }

  constructor() {
    DocumentService.ready().then((): void => {
      const tabContainers = document.querySelectorAll('.t3js-tabs');
      tabContainers.forEach((tabContainer: HTMLElement): void => {
        const currentActiveTab = Tabs.receiveActiveTab(tabContainer.id);
        if (currentActiveTab) {
          new Tab(document.querySelector('a[href="' + currentActiveTab + '"]')).show();
        }

        const storeLastActiveTab = tabContainer.dataset.storeLastTab === '1';
        if (storeLastActiveTab) {
          tabContainer.addEventListener('show.bs.tab', (e: Event): void => {
            const id = (e.currentTarget as HTMLElement).id;
            const tabTarget = (e.target as HTMLAnchorElement).hash;
            Tabs.storeActiveTab(id, tabTarget);
          });
        }
      });
    });

    // Remove legacy values from localStorage
    Client.unsetByPrefix('tabs-');
  }
}

export default new Tabs();
