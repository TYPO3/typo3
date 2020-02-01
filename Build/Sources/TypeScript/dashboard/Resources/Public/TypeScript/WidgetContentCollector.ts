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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');

class WidgetContentCollector {

  private selector: string = '.dashboard-item';

  constructor() {
    $((): void => {
      this.initialize();
    });
  }

  public initialize(): void {
    $(this.selector).each((index: number, triggerElement: Element): void => {
      const sentRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls['ext-dashboard-get-widget-content']))
        .withQueryArguments({
          widget: $(triggerElement).data('widget-key'),
        })
        .get()
        .then(async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          $(triggerElement).find('.widget-content').html(data.content);
          $(triggerElement).find('.widget-content').removeClass('hide');
          $(triggerElement).find('.widget-waiting').addClass('hide');

          if (Object.keys(data.eventdata).length > 0) {
            $(triggerElement).trigger('widgetContentRendered', data.eventdata);
          } else {
            $(triggerElement).trigger('widgetContentRendered');
          }
        }
        );

      sentRequest.catch((reason: Error): void => {
        $(triggerElement).find('.widget-error').removeClass('hide');
        $(triggerElement).find('.widget-waiting').addClass('hide');
        console.warn('Error while retrieving widget [' + $(triggerElement).data('widget-key') + '] content: ' + reason.message)
      });
    });
  }
}

export = new WidgetContentCollector();
