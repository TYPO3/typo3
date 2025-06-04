import LiveSearchConfigurator from '@typo3/backend/live-search/live-search-configurator';
import '@typo3/backend/live-search/element/provider/page-provider-result-item';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Notification from '@typo3/backend/notification';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ResultItemActionInterface, ResultItemInterface } from '@typo3/backend/live-search/element/result/item/item';
import windowManager from '@typo3/backend/window-manager';

export function registerType(type: string) {
  LiveSearchConfigurator.addInvokeHandler(type, 'switch_backend_user', (resultItem: ResultItemInterface): void => {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user)).post({
      targetUser: resultItem.extraData.uid,
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data.success === true && data.url) {
        top.window.location.href = data.url;
      } else {
        Notification.error('Switching to user went wrong.');
      }
    });
  });

  LiveSearchConfigurator.addInvokeHandler(type, 'preview', (resultItem: ResultItemInterface, action: ResultItemActionInterface): void => {
    windowManager.localOpen(action.url, true);
  });
}
