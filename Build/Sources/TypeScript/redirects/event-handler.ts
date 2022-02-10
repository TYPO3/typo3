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

import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import NotificationService from '@typo3/backend/notification';
import DeferredAction from '@typo3/backend/action-button/deferred-action';

/**
 * Module: @typo3/redirects/event-handler
 * @exports @typo3/redirects/event-handler
 */
class EventHandler {
  public constructor() {
    document.addEventListener(
      'typo3:redirects:slugChanged',
      (evt: CustomEvent) => this.onSlugChanged(evt.detail),
    );
  }

  public dispatchCustomEvent(name: string, detail: any = null): void {
    const event = new CustomEvent(name, {detail: detail});
    document.dispatchEvent(event);
  }

  public onSlugChanged(detail: any): void {
    let actions: any = [];
    const correlations = detail.correlations;

    if (detail.autoUpdateSlugs) {
      actions.push({
        label: TYPO3.lang['notification.redirects.button.revert_update'],
        action: new DeferredAction(() => this.revert([
          correlations.correlationIdSlugUpdate,
          correlations.correlationIdRedirectCreation,
        ])),
      });
    }
    if (detail.autoCreateRedirects) {
      actions.push({
        label: TYPO3.lang['notification.redirects.button.revert_redirect'],
        action: new DeferredAction(() => this.revert([
          correlations.correlationIdRedirectCreation,
        ])),
      });
    }

    let title = TYPO3.lang['notification.slug_only.title'];
    let message = TYPO3.lang['notification.slug_only.message'];
    if (detail.autoCreateRedirects) {
      title = TYPO3.lang['notification.slug_and_redirects.title'];
      message = TYPO3.lang['notification.slug_and_redirects.message'];
    }
    NotificationService.info(
      title,
      message,
      0,
      actions,
    );
  }

  private revert(correlationIds: string[]): Promise<AjaxResponse> {
    const request = new AjaxRequest(TYPO3.settings.ajaxUrls.redirects_revert_correlation).withQueryArguments({
      correlation_ids: correlationIds
    }).get();

    request.then(async (response: AjaxResponse): Promise<void> => {
      const json = await response.resolve();
      if (json.status === 'ok') {
        NotificationService.success(json.title, json.message);
      }
      if (json.status === 'error') {
        NotificationService.error(json.title, json.message);
      }
    }).catch((): void => {
      NotificationService.error(TYPO3.lang.redirects_error_title, TYPO3.lang.redirects_error_message);
    });
    return request;
  }
}

export default new EventHandler();
