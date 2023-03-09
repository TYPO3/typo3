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

import 'bootstrap';
import $ from 'jquery';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import '../../renderable/clearable';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';

/**
 * Module: @typo3/install/module/local-configuration
 */
class LocalConfiguration extends AbstractInteractableModule {
  private searchInput: HTMLInputElement;
  private selectorItem: string = '.t3js-localConfiguration-item';
  private selectorToggleAllTrigger: string = '.t3js-localConfiguration-toggleAll';
  private selectorWriteTrigger: string = '.t3js-localConfiguration-write';
  private selectorSearchTrigger: string = '.t3js-localConfiguration-search';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getContent();

    // Write out new settings
    currentModal.on('click', this.selectorWriteTrigger, (): void => {
      this.write();
    });

    // Expand / collapse "Toggle all" button
    currentModal.on('click', this.selectorToggleAllTrigger, (): void => {
      const modalContent = this.getModalBody();
      const panels = modalContent.find('.panel-collapse');
      const action = (panels.eq(0).hasClass('show')) ? 'hide' : 'show';
      panels.collapse(action);
    });

    // Make jquerys "contains" work case-insensitive
    $.expr[':'].contains = $.expr.createPseudo((arg: string): Function => {
      return (elem: JQuery): boolean => {
        return $(elem).text().toUpperCase().includes(arg.toUpperCase());
      };
    });

    // Focus search field on certain user interactions
    currentModal.on('keydown', (e: JQueryEventObject): void => {
      const $searchInput = currentModal.find(this.selectorSearchTrigger);
      if (e.ctrlKey || e.metaKey) {
        // Focus search field on ctrl-f
        if (String.fromCharCode(e.which).toLowerCase() === 'f') {
          e.preventDefault();
          $searchInput.trigger('focus');
        }
      } else if (e.keyCode === 27) {
        // Clear search on ESC key
        e.preventDefault();
        $searchInput.val('').trigger('focus');
      }
    });

    // Perform expand collapse on search matches
    currentModal.on('keyup', this.selectorSearchTrigger, (e: JQueryEventObject): void => {
      const typedQuery = $(e.target).val();
      this.search(typedQuery);
    });
    currentModal.on('change', this.selectorSearchTrigger, (e: JQueryEventObject): void => {
      const typedQuery = $(e.target).val();
      this.search(typedQuery);
    });
  }

  private search(typedQuery: string): void {
    this.currentModal.find(this.selectorItem).each((index: number, element: Element): void => {
      const $item = $(element);
      if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
        $item.removeClass('hidden').addClass('searchhit');
      } else {
        $item.removeClass('searchhit').addClass('hidden');
      }
    });
    this.currentModal.find('.searchhit').parent().parent().parent().collapse('show');
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('localConfigurationGetContent')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.html(data.html);
            Modal.setButtons(data.buttons);
            this.searchInput = <HTMLInputElement>modalContent.find((this.selectorSearchTrigger)).get(0);
            this.searchInput.clearable();
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private write(): void {
    this.setModalButtonsState(false);

    const modalContent: JQuery = this.getModalBody();
    const executeToken: JQuery = this.getModuleContent().data('local-configuration-write-token');
    const configurationValues: Record<string, string> = {};
    this.findInModal('.t3js-localConfiguration-pathValue').each((i: number, element: HTMLInputElement): void => {
      const $element: JQuery = $(element);
      if ($element.attr('type') === 'checkbox') {
        if (element.checked) {
          configurationValues[$element.data('path')] = '1';
        } else {
          configurationValues[$element.data('path')] = '0';
        }
      } else {
        configurationValues[$element.data('path')] = $element.val();
      }
    });
    (new AjaxRequest(Router.getUrl())).post({
      install: {
        action: 'localConfigurationWrite',
        token: executeToken,
        configurationValues: configurationValues,
      },
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data.success === true && Array.isArray(data.status)) {
        data.status.forEach((element: MessageInterface): void => {
          Notification.showMessage(element.title, element.message, element.severity);
        });
      } else {
        Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
      }
    }, (error: AjaxResponse): void => {
      Router.handleAjaxError(error, modalContent);
    }).finally((): void => {
      this.setModalButtonsState(true);
    });
  }
}

export default new LocalConfiguration();
