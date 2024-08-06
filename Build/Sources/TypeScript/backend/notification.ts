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

import { LitElement, html } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import { classMap } from 'lit/directives/class-map';
import { ifDefined } from 'lit/directives/if-defined';
import { AbstractAction } from './action-button/abstract-action';
import { SeverityEnum } from './enum/severity';
import Severity from './severity';
import '@typo3/backend/element/icon-element';

interface Action {
  label: string;
  action?: AbstractAction;
}

/**
 * Module: @typo3/backend/notification
 * Notification API for the TYPO3 backend
 */
class Notification {
  private static readonly duration: number = 5;
  private static messageContainer: HTMLElement = null;

  /**
   * Show a notice notification
   *
   * @param {string} title
   * @param {string} message
   * @param {number} duration
   * @param {Action[]} actions
   */
  public static notice(title: string, message?: string, duration?: number, actions?: Array<Action>): void {
    Notification.showMessage(title, message, SeverityEnum.notice, duration, actions);
  }

  /**
   * Show a info notification
   *
   * @param {string} title
   * @param {string} message
   * @param {number} duration
   * @param {Action[]} actions
   */
  public static info(title: string, message?: string, duration?: number, actions?: Array<Action>): void {
    Notification.showMessage(title, message, SeverityEnum.info, duration, actions);
  }

  /**
   * Show a success notification
   *
   * @param {string} title
   * @param {string} message
   * @param {number} duration
   * @param {Action[]} actions
   */
  public static success(title: string, message?: string, duration?: number, actions?: Array<Action>): void {
    Notification.showMessage(title, message, SeverityEnum.ok, duration, actions);
  }

  /**
   * Show a warning notification
   *
   * @param {string} title
   * @param {string} message
   * @param {number} duration
   * @param {Action[]} actions
   */
  public static warning(title: string, message?: string, duration?: number, actions?: Array<Action>): void {
    Notification.showMessage(title, message, SeverityEnum.warning, duration, actions);
  }

  /**
   * Show a error notification
   *
   * @param {string} title
   * @param {string} message
   * @param {number} duration
   * @param {Action[]} actions
   */
  public static error(title: string, message?: string, duration: number = 0, actions?: Array<Action>): void {
    Notification.showMessage(title, message, SeverityEnum.error, duration, actions);
  }

  /**
   * @param {string} title
   * @param {string} message
   * @param {SeverityEnum} severity
   * @param {number} duration
   * @param {Action[]} actions
   */
  public static showMessage(
    title: string,
    message?: string,
    severity: SeverityEnum = SeverityEnum.info,
    duration?: number | string,
    actions: Array<Action> = [],
  ): void {
    if (typeof duration === 'undefined') {
      duration = (severity === SeverityEnum.error) ? 0 : this.duration;
    }

    if (this.messageContainer === null || document.getElementById('alert-container') === null) {
      this.messageContainer = document.createElement('div');
      this.messageContainer.setAttribute('id', 'alert-container');
      document.body.appendChild(this.messageContainer);
    }

    const box = <NotificationMessage>document.createElement('typo3-notification-message');
    box.setAttribute('notification-id', 'notification-' + Math.random().toString(36).substring(2, 6));
    box.setAttribute('notification-title', title);
    if (message) {
      box.setAttribute('notification-message', message);
    }
    box.setAttribute('notification-severity', severity.toString());
    box.setAttribute('notification-duration', duration.toString());
    box.actions = actions;
    this.messageContainer.appendChild(box);
  }
}

@customElement('typo3-notification-message')
export class NotificationMessage extends LitElement {
  @property({ type: String, attribute: 'notification-id' }) notificationId: string;
  @property({ type: String, attribute: 'notification-title' }) notificationTitle: string;
  @property({ type: String, attribute: 'notification-message' }) notificationMessage: string;
  @property({ type: Number, attribute: 'notification-severity' }) notificationSeverity: SeverityEnum = SeverityEnum.info;
  @property({ type: Number, attribute: 'notification-duration' }) notificationDuration: number = 0;
  @property({ type: Array, attribute: false }) actions: Array<Action> = [];

  @state() executingAction: number = -1;

