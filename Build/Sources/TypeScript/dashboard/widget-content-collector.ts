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
import RegularEvent from '@typo3/core/event/regular-event';

class WidgetContentCollector {

  private readonly selector: string = '.dashboard-item';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    this.registerEvents();
    const items = document.querySelectorAll(this.selector);
    items.forEach((triggerElement: HTMLElement): void => {
      let event: Event;
      const eventInitDict: EventInit = {
        bubbles: true,
      };
      event = new Event('widgetRefresh', eventInitDict);
      triggerElement.dispatchEvent(event);
    });
  }

  private registerEvents(): void {
    new RegularEvent('widgetRefresh', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      this.getContentForWidget(target);
    }).delegateTo(document, this.selector);
  }

  private getContentForWidget(element: HTMLElement): void {
    const widgetWaitingElement = element.querySelector('.widget-waiting');
    const widgetContentElement = element.querySelector('.widget-content');
    const widgetErrorElement = element.querySelector('.widget-error');

    widgetWaitingElement.classList.remove('hide');
    widgetContentElement.classList.add('hide');
    widgetErrorElement.classList.add('hide');

    const sentRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_get_widget_content))
      .withQueryArguments({
        widget: element.dataset.widgetKey,
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
        element.dispatchEvent(event);
      });

    sentRequest.catch((reason: Error): void => {
      if (widgetErrorElement !== null) {
        widgetErrorElement.classList.remove('hide');
      }
      if (widgetWaitingElement !== null) {
        widgetWaitingElement.classList.add('hide');
      }
      console.warn(`Error while retrieving widget [${element.dataset.widgetKey}] content: ${reason.message}`);
    });
  }
}

export default new WidgetContentCollector();
