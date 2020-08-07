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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ResponseError} from 'TYPO3/CMS/Core/Ajax/ResponseError';
import '../../Renderable/Clearable';
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import Router = require('../../Router');

/**
 * Module: TYPO3/CMS/Install/Module/LocalConfiguration
 */
class LocalConfiguration extends AbstractInteractableModule {
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
      const action = (panels.eq(0).hasClass('in')) ? 'hide' : 'show';
      panels.collapse(action);
    });

    // Make jquerys "contains" work case-insensitive
    jQuery.expr[':'].contains = jQuery.expr.createPseudo((arg: any): Function => {
      return (elem: any): boolean => {
        return jQuery(elem).text().toUpperCase().includes(arg.toUpperCase());
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
      const $searchInput = currentModal.find((this.selectorSearchTrigger));
      currentModal.find('div.item').each((index: number, element: any): void => {
        const $item = $(element);
        if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
          $item.removeClass('hidden').addClass('searchhit');
        } else {
          $item.removeClass('searchhit').addClass('hidden');
        }
      });
      currentModal.find('.searchhit').parent().collapse('show');
      // Make search field clearable
      const searchInput = <HTMLInputElement>$searchInput.get(0);
      searchInput.clearable();
      searchInput.focus();
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('localConfigurationGetContent')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.html(data.html);
            Modal.setButtons(data.buttons);
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private write(): void {
    this.setModalButtonsState(false);

    const modalContent: JQuery = this.getModalBody();
    const executeToken: JQuery = this.getModuleContent().data('local-configuration-write-token');
    const configurationValues: any = {};
    this.findInModal('.t3js-localConfiguration-pathValue').each((i: number, element: any): void => {
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
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (data.success === true && Array.isArray(data.status)) {
        data.status.forEach((element: any): void => {
          Notification.showMessage(element.title, element.message, element.severity);
        });
      } else {
        Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
      }
    }, (error: ResponseError): void => {
      Router.handleAjaxError(error, modalContent);
    }).finally((): void => {
      this.setModalButtonsState(true);
    });
  }
}

export = new LocalConfiguration();
