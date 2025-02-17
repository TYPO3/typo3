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

import { html, LitElement, TemplateResult } from 'lit';
import { customElement } from 'lit/decorators';

enum Selectors {
  dashboardItem = '.dashboard-item'
}

@customElement('typo3-dashboard-widget-refresh')
export class WidgetRefresh extends LitElement {

  public override connectedCallback(): void {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }

    this.addEventListener('click', this.onRefresh);
    this.addEventListener('keydown', this.onKeyDown);
  }

  public override disconnectedCallback(): void {
    this.removeEventListener('click', this.onRefresh);
    this.removeEventListener('keydown', this.onKeyDown);

    super.disconnectedCallback();
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private onKeyDown(e: KeyboardEvent): void {
    if (e.key === 'Enter' || e.key === ' ') {
      this.onRefresh(e);
    }
  }

  private onRefresh(e: Event): void {
    e.preventDefault();
    this.closest(Selectors.dashboardItem).dispatchEvent(
      new Event('widgetRefresh', { bubbles: true })
    );
    this.blur();
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-dashboard-widget-refresh': WidgetRefresh;
  }
}
