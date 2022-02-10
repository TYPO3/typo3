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

import {LitElement, html} from 'lit';
import {customElement, property, state} from 'lit/decorators';
import {classMap} from 'lit/directives/class-map';
import {ifDefined} from 'lit/directives/if-defined';
import {AbstractAction} from './action-button/abstract-action';
import {SeverityEnum} from './enum/severity';
import Severity from './severity';

interface Action {
  label: string;
  action?: AbstractAction;
}

/**
 * Module: @typo3/backend/notification
 * Notification API for the TYPO3 backend
 */
class Notification {
  private static duration: number = 5;
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
    duration: number | string = this.duration,
    actions: Array<Action> = [],
  ): void {

    duration = (typeof duration === 'undefined') ? this.duration : duration;

    if (this.messageContainer === null || document.getElementById('alert-container') === null) {
      this.messageContainer = document.createElement('div');
      this.messageContainer.setAttribute('id', 'alert-container');
      document.body.appendChild(this.messageContainer);
    }

    const box = <NotificationMessage>document.createElement('typo3-notification-message');
    box.setAttribute('notificationId', 'notification-' + Math.random().toString(36).substr(2, 5));
    box.setAttribute('title', title);
    if (message) {
      box.setAttribute('message', message);
    }
    box.setAttribute('severity', severity.toString());
    box.setAttribute('duration', duration.toString());
    box.actions = actions;
    this.messageContainer.appendChild(box);
  }
}

@customElement('typo3-notification-message')
class NotificationMessage extends LitElement {
  @property() notificationId: string;
  @property() title: string;
  @property() message: string;
  @property({type: Number}) severity: SeverityEnum = SeverityEnum.info;
  @property() duration: number = 0;
  @property({type: Array, attribute: false}) actions: Array<Action> = [];

  @state() visible: boolean = false;
  @state() executingAction: number = -1;

  createRenderRoot(): Element|ShadowRoot {
    return this;
  }

  async firstUpdated() {
    await new Promise(resolve => window.setTimeout(resolve, 200));
    this.visible = true;
    await this.requestUpdate();
    if (this.duration > 0) {
      await new Promise(resolve => window.setTimeout(resolve, this.duration * 1000));
      this.close();
    }
  }

  async close(): Promise<void> {
    this.visible = false;
    const onfinish = () => {
      this.parentNode && this.parentNode.removeChild(this);
    };

    if ('animate' in this) {
      this.style.overflow = 'hidden';
      this.style.display = 'block';
      this.animate(
        [
          { height: this.getBoundingClientRect().height + 'px' },
          { height: 0 },
        ], {
          duration: 400,
          easing: 'cubic-bezier(.02, .01, .47, 1)'
        }
      ).onfinish = onfinish;
    } else {
      onfinish();
    }
  }

  render() {
    const className = Severity.getCssClass(this.severity);
    let icon = '';
    switch (this.severity) {
      case SeverityEnum.notice:
        icon = 'lightbulb-o';
        break;
      case SeverityEnum.ok:
        icon = 'check';
        break;
      case SeverityEnum.warning:
        icon = 'exclamation';
        break;
      case SeverityEnum.error:
        icon = 'times';
        break;
      case SeverityEnum.info:
      default:
        icon = 'info';
    }

    /* eslint-disable @typescript-eslint/indent */
    return html`
      <div
        id="${ifDefined(this.notificationId || undefined)}"
        class="${'alert alert-' + className + ' alert-dismissible fade' + (this.visible ? ' in' : '')}"
        role="alert">
        <button type="button" class="close" @click="${async (e: Event) => this.close()}">
          <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
          <span class="sr-only">Close</span>
        </button>
        <div class="media">
          <div class="media-left">
            <span class="fa-stack fa-lg">
              <i class="fa fa-circle fa-stack-2x"></i>
              <i class="${'fa fa-' + icon + ' fa-stack-1x'}"></i>
            </span>
          </div>
          <div class="media-body">
            <h4 class="alert-title">${this.title}</h4>
            <p class="alert-message text-pre-wrap">${this.message ? this.message : ''}</p>
          </div>
        </div>
        ${this.actions.length === 0 ? '' : html`
          <div class="alert-actions">
            ${this.actions.map((action, index) => html`
              <a href="#"
                 title="${action.label}"
                 @click="${async (e: any) => {
                   e.preventDefault()
                   this.executingAction = index;
                   await this.updateComplete;
                   if ('action' in action) {
                     await action.action.execute(e.currentTarget);
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

let notificationObject: any;

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
