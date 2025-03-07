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

import { html, css, type TemplateResult, LitElement } from 'lit';
import { customElement, property } from 'lit/decorators';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';

enum Modes {
  switch = 'switch',
  exit = 'exit',
}

/**
 * Module: @typo3/backend/switch-user
 *
 * @example
 * <typo3-switch-user class="some" targetUser="123" mode="switch">
 *   Switch user
 * </typo3-switch-user>
 */
@customElement('typo3-backend-switch-user')
export class SwitchUser extends LitElement {
  static override styles = [css`:host { cursor: pointer; appearance: button; }`];

  @property({ type: String }) targetUser: string;
  @property({ type: Modes }) mode: Modes = Modes.switch;

  public constructor() {
    super();
    this.addEventListener('click', (event: Event): void => {
      event.preventDefault();
      if (this.mode === Modes.switch) {
        this.handleSwitchUser();
      } else if (this.mode === Modes.exit) {
        this.handleExitSwitchUser();
      }
    });
    this.addEventListener('keydown', (event: KeyboardEvent): void => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (this.mode === Modes.switch) {
          this.handleSwitchUser();
        } else if (this.mode === Modes.exit) {
          this.handleExitSwitchUser();
        }
      }
    });
  }

  public override connectedCallback(): void {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private handleSwitchUser(): void {
    if (!this.targetUser) {
      // Invalid request without target user
      Notification.error('Switching to user went wrong.');
      return;
    }

    (new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user)).post({
      targetUser: this.targetUser,
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data.success === true && data.url) {
        top.window.location.href = data.url;
      } else {
        Notification.error('Switching to user went wrong.');
      }
    });
  }

  private handleExitSwitchUser(): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user_exit)).post({}).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data.success === true && data.url) {
        top.window.location.href = data.url;
      } else {
        Notification.error('Exiting current user went wrong.');
      }
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-switch-user': SwitchUser;
  }
}
