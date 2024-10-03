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

import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule, ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import MessageInterface from '../../message-interface';
import { FlashMessage } from '../../renderable/flash-message';
import Severity from '../../renderable/severity';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

enum Identifiers {
  output = '.t3js-coreUpdate-output',
  updateButton = '.t3js-coreUpdate-button'
}

type ActionItem = {
  loadingMessage: string;
  finishMessage: string;
  nextActionName: string;
}

type ActionQueue = {
  [k: string]: ActionItem;
}

class CoreUpdate extends AbstractInteractableModule {
  private readonly actionQueue: ActionQueue = {
    coreUpdateIsUpdateAvailable: {
      loadingMessage: 'Checking for possible regular or security update',
      finishMessage: undefined,
      nextActionName: undefined,
    },
    coreUpdateCheckPreConditions: {
      loadingMessage: 'Checking if update is possible',
      finishMessage: 'System can be updated',
      nextActionName: 'coreUpdateDownload',
    },
    coreUpdateDownload: {
      loadingMessage: 'Downloading new core',
      finishMessage: undefined,
      nextActionName: 'coreUpdateVerifyChecksum',
    },
    coreUpdateVerifyChecksum: {
      loadingMessage: 'Verifying checksum of downloaded core',
      finishMessage: undefined,
      nextActionName: 'coreUpdateUnpack',
    },
    coreUpdateUnpack: {
      loadingMessage: 'Unpacking core',
      finishMessage: undefined,
      nextActionName: 'coreUpdateMove',
    },
    coreUpdateMove: {
      loadingMessage: 'Moving core',
      finishMessage: undefined,
      nextActionName: 'coreUpdateActivate',
    },
    coreUpdateActivate: {
      loadingMessage: 'Activating core',
      finishMessage: 'Core updated - please reload your browser',
      nextActionName: undefined,
    },
  };

  /**
   * Clone of a DOM object acts as button template
   */
  private buttonTemplate: HTMLElement = null;

  /**
   * Fetching the templates out of the DOM
   */
  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    this.loadModuleFrameAgnostic('@typo3/install/renderable/flash-message.js').then(async (): Promise<void> => {
      await this.getData();
      this.buttonTemplate = this.findInModal(Identifiers.updateButton)?.cloneNode(true) as HTMLElement;
    });

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      event.preventDefault();

      if (!target.hasAttribute('data-action')) {
        this.callAction('coreUpdateIsUpdateAvailable');
        return;
      }

      const action = target.dataset.action;
      this.findInModal(Identifiers.output).innerHTML = '';
      switch (action) {
        case 'updateDevelopment':
          this.update('development');
          break;
        case 'updateRegular':
          this.update('regular');
          break;
        default:
          throw 'Unknown update action "' + action + '"';
      }
    }).delegateTo(currentModal, '.t3js-coreUpdate-init');
  }

  private getData(): Promise<void> {
    const modalContent = this.getModalBody();
    return (new AjaxRequest(Router.getUrl('coreUpdateGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponseWithButtons = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  /**
   * Execute core update.
   *
   * @param type Either 'development' or 'regular'
   */
  private update(type: string): void {
    if (type !== 'development') {
      type = 'regular';
    }
    this.callAction('coreUpdateCheckPreConditions', type);
  }

  /**
   * Generic method to call actions from the queue
   *
   * @param actionName Name of the action to be called
   * @param type Update type (optional)
   */
  private callAction(actionName: string, type?: string): void {
    const data: any = {
      install: {
        action: actionName,
      },
    };
    if (type !== undefined) {
      data.install.type = type;
    }
    this.addLoadingMessage(this.actionQueue[actionName].loadingMessage);
    (new AjaxRequest(Router.getUrl()))
      .withQueryArguments(data)
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          // @todo: build a map containing the desired type for the response of `actionName`
          const result = await response.resolve();
          const canContinue = this.handleResult(result, this.actionQueue[actionName].finishMessage);
          if (canContinue === true && (this.actionQueue[actionName].nextActionName !== undefined)) {
            this.callAction(this.actionQueue[actionName].nextActionName, type);
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, this.getModalBody());
        }
      );
  }

  /**
   * Handle ajax result of core update step.
   */
  private handleResult(data: any, successMessage: string): boolean {
    const canContinue: boolean = data.success;
    this.removeLoadingMessage();

    if (data.status && typeof (data.status) === 'object') {
      this.showStatusMessages(data.status);
    }
    if (data.action && typeof (data.action) === 'object') {
      this.showActionButton(data.action);
    }
    if (canContinue && successMessage) {
      this.addMessage(Severity.ok, successMessage);
    }
    return canContinue;
  }

  /**
   * Add a loading message with some text.
   *
   * @param messageTitle
   */
  private addLoadingMessage(messageTitle: string): void {
    this.addMessage(Severity.loading, messageTitle);
  }

  /**
   * Remove an enabled loading message
   */
  private removeLoadingMessage(): void {
    this.findInModal(Identifiers.output).querySelector('typo3-install-flashmessage')?.remove();
  }

  /**
   * Show a list of status messages
   *
   * @param messages
   */
  private showStatusMessages(messages: MessageInterface[]): void {
    for (const element of messages) {
      this.addMessage(element.severity, element.title ?? '', element.message ?? '');
    }
  }

  /**
   * Show an action button
   *
   * @param button
   */
  private showActionButton(button: any): void {
    const domButton = this.buttonTemplate;
    if (button.action) {
      domButton.setAttribute('data-action', button.action);
    }
    if (button.title) {
      domButton.innerText = button.title;
    }
    this.findInModal(Identifiers.updateButton).replaceWith(domButton);
  }

  /**
   * Show a status message
   */
  private addMessage(severity: number, title: string, message?: string): void {
    this.findInModal(Identifiers.output).append(FlashMessage.create(severity, title, message));
  }
}

export default new CoreUpdate();
