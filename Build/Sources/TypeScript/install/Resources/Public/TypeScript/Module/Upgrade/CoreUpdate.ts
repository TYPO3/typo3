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

import $ from 'jquery';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ResponseError} from 'TYPO3/CMS/Core/Ajax/ResponseError';
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import FlashMessage = require('../../Renderable/FlashMessage');
import Severity = require('../../Renderable/Severity');
import Router = require('../../Router');

interface ActionItem {
  loadingMessage: string;
  finishMessage: string;
  nextActionName: string;
}

interface ActionQueue {
  [k: string]: ActionItem;
}

class CoreUpdate extends AbstractInteractableModule {
  private actionQueue: ActionQueue = {
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

  private selectorOutput: string = '.t3js-coreUpdate-output';
  private updateButton: string = '.t3js-coreUpdate-button';

  /**
   * Clone of a DOM object acts as button template
   */
  private buttonTemplate: any = null;

  /**
   * Fetching the templates out of the DOM
   */
  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData().then((): void => {
      this.buttonTemplate = this.findInModal(this.updateButton).clone();
    });

    currentModal.on('click', '.t3js-coreUpdate-init', (e: JQueryEventObject): void => {
      e.preventDefault();
      // Don't use jQuery's data() function, as the DOM is re-rendered and any set data attribute gets lost.
      // See showActionButton()
      const action = $(e.currentTarget).attr('data-action');

      this.findInModal(this.selectorOutput).empty();
      switch (action) {
        case 'checkForUpdate':
          this.callAction('coreUpdateIsUpdateAvailable');
          break;
        case 'updateDevelopment':
          this.update('development');
          break;
        case 'updateRegular':
          this.update('regular');
          break;
        default:
          throw 'Unknown update action "' + action + '"';
      }
    });
  }

  private getData(): Promise<any> {
    const modalContent = this.getModalBody();
    return (new AjaxRequest(Router.getUrl('coreUpdateGetData')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
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
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const result = await response.resolve();
          const canContinue = this.handleResult(result, this.actionQueue[actionName].finishMessage);
          if (canContinue === true && (this.actionQueue[actionName].nextActionName !== undefined)) {
            this.callAction(this.actionQueue[actionName].nextActionName, type);
          }
        },
        (error: ResponseError): void => {
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
    if (successMessage) {
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
    const domMessage = FlashMessage.render(Severity.loading, messageTitle);
    this.findInModal(this.selectorOutput).append(domMessage);
  }

  /**
   * Remove an enabled loading message
   */
  private removeLoadingMessage(): void {
    this.findInModal(this.selectorOutput).find('.alert-loading').remove();
  }

  /**
   * Show a list of status messages
   *
   * @param messages
   */
  private showStatusMessages(messages: any): void {
    $.each(messages, (index: number, element: any): void => {
      let title: string = '';
      let message: string = '';
      const severity: number = element.severity;
      if (element.title) {
        title = element.title;
      }
      if (element.message) {
        message = element.message;
      }
      this.addMessage(severity, title, message);
    });
  }

  /**
   * Show an action button
   *
   * @param button
   */
  private showActionButton(button: any): void {
    let title = false;
    let action = false;
    if (button.title) {
      title = button.title;
    }
    if (button.action) {
      action = button.action;
    }
    const domButton = this.buttonTemplate;
    if (action) {
      domButton.attr('data-action', action);
    }
    if (title) {
      domButton.text(title);
    }
    this.findInModal(this.updateButton).replaceWith(domButton);
  }

  /**
   * Show a status message
   */
  private addMessage(severity: number, title: string, message?: string): void {
    const domMessage = FlashMessage.render(severity, title, message);
    this.findInModal(this.selectorOutput).append(domMessage);
  }
}

export = new CoreUpdate();
