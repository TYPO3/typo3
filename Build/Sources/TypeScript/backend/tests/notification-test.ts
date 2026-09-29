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

  describe('auto dismissal', () => {
    async function showNotification(): Promise<{ element: LitElement, isCleared: () => boolean }> {
      Notification.success('Title', 'Message', 1);
      const element = document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement;
      await element.updateComplete;
      let cleared = false;
      element.addEventListener('typo3-notification-clear', (): void => {
        cleared = true;
      });
      // Notifications appear with a delay, the countdown starts afterwards
      await clock.tickAsync(200);
      return { element, isCleared: (): boolean => cleared };
    }

    it('pauses while the notification is pointed at', async () => {
      const { element, isCleared } = await showNotification();

      element.dispatchEvent(new MouseEvent('mouseenter'));
      await clock.tickAsync(5000);
      expect(isCleared()).to.be.false;

      element.dispatchEvent(new MouseEvent('mouseleave'));
      await clock.tickAsync(1100);
      expect(isCleared()).to.be.true;
    });

    it('pauses while the notification has the focus', async () => {
      const { element, isCleared } = await showNotification();

      element.dispatchEvent(new FocusEvent('focusin'));
      await clock.tickAsync(5000);
      expect(isCleared()).to.be.false;

      element.dispatchEvent(new FocusEvent('focusout'));
      await clock.tickAsync(1100);
      expect(isCleared()).to.be.true;
    });

    it('continues with the time that was left', async () => {
      const { element, isCleared } = await showNotification();

      await clock.tickAsync(600);
      element.dispatchEvent(new MouseEvent('mouseenter'));
      await clock.tickAsync(5000);
      element.dispatchEvent(new MouseEvent('mouseleave'));

      await clock.tickAsync(300);
      expect(isCleared()).to.be.false;
      await clock.tickAsync(200);
      expect(isCleared()).to.be.true;
    });

    it('stays paused when the pointer leaves while the focus is still inside', async () => {
      const { element, isCleared } = await showNotification();

      element.dispatchEvent(new FocusEvent('focusin'));
      element.dispatchEvent(new MouseEvent('mouseenter'));
      element.dispatchEvent(new MouseEvent('mouseleave'));
      await clock.tickAsync(5000);
      expect(isCleared()).to.be.false;

      element.dispatchEvent(new FocusEvent('focusout'));
      await clock.tickAsync(1100);
      expect(isCleared()).to.be.true;
    });

    it('stays paused when the pointer rests on the notification before it appeared', async () => {
      Notification.success('Title', 'Message', 1);
      const element = document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement;
      await element.updateComplete;
      let cleared = false;
      element.addEventListener('typo3-notification-clear', (): void => {
        cleared = true;
      });

      element.dispatchEvent(new MouseEvent('mouseenter'));
      await clock.tickAsync(5000);
      expect(cleared).to.be.false;

      element.dispatchEvent(new MouseEvent('mouseleave'));
      await clock.tickAsync(1100);
      expect(cleared).to.be.true;
    });

    it('is not cleared by the countdown while an action is executing', async () => {
      let finishAction: () => void;
      Notification.success('Title', 'Message', 1, [
        {
          label: 'Undo',
          action: new DeferredAction((): Promise<void> => new Promise((resolve): void => {
            finishAction = resolve;
          })),
        },
      ]);
      const element = document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement;
      await element.updateComplete;
      let cleared = false;
      element.addEventListener('typo3-notification-clear', (): void => {
        cleared = true;
      });
      await clock.tickAsync(200);

      (element.querySelector('.alert-actions a') as HTMLAnchorElement).click();
      await clock.tickAsync(5000);
      expect(cleared).to.be.false;

      finishAction();
      await clock.tickAsync(0);
      expect(cleared).to.be.true;
    });
  });

  it('announces notifications to assistive technology', async () => {
    Notification.info('Info message', 'Some text', 1);
    await (document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement).updateComplete;
    expect(document.querySelector('#alert-container .alert-list').getAttribute('aria-live')).to.equal('polite');
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

  it('deferred action is called with its link', async () => {
    let called = false;
    Notification.info('Info message', 'Some text', 0, [
      {
        label: 'My deferred action',
        action: new DeferredAction(async (): Promise<void> => {
          called = true;
        }),
      },
    ]);
    const element = document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement;
    await element.updateComplete;

    (element.querySelector('.alert-actions a') as HTMLAnchorElement).click();
    await clock.tickAsync(0);

    expect(called).to.be.true;
  });

  it('executes an action only once while it is executing', async () => {
    let calls = 0;
    let finishAction: () => void;
    Notification.info('Info message', 'Some text', 0, [
      {
        label: 'Deferred action',
        action: new DeferredAction((): Promise<void> => {
          calls++;
          return new Promise((resolve): void => {
            finishAction = resolve;
          });
        }),
      },
      {
        label: 'Other action',
        action: new ImmediateAction((): void => {
          calls++;
        }),
      },
    ]);
    const element = document.querySelector('#alert-container typo3-notification-message:last-child') as LitElement;
    await element.updateComplete;
    const [first, second] = Array.from(element.querySelectorAll('.alert-actions a')) as HTMLAnchorElement[];

    first.click();
    await clock.tickAsync(0);
    // Keyboard activation still reaches a link which ignores the pointer
    first.click();
    second.click();
    await clock.tickAsync(0);
    expect(calls).to.equal(1);
    expect(first.getAttribute('aria-disabled')).to.equal('true');
    expect(second.getAttribute('aria-disabled')).to.equal('true');

    finishAction();
    await clock.tickAsync(0);
    expect(calls).to.equal(1);
  });
});
