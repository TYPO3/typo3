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

/**
 * Module: @typo3/backend/form-engine-link-browser-adapter
 * LinkBrowser communication with parent window
 */
import LinkBrowser from '@typo3/backend/link-browser';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { FormEngineLinkBrowserSetLinkEvent, type OnFieldChangeItem } from '@typo3/backend/event/form-engine-link-browser-set-link-event';

export default (function() {

  /**
   * @exports @typo3/backend/form-engine-link-browser-adapter
   */
  const FormEngineLinkBrowserAdapter: any = {
    onFieldChangeItems: null // those are set in the module initializer function in PHP
  };

  /**
   * @param {OnFieldChangeItem[]} onFieldChangeItems
   */
  FormEngineLinkBrowserAdapter.setOnFieldChangeItems = function(onFieldChangeItems: Array<OnFieldChangeItem>) {
    FormEngineLinkBrowserAdapter.onFieldChangeItems = onFieldChangeItems;
  };

  /**
   * Send an event with the the currently select link as payload to the opening context
   */
  LinkBrowser.finalizeFunction = async (url: string): Promise<void> => {
    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.link_browser_encodetypolink).withQueryArguments({
      ...LinkBrowser.getLinkAttributeValues(),
      url
    }).get();

    const { typoLink } = await response.resolve();
    if (!typoLink) {
      return;
    }
    window.frameElement!.dispatchEvent(new FormEngineLinkBrowserSetLinkEvent(
      typoLink,
      FormEngineLinkBrowserAdapter.onFieldChangeItems,
    ));
  };

  return FormEngineLinkBrowserAdapter;
})();