  public async firstUpdated(): Promise<void> {
    await new Promise(resolve => window.setTimeout(resolve, 200));
    await this.requestUpdate();
    if (this.notificationDuration > 0) {
      await new Promise(resolve => window.setTimeout(resolve, this.notificationDuration * 1000));
      this.close();
    }
  }

  public async close(): Promise<void> {
    this.addEventListener('typo3-notification-clear-finish', (): void => {
      this.parentNode && this.parentNode.removeChild(this);
    });

    const dispatchFinishEvent = (): void => {
      this.dispatchEvent(new CustomEvent('typo3-notification-clear-finish'));
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!prefersReducedMotion && 'animate' in this) {
      this.style.overflow = 'hidden';
      this.style.display = 'block';
      this.animate(
        [
          { height: this.getBoundingClientRect().height + 'px' },
          { height: 0, opacity: 0, marginTop: 0 },
        ], {
          duration: 400,
          easing: 'cubic-bezier(.02, .01, .47, 1)'
        }
      ).onfinish = dispatchFinishEvent;
    } else {
      dispatchFinishEvent();
    }
  }

  protected createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected render() {
    const className = Severity.getCssClass(this.notificationSeverity);
    let icon = '';
    switch (this.notificationSeverity) {
      case SeverityEnum.notice:
        icon = 'actions-lightbulb';
        break;
      case SeverityEnum.ok:
        icon = 'actions-check';
        break;
      case SeverityEnum.warning:
        icon = 'actions-exclamation';
        break;
      case SeverityEnum.error:
        icon = 'actions-close';
        break;
      case SeverityEnum.info:
      default:
        icon = 'actions-info';
    }

    const randomSuffix = (Math.random() + 1).toString(36).substring(2);

    /* eslint-disable @typescript-eslint/indent */
    return html`
      <div
        id="${ifDefined(this.notificationId || undefined)}"
        class="alert alert-${className} alert-dismissible"
        role="alertdialog"
        aria-labelledby="alert-title-${randomSuffix}"
        aria-describedby="alert-message-${randomSuffix}"
      >
        <button type="button" class="close" @click="${async () => this.close()}">
          <span aria-hidden="true"><typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon></span>
          <span class="visually-hidden">Close</span>
        </button>
        <div class="media">
          <div class="media-left">
            <span class="icon-emphasized">
              <typo3-backend-icon identifier="${icon}" size="small"></typo3-backend-icon>
            </span>
          </div>
          <div class="media-body">
            <div class="alert-title" id="alert-title-${randomSuffix}">${this.notificationTitle}</div>
            <p class="alert-message" id="alert-message-${randomSuffix}">${this.notificationMessage ? this.notificationMessage : ''}</p>
          </div>
        </div>
        ${this.actions.length === 0 ? '' : html`
          <div class="alert-actions">
            ${this.actions.map((action, index) => html`
              <a href="#"
                 title="${action.label}"
                 @click="${async (event: PointerEvent) => {
                   event.preventDefault();
                   this.executingAction = index;
                   await this.updateComplete;
                   if ('action' in action) {
                     await action.action.execute(event.currentTarget as HTMLAnchorElement);
                   }
                   this.close();
                 }}"
                 class="${classMap({
                   executing: this.executingAction === index,
                   disabled: this.executingAction >= 0 && this.executingAction !== index
                 })}"
                >${action.label}</a>
            `)}
          </div>
        `}
      </div>
    `;
    /* eslint-enable @typescript-eslint/indent */
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-notification-message': NotificationMessage;
  }
}

let notificationObject: typeof Notification;

try {
  // fetch from parent
  if (parent && parent.window.TYPO3 && parent.window.TYPO3.Notification) {
    notificationObject = parent.window.TYPO3.Notification;
  }

  // fetch object from outer frame
  if (top && top.TYPO3.Notification) {
    notificationObject = top.TYPO3.Notification;
  }
} catch {
  // This only happens if the opener, parent or top is some other url (eg a local file)
  // which loaded the current window. Then the browser's cross domain policy jumps in
  // and raises an exception.
  // For this case we are safe and we can create our global object below.
}

if (!notificationObject) {
  notificationObject = Notification;

  // attach to global frame
  if (typeof TYPO3 !== 'undefined') {
    TYPO3.Notification = notificationObject;
  }
}
export default notificationObject;
