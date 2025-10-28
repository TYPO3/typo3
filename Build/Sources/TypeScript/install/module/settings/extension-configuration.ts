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
import '../../renderable/clearable';
import '../../renderable/wrap-group';
import '../../renderable/offset-group';
import { AbstractInteractableModule, type ModuleLoadedResponse } from '../abstract-interactable-module';
import ModuleMenu from '@typo3/backend/module-menu';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import type MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import { Collapse } from 'bootstrap';
import DebounceEvent from '@typo3/core/event/debounce-event';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';

enum Identifiers {
  formListener = '.t3js-extensionConfiguration-form',
  searchInput = '.t3js-extensionConfiguration-search'
}

type ExtensionConfigurationWrittenResponse = {
  status: MessageInterface[],
  success: boolean,
};

/**
 * Module: @typo3/install/module/extension-configuration
 */
class ExtensionConfiguration extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getContent();

    // Focus search field on certain user interactions
    new RegularEvent('keydown', (event: KeyboardEvent) => {
      const searchInput = currentModal.querySelector<HTMLInputElement>(Identifiers.searchInput);
      if (event.ctrlKey || event.metaKey) {
        // Focus search field on ctrl-f
        if (event.key === 'f' || event.key === 'F') {
          event.preventDefault();
          searchInput.focus();
        }
      } else if (event.key === KeyTypesEnum.ESCAPE) {
        if (searchInput.value.trim() !== '') {
          // Clear search on ESC key otherwise close the modal
          event.preventDefault();
          searchInput.value = '';
          searchInput.focus();
        }
      }
    }).bindTo(currentModal);

    // Perform expand collapse on search matches
    new DebounceEvent('input', (event: Event, target: HTMLInputElement): void => {
      const typedQuery = target.value;
      this.search(typedQuery);
    }, 100).delegateTo(currentModal, Identifiers.searchInput);

    new RegularEvent('change', (event: Event, target: HTMLInputElement): void => {
      const typedQuery = target.value;
      this.search(typedQuery);
    }).delegateTo(currentModal, Identifiers.searchInput);

    new RegularEvent('submit', (event: Event, target: HTMLFormElement): void => {
      event.preventDefault();
      this.write(target);
    }).delegateTo(currentModal, Identifiers.formListener);
  }

  private search(typedQuery: string): void {
    this.currentModal.querySelectorAll('.search-item').forEach((element: Element): void => {
      if (typedQuery === '' || element.textContent.toLowerCase().trim().includes(typedQuery.toLowerCase())) {
        element.classList.add('searchhit');
        element.classList.remove('hidden');
      } else {
        element.classList.remove('searchhit');
        element.classList.add('hidden');
      }
    });
    this.currentModal.querySelectorAll('.searchhit').forEach((resultElement: HTMLElement) => {
      Collapse.getOrCreateInstance(resultElement).show();
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('extensionConfigurationGetContent')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponse = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            (modalContent.querySelector(Identifiers.searchInput) as HTMLInputElement).clearable();
            this.initializeWrap();
            this.initializeColorPicker();
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private initializeColorPicker(): void {
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      topLevelModuleImport('@typo3/backend/color-picker.js');
    } else {
      import('@typo3/backend/color-picker');
    }
  }

  /**
   * Submit the form and show the result message
   */
  private write(form: HTMLFormElement): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.extensionConfigurationWriteToken;
    const extensionConfiguration: Record<string, string> = {};
    for (const [name, value] of new FormData(form)) {
      extensionConfiguration[name] = value.toString();
    }

    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          token: executeToken,
          action: 'extensionConfigurationWrite',
          extensionKey: form.dataset.extensionKey,
          extensionConfiguration: extensionConfiguration,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ExtensionConfigurationWrittenResponse = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              Notification.showMessage(element.title, element.message, element.severity);
            });
            if (document.body.dataset.context === 'backend') {
              ModuleMenu.App.refreshMenu();
            }
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
   * configuration properties
   */
  private initializeWrap(): void {
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      topLevelModuleImport('@typo3/install/renderable/wrap-group.js');
      topLevelModuleImport('@typo3/install/renderable/offset-group.js');
    }

    this.currentModal.querySelectorAll('.t3js-emconf-offset').forEach((element: HTMLInputElement): void => {
      const parent = element.parentElement;

      element.setAttribute('data-offsetfield-x', '#' + element.id + '_offset_x');
      element.setAttribute('data-offsetfield-y', '#' + element.id + '_offset_y');
      element.classList.add('hidden');

      const offsetGroup = parent.ownerDocument.createElement('typo3-install-offset-group');
      offsetGroup.offsetId = element.id;
      offsetGroup.values = element.value.split(',');

      parent.appendChild(offsetGroup);

      parent.querySelectorAll('.t3js-emconf-offsetfield').forEach((offsetField: HTMLInputElement) => {
        new RegularEvent('keyup', (event: KeyboardEvent) => {
          const target = parent.querySelector<HTMLInputElement>((event.currentTarget as HTMLElement).dataset.target);
          target.value = parent.querySelector<HTMLInputElement>(target.dataset.offsetfieldX).value + ',' + parent.querySelector<HTMLInputElement>(target.dataset.offsetfieldY).value;
        }).bindTo(offsetField);
      });
    });

    this.currentModal.querySelectorAll('.t3js-emconf-wrap').forEach((element: HTMLInputElement): void => {
      const parent = element.parentElement;

      element.setAttribute('data-wrapfield-start', '#' + element.id + '_wrap_start');
      element.setAttribute('data-wrapfield-end', '#' + element.id + '_wrap_end');
      element.classList.add('hidden');

      const offsetGroup = parent.ownerDocument.createElement('typo3-install-wrap-group');
      offsetGroup.wrapId = element.id;
      offsetGroup.values = element.value.split('|');

      parent.appendChild(offsetGroup);

      parent.querySelectorAll('.t3js-emconf-wrapfield').forEach((wrapField: HTMLInputElement) => {
        new RegularEvent('keyup', (event: KeyboardEvent) => {
          const target = parent.querySelector<HTMLInputElement>((event.currentTarget as HTMLElement).dataset.target);
          target.value = parent.querySelector<HTMLInputElement>(target.dataset.wrapfieldStart).value + '|' + parent.querySelector<HTMLInputElement>(target.dataset.wrapfieldEnd).value;
        }).bindTo(wrapField);
      });
    });
  }
}

export default new ExtensionConfiguration();
