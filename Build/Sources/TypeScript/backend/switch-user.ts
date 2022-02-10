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

import {html, TemplateResult, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';

enum Modes {
  switch = 'switch',
  exit = 'exit',
}

/**
 * Module: @typo3/backend/switch-user
 *
 * @example
 * <typo3-switch-user targetUser="123" mode="switch">
 *   <button>Switch user</button>
 * </typo3-switch-user>
 */
@customElement('typo3-backend-switch-user')
class SwitchUser extends LitElement {
  @property({type: String}) targetUser: string;
  @property({type: Modes}) mode: Modes = Modes.switch;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      if (this.mode === Modes.switch) {
        this.handleSwitchUser();
      } else if (this.mode === Modes.exit) {
        this.handleExitSwitchUser();
      }
    });
  }

  protected render(): TemplateResult {
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
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (data.success === true && data.url) {
        top.window.location.href = data.url;
      } else {
        Notification.error('Switching to user went wrong.');
      }
    });
  }

  private handleExitSwitchUser(): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.switch_user_exit)).post({}).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (data.success === true && data.url) {
        top.window.location.href = data.url;
      } else {
        Notification.error('Exiting current user went wrong.');
      }
    });
  }
}


