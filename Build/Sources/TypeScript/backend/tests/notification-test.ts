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

import DeferredAction from '@typo3/backend/action-button/deferred-action.js';
import ImmediateAction from '@typo3/backend/action-button/immediate-action.js';
import Notification from '@typo3/backend/notification.js';
import Icons from '@typo3/backend/icons.js';
import { expect } from '@open-wc/testing';
import { stub, useFakeTimers, type SinonStub, type SinonFakeTimers } from 'sinon';
import type { LitElement } from 'lit';

describe('@typo3/backend/notification:', () => {
  let clock: SinonFakeTimers;
  let getIconStub: SinonStub<Parameters<typeof Icons['getIcon']>, Promise<string>>;

  beforeEach((): void => {
    clock = useFakeTimers();
    getIconStub = stub(Icons, 'getIcon');
    getIconStub.returns(Promise.resolve('X'));
  });

  afterEach((): void => {
    clock.restore();
    getIconStub.restore();

    const alertContainer = document.querySelector('#alert-container .alert-list');
    while (alertContainer !== null && alertContainer.firstChild) {
      alertContainer.removeChild(alertContainer.firstChild);
    }
  });

  describe('can render notifications with dismiss after 1000ms', () => {
    interface NotificationDataSet {
      method: typeof Notification.notice | typeof Notification.info | typeof Notification.success | typeof Notification.warning | typeof Notification.error,
      title: string;
      message: string;
      class: string;
    }

    function notificationProvider(): Array<NotificationDataSet> {
      return [
        {
          method: Notification.notice,
          title: 'Notice message',
          message: 'This notification describes a notice',
          class: 'alert-notice',
        },
        {
          method: Notification.info,
          title: 'Info message',
          message: 'This notification describes an informative action',
          class: 'alert-info',
        },
        {
          method: Notification.success,
          title: 'Success message',
          message: 'This notification describes a successful action',
          class: 'alert-success',
        },
        {
          method: Notification.warning,
          title: 'Warning message',
          message: 'This notification describes a harmful action',
          class: 'alert-warning',
        },
        {
          method: Notification.error,
          title: 'Error message',
          message: 'This notification describes an erroneous action',
          class: 'alert-danger',
        },
      ];
    }

    for (const dataSet of notificationProvider()) {
      it('can render a notification of type ' + dataSet.class, async () => {
        dataSet.method(dataSet.title, dataSet.message, 1);

        const notificationMessage = document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement;
        await notificationMessage.updateComplete;

        const alertSelector = 'div.alert.' + dataSet.class;
        const alertBox = document.querySelector(alertSelector);
        expect(alertBox).not.to.be.null;
        expect(alertBox.querySelector('.alert-title').textContent).to.equal(dataSet.title);
        expect(alertBox.querySelector('.alert-message').textContent).to.equal(dataSet.message);

        // wait for the notification to disappear for the next assertion (which tests for auto dismiss)
        await clock.tickAsync(2000);

        // Notifications are hidden via an animation which cannot be faked by Sinon.
        // Instead, we dispatch a custom event to enforce its removal.
        notificationMessage.dispatchEvent(new CustomEvent('typo3-notification-clear-finish'));
        expect(document.querySelector(alertSelector)).to.be.null;
      });
    }
  });

  it('can render action buttons', async () => {
    Notification.info(
      'Info message',
      'Some text',
      1,
      [
        {
          label: 'My action',
          action: new ImmediateAction((promise: Promise<void>): Promise<void> => {
            return promise;
          }),
        },
        {
          label: 'My other action',
          action: new DeferredAction((promise: Promise<void>): Promise<void> => {
            return promise;
          }),
        },
      ],
    );

    await (document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement).updateComplete;
    const alertBox = document.querySelector('div.alert');
    expect(alertBox.querySelector('.alert-actions')).not.to.be.null;
    expect(alertBox.querySelectorAll('.alert-actions a').length).to.equal(2);
    expect(alertBox.querySelectorAll('.alert-actions a')[0].textContent).to.equal('My action');
    expect(alertBox.querySelectorAll('.alert-actions a')[1].textContent).to.equal('My other action');
  });

  it('immediate action is called', async () => {
    let called = false;

    Notification.info(
      'Info message',
      'Some text',
      1,
      [
        {
          label: 'My immediate action',
          action: new ImmediateAction(() => {
            called = true;
          }),
        },
      ],
    );

    await (document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement).updateComplete;
    const alertBox = document.querySelector('div.alert');
    (<HTMLAnchorElement>alertBox.querySelector('.alert-actions a')).click();
    await (document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement).updateComplete;
    expect(called).to.be.true;
  });
});
