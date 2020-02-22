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

import * as $ from 'jquery';
import * as NProgress from 'nprogress';
import Notification = require('TYPO3/CMS/Backend/Notification');
import 'datatables';

enum ExtensionManagerUpdateIdentifier {
  extensionTable = '#terTable',
  terUpdateAction = '.update-from-ter',
  pagination = '.pagination-wrap',
  splashscreen = '.splash-receivedata',
  terTableDataTableWrapper = '#terTableWrapper .dataTables_wrapper',
}

class ExtensionManagerUpdate {
  /**
   * Register "update from ter" action
   */
  public initializeEvents(): void {
    $(ExtensionManagerUpdateIdentifier.terUpdateAction).each((index: number, element: any): void => {
      // "this" is the form which updates the extension list from
      // TER on submit
      const $me = $(element);
      const updateURL = $me.attr('action');

      $me.attr('action', '#');
      $me.submit((): boolean => {
        // Force update on click.
        this.updateFromTer(updateURL, true);

        // Prevent normal submit action.
        return false;
      });

      // This might give problems when there are more "update"-buttons,
      // each one would trigger a TER-this.
      this.updateFromTer(updateURL, false);
    });
  }

  private updateFromTer(url: string, forceUpdate: boolean): void {
    if (forceUpdate) {
      url = url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1';
    }

    // Hide triggers for TER update
    $(ExtensionManagerUpdateIdentifier.terUpdateAction).addClass('extensionmanager-is-hidden');

    // Hide extension table
    $(ExtensionManagerUpdateIdentifier.extensionTable).hide();

    // Show loaders
    $(ExtensionManagerUpdateIdentifier.splashscreen).addClass('extensionmanager-is-shown');
    $(ExtensionManagerUpdateIdentifier.terTableDataTableWrapper).addClass('extensionmanager-is-loading');
    $(ExtensionManagerUpdateIdentifier.pagination).addClass('extensionmanager-is-loading');

    let reload = false;

    $.ajax({
      url: url,
      dataType: 'json',
      cache: false,
      beforeSend: (): void => {
        NProgress.start();
      },
      success: (data: any): void => {
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
      },
      error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): void => {
        // Create an error message with diagnosis info.
        const errorMessage = textStatus + '(' + errorThrown + '): ' + jqXHR.responseText;

        Notification.warning(
          TYPO3.lang['extensionList.updateFromTerFlashMessage.title'],
          errorMessage,
          10,
        );
      },
      complete: (): void => {
        NProgress.done();

        if (!reload) {
          // Hide loaders
          $(ExtensionManagerUpdateIdentifier.splashscreen).removeClass('extensionmanager-is-shown');
          $(ExtensionManagerUpdateIdentifier.terTableDataTableWrapper).removeClass('extensionmanager-is-loading');
          $(ExtensionManagerUpdateIdentifier.pagination).removeClass('extensionmanager-is-loading');

          // Show triggers for TER-update
          $(ExtensionManagerUpdateIdentifier.terUpdateAction).removeClass('extensionmanager-is-hidden');

          // Show extension table
          $(ExtensionManagerUpdateIdentifier.extensionTable).show();
        }
      },
    });
  }
}

export = ExtensionManagerUpdate;
