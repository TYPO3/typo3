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

import { customElement, property } from 'lit/decorators';
import { html, LitElement, nothing, TemplateResult } from 'lit';
import { ClassInfo, classMap } from 'lit/directives/class-map';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import Severity from '../severity';
import '@typo3/backend/element/icon-element';
import { lll } from '@typo3/core/lit-helper';

/**
 * * Module: @typo3/backend/element/alert-element
 *
 * @example
 * <typo3-backend-alert
 *   severity="2"
 *   heading="Alert heading"
 *   message="Alert message"
 *   dismissible
 *   show-icon
 * ></typo3-backend-alert>
 *
 * @internal this is subject to change
 */
@customElement('typo3-backend-alert')
export class AlertElement extends LitElement {
  @property({ type: Number }) severity: SeverityEnum = SeverityEnum.info;
  @property({ type: Boolean }) dismissible: boolean = false;
  @property({ type: Boolean }) visible: boolean = true;
  @property({ type: String }) heading: string = null;
  @property({ type: String }) message: string = null;
  @property({ type: Boolean, attribute: 'show-icon' }) showIcon: boolean = false;

  private readonly randomSuffix: string = Math.random().toString(36).substring(7);

  private static getIconIdentifier(severity: SeverityEnum): string {
    const icons = {
      [SeverityEnum.notice]: 'actions-lightbulb',
      [SeverityEnum.ok]: 'actions-check',
      [SeverityEnum.warning]: 'actions-exclamation',
      [SeverityEnum.error]: 'actions-close',
      [SeverityEnum.info]: 'actions-info',
    };
    return icons[severity] || 'actions-info';
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult | symbol {

    return html`
      <div
        id="alert-${this.randomSuffix}"
        class=${classMap(this.getClasses())}
        role="alert"
        aria-labelledby="alert-title-${this.randomSuffix}"
        aria-describedby="alert-message-${this.randomSuffix}"
        @closed.bs.alert="${this.remove}"
      >
        <div class="alert-inner">
          ${this.showIcon ? html`
            <div class="alert-icon">
              <span class="icon-emphasized">
                <typo3-backend-icon identifier="${AlertElement.getIconIdentifier(this.severity)}" size="small"></typo3-backend-icon>
              </span>
            </div>
          ` : nothing}
          <div class="alert-content">
            ${this.heading ? html`<h4 class="alert-title" id="alert-title-${this.randomSuffix}">${this.heading}</h4>` : nothing}
            <p class="alert-body" id="alert-message-${this.randomSuffix}">${this.message}</p>
          </div>
        </div>
        ${this.dismissible ? this.renderDismissButton() : nothing}
      </div>
    `;
  }

  private getClasses(): ClassInfo {
    return {
      ['alert']: true,
      ['alert-' + Severity.getCssClass(this.severity)]: true,
      ['alert-dismissible']: this.dismissible,
      ['fade']: true,
      ['show']: this.visible,
      ['hidden']: !this.visible,
    };
  }

  private renderDismissButton(): TemplateResult {
    return html`
      <button type="button" class="close" data-bs-dismiss="alert" aria-label="${ lll('button.close') || 'Close'}">
        <span aria-hidden="true"><typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon></span>
        <span class="visually-hidden">${ lll('button.close') || 'Close'}</span>
      </button>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-alert': AlertElement;
  }
}
