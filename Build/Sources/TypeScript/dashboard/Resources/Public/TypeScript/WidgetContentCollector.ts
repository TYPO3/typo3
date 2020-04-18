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

import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');

class WidgetContentCollector {

  private readonly selector: string = '.dashboard-item';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    const items = document.querySelectorAll(this.selector);
    items.forEach((triggerElement: HTMLElement): void => {
      const widgetWaitingElement = triggerElement.querySelector('.widget-waiting');
      const widgetContentElement = triggerElement.querySelector('.widget-content');
      const widgetErrorElement = triggerElement.querySelector('.widget-error');

      const sentRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_get_widget_content))
        .withQueryArguments({
          widget: triggerElement.dataset.widgetKey,
        })
        .get()
        .then(async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (widgetContentElement !== null) {
            widgetContentElement.innerHTML = data.content;
            widgetContentElement.classList.remove('hide');
          }
          if (widgetWaitingElement !== null) {
            widgetWaitingElement.classList.add('hide');
          }

          let event: Event;
          const eventInitDict: EventInit = {
            bubbles: true,
          };
          if (Object.keys(data.eventdata).length > 0) {
            event = new CustomEvent('widgetContentRendered', {...eventInitDict, detail: data.eventdata});
          } else {
            event = new Event('widgetContentRendered', eventInitDict);
          }
          triggerElement.dispatchEvent(event);
        });

      sentRequest.catch((reason: Error): void => {
        if (widgetErrorElement !== null) {
          widgetErrorElement.classList.remove('hide');
        }
        if (widgetWaitingElement !== null) {
          widgetWaitingElement.classList.add('hide');
        }
        console.warn(`Error while retrieving widget [${triggerElement.dataset.widgetKey}] content: ${reason.message}`);
      });
    });
  }
}

export = new WidgetContentCollector();
