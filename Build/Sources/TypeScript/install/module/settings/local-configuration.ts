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
import { Collapse } from 'bootstrap';
import '../../renderable/clearable';
import { AbstractInteractableModule, type ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';
import type MessageInterface from '@typo3/install/message-interface';

enum Identifiers {
  item = '.t3js-localConfiguration-item',
  toggleAllTrigger = '.t3js-localConfiguration-toggleAll',
  writeTrigger = '.t3js-localConfiguration-write',
  searchTrigger = '.t3js-localConfiguration-search'
}

type LocalConfigurationWrittenResponse = {
  status: MessageInterface[],
  success: boolean,
};

/**
 * Module: @typo3/install/module/local-configuration
 */
class LocalConfiguration extends AbstractInteractableModule {
  private searchInput: HTMLInputElement;

  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getContent();

    // Write out new settings
    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.write();
    }).delegateTo(currentModal, Identifiers.writeTrigger);

    // Expand / collapse "Toggle all" button
    new RegularEvent('click', (): void => {
      const modalContent = this.getModalBody();
      const panels = modalContent.querySelectorAll<HTMLElement>('.panel-collapse');
      panels.forEach((panel: HTMLElement) => {
        const action = panels[0].classList.contains('show') ? 'hide' : 'show';
        Collapse.getOrCreateInstance(panel)[action]();
      });
    }).delegateTo(currentModal, Identifiers.toggleAllTrigger);

    // Focus search field on certain user interactions
    new RegularEvent('keydown', (event: KeyboardEvent) => {
      const searchInput = currentModal.querySelector<HTMLInputElement>(Identifiers.searchTrigger);
      if (event.ctrlKey || event.metaKey) {
        // Focus search field on ctrl-f
        if (event.key === 'f' || event.key === 'F') {
          event.preventDefault();
          searchInput.focus();
        }
      } else if (event.key === KeyTypesEnum.ESCAPE) {
        // Clear search on ESC key
        event.preventDefault();
        searchInput.value = '';
        searchInput.focus();
      }
    }).bindTo(currentModal);

    // Perform expand collapse on search matches
    new RegularEvent('input', (event: Event, target: HTMLInputElement): void => {
      const typedQuery = target.value;
      this.search(typedQuery);
    }).delegateTo(currentModal, Identifiers.searchTrigger);

    new RegularEvent('change', (event: Event, target: HTMLInputElement): void => {
      const typedQuery = target.value;
      this.search(typedQuery);
    }).delegateTo(currentModal, Identifiers.searchTrigger);
  }

  private search(typedQuery: string): void {
    this.currentModal.querySelectorAll(Identifiers.item).forEach((element: HTMLElement): void => {
      if (element.textContent.toLowerCase().trim().includes(typedQuery.toLowerCase())) {
        element.classList.remove('hidden');
        element.classList.add('searchhit');
      } else {
        element.classList.remove('searchhit');
        element.classList.add('hidden');
      }
    });

    this.currentModal.querySelectorAll('.searchhit').forEach((resultElement: HTMLElement) => {
      const collapseElement = resultElement.closest('.panel-collapse');
      Collapse.getOrCreateInstance(collapseElement).show();
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('localConfigurationGetContent')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponseWithButtons = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
            this.searchInput = modalContent.querySelector<HTMLInputElement>((Identifiers.searchTrigger));
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

    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.localConfigurationWriteToken;
    const configurationValues: Record<string, string> = {};
    this.currentModal.querySelectorAll('.t3js-localConfiguration-pathValue').forEach((element: HTMLInputElement): void => {
      if (element.type === 'checkbox') {
        if (element.checked) {
          configurationValues[element.dataset.path] = '1';
        } else {
          configurationValues[element.dataset.path] = '0';
        }
      } else {
        configurationValues[element.dataset.path] = element.value;
      }
    });
    (new AjaxRequest(Router.getUrl())).post({
      install: {
        action: 'localConfigurationWrite',
        token: executeToken,
        configurationValues: configurationValues,
      },
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data: LocalConfigurationWrittenResponse = await response.resolve();
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
