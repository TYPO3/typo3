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

import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import { AbstractInlineModule, type ActionResponse } from '../abstract-inline-module';
import type MessageInterface from '@typo3/install/message-interface';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';

/**
 * Module: @typo3/install/module/dump-autoload
 */
class DumpAutoload extends AbstractInlineModule {
  public override initialize(trigger: HTMLButtonElement): void {
    this.setButtonState(trigger, false);

    (new AjaxRequest(Router.getUrl('dumpAutoload')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ActionResponse = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            if (data.status.length > 0) {
              data.status.forEach((element: MessageInterface): void => {
                Notification.success(element.message);
              });
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (): void => {
          // In case the dump action fails (typically 500 from server), do not kill the entire
          // Install Tool, instead show a notification that something went wrong.
          Notification.error(
            'Autoloader not dumped',
            'Dumping autoload files failed for unknown reasons. Check the system for broken extensions and try again.'
          );
        }
      )
      .finally((): void => {
        this.setButtonState(trigger, true);
      });
  }
}

export default new DumpAutoload();
