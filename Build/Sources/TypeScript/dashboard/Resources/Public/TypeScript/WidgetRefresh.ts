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

import {html, LitElement, TemplateResult} from 'lit';
import {customElement} from 'lit/decorators';

enum Selectors {
  dashboardItem = '.dashboard-item'
}

@customElement('typo3-dashboard-widget-refresh')
class WidgetRefresh extends LitElement {

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.closest(Selectors.dashboardItem).dispatchEvent(
        new Event('widgetRefresh', {bubbles: true})
      );
      this.querySelector('button').blur();
    });
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

}
