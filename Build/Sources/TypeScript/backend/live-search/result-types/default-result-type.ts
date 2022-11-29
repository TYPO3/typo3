import {ResultItemActionInterface, ResultItemInterface} from '@typo3/backend/live-search/element/result/item/item';
import LiveSearch from '@typo3/backend/toolbar/live-search';
import '@typo3/backend/live-search/element/provider/page-provider-result-item';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';

export function registerType(type: string) {
  LiveSearch.addInvokeHandler(type, 'switch_backend_user', (resultItem: ResultItemInterface): void => {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user)).post({
      targetUser: resultItem.extraData.uid,
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (data.success === true && data.url) {
        top.window.location.href = data.url;
      } else {
        Notification.error('Switching to user went wrong.');
      }
    });
  });
}
