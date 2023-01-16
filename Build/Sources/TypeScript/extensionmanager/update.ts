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
import NProgress from 'nprogress';
import Notification from '@typo3/backend/notification';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import RegularEvent from '@typo3/core/event/regular-event';

enum ExtensionManagerUpdateIdentifier {
  extensionTable = '#terTable',
  terUpdateAction = '.update-from-ter',
  pagination = '.pagination-wrap',
  splashscreen = '.splash-receivedata',
  terTableWrapper = '#terTableWrapper .table',
}

class ExtensionManagerUpdate {
  /**
   * Register "update from ter" action
   */
  public initializeEvents(): void {
    const terUpdateActionForm = document.querySelector(ExtensionManagerUpdateIdentifier.terUpdateAction) as HTMLFormElement|null;
    if (terUpdateActionForm !== null) {
      new RegularEvent('submit', (e: SubmitEvent) => {
        e.preventDefault();

        this.updateFromTer((e.target as HTMLFormElement).action, true);
      }).bindTo(terUpdateActionForm);

      this.updateFromTer(terUpdateActionForm.action, false);
    }
  }

  private updateFromTer(url: string, forceUpdate: boolean): void {
    if (forceUpdate) {
      url = url + '&forceUpdateCheck=1';
    }

    // Hide triggers for TER update
    $(ExtensionManagerUpdateIdentifier.terUpdateAction).addClass('extensionmanager-is-hidden');

    // Hide extension table
    $(ExtensionManagerUpdateIdentifier.extensionTable).hide();

    // Show loaders
    $(ExtensionManagerUpdateIdentifier.splashscreen).addClass('extensionmanager-is-shown');
    $(ExtensionManagerUpdateIdentifier.terTableWrapper).addClass('extensionmanager-is-loading');
    $(ExtensionManagerUpdateIdentifier.pagination).addClass('extensionmanager-is-loading');

    let reload = false;

    NProgress.start();
    new AjaxRequest(url).get().then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      // Something went wrong, show message
      if (data.errorMessage.length) {
        Notification.error(TYPO3.lang['extensionList.updateFromTerFlashMessage.title'], data.errorMessage, 10);
      }

      // Message with latest updates
      const $lastUpdate = $(ExtensionManagerUpdateIdentifier.terUpdateAction + ' .extension-list-last-updated');
      $lastUpdate.text(data.timeSinceLastUpdate);
      $lastUpdate.attr(
        'title',
        TYPO3.lang['extensionList.updateFromTer.lastUpdate.timeOfLastUpdate'] + data.lastUpdateTime,
      );

      if (data.updated) {
        // Reload page
        reload = true;
        window.location.replace(window.location.href);
      }
    }, async (error: AjaxResponse): Promise<void> => {
      // Create an error message with diagnosis info.
      const errorMessage = error.response.statusText + '(' + error.response.status + '): ' + await error.response.text();

      Notification.warning(
        TYPO3.lang['extensionList.updateFromTerFlashMessage.title'],
        errorMessage,
        10,
      );
    }).finally((): void => {
      NProgress.done();

      if (!reload) {
        // Hide loaders
        $(ExtensionManagerUpdateIdentifier.splashscreen).removeClass('extensionmanager-is-shown');
        $(ExtensionManagerUpdateIdentifier.terTableWrapper).removeClass('extensionmanager-is-loading');
        $(ExtensionManagerUpdateIdentifier.pagination).removeClass('extensionmanager-is-loading');

        // Show triggers for TER-update
        $(ExtensionManagerUpdateIdentifier.terUpdateAction).removeClass('extensionmanager-is-hidden');

        // Show extension table
        $(ExtensionManagerUpdateIdentifier.extensionTable).show();
      }
    });
  }
}

export default ExtensionManagerUpdate;
