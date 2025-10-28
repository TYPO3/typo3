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
  searchTrigger = '.t3js-localConfiguration-search',
  createRowTrigger = '.t3js-localConfiguration-createRow',
  removeRowTrigger = '.t3js-localConfiguration-removeRow',
  configurationItem = '.t3js-localConfiguration-configuration-item',
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
  private toggleAll: boolean = false;

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
      this.toggleAll = !this.toggleAll;
      const action = this.toggleAll ? 'show' : 'hide';
      panels.forEach((panel: HTMLElement) => {
        const toggleButton: HTMLElement = modalContent.querySelector(`[data-bs-target="#${panel.id}"]`);
        if (toggleButton) {
          toggleButton.classList.toggle('collapsed', !this.toggleAll);
        }
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
        if (searchInput.value.trim() !== '') {
          // Clear search on ESC key otherwise close the modal
          event.preventDefault();
          searchInput.value = '';
          searchInput.focus();
        }
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

    // Remove a cloned row
    new RegularEvent('click', (event: Event, target: HTMLInputElement): void => {
      event.preventDefault();
      const row = target.closest(Identifiers.configurationItem);
      row?.remove();
    }).delegateTo(currentModal, Identifiers.removeRowTrigger);

    // Add a fresh clone row
    new RegularEvent('click', (event: Event, target: HTMLInputElement): void => {
      event.preventDefault();

      const container = target.closest('.t3js-configuration-list') as HTMLElement|null;
      if (container === null) {
        return;
      }

      const querySelectorLast = (selector: string): HTMLElement|null => {
        const elements = container.querySelectorAll(selector);
        return elements.item(elements.length - 1) as HTMLElement|null;
      };

      // Iterate all rows to find empty inputs per row
      const rows = container.querySelectorAll(Identifiers.configurationItem);
      for (const row of rows) {
        const inputs = row.querySelectorAll('input[type="text"]') as NodeListOf<HTMLInputElement>;
        let arrayKey;
        let arrayValue;
        if (container.dataset.type === 'map') {
          arrayKey = inputs.item(0).value.trim();
          arrayValue = inputs.item(1).value.trim();
        } else if (container.dataset.type === 'element-list') {
          arrayKey = undefined;
          arrayValue = inputs[0].value.trim();
        } else {
          return;
        }

        // Skip if map is lacking key/value, or element-list is lacking value
        // @todo: this doesn't allow empty values when using `map` – on purpose?
        if (arrayKey === '' || arrayValue === '') {
          for (const input of inputs) {
            if (input.value === '') {
              // Found an empty input, focus it
              input.focus();
              return;
            }
          }
        }
      }

      const rowTemplate = (container.querySelector('#' + container.dataset.namespace) as HTMLTemplateElement).content.cloneNode(true) as DocumentFragment;
      container.querySelector('.configuration-map-item-collection').append(rowTemplate);

      const lastRow = querySelectorLast(Identifiers.configurationItem);
      // Focus first input field in inserted item
      (lastRow.querySelector('input[type="text"]') as HTMLInputElement|null)?.focus();
    }).delegateTo(currentModal, Identifiers.createRowTrigger);
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
    const configurationValues: Record<string, string | Record<string, string>> = {};
    const collectedArrayKeys: Record<string, string[]> = {};
    const collectedArrayValues: Record<string, string[]> = {};
    this.currentModal.querySelectorAll('.t3js-localConfiguration-pathValue').forEach((element: HTMLInputElement): void => {
      if (element.type === 'checkbox') {
        if (element.checked) {
          configurationValues[element.dataset.path] = '1';
        } else {
          configurationValues[element.dataset.path] = '0';
        }
      } else {
        if (element.dataset.valuetype === 'map' || element.dataset.valuetype === 'element-list') {
          // Special type "data-valuetype='map|element-list'" found.
          // "map": has 'speaking' key and a value
          // "element-list": only the value counts, the key is just a running numerical index
          // Note that "array" is a regular input string value that is later exploded and NOT part
          // of this code fork. The type "list" is a string-only listing NOT getting exploded.
          // We want to convert these pairs:
          // <input type="text" name="/GFX/someKey/key[]" value="myKey">
          // <input type="text" name="/GFX/someKey/value[]" value="myValue">
          // <input type="text" name="/GFX/someKey/key[]" value="anotherKey">
          // <input type="text" name="/GFX/someKey/value[]" value="anotherValue">
          // into:
          // configurationValues[GFX/someKey][myKey] = myValue
          // configurationValues[GFX/someKey][anotherKey] = anotherValue
          // This is done with a temporary helper structure of collectedArrayKeys+collectedArrayValues,
          // indexed by their main key ("GFX/someKey") so they can later be easily piped into it as JavaScript array.

          // The "value[]" portion is always required (for map+list), but "key[]" can be optional (only needed for map)
          if (element.dataset.path!.includes('/key[]') && element.value !== '') {
            const itemArrayPath = element.dataset.path!.replace('/key[]', '');
            if (collectedArrayKeys[itemArrayPath] === undefined) {
              collectedArrayKeys[itemArrayPath] = [];
            }
            collectedArrayKeys[itemArrayPath].push(element.value);
          } else if (element.dataset.path!.includes('/value[]') && element.value !== '') {
            const itemArrayPath = element.dataset.path!.replace('/value[]', '');
            if (collectedArrayValues[itemArrayPath] === undefined) {
              collectedArrayValues[itemArrayPath] = [];
            }
            collectedArrayValues[itemArrayPath].push(element.value);
          }
        } else {
          // Regular input string values.
          configurationValues[element.dataset.path] = element.value;
        }
      }
    });

    // Now iterate the collectedArrayValues and collectedArrayKeys (map needs key+value, list only values).
    for (const itemArrayPath in collectedArrayValues) {
      if (Object.prototype.hasOwnProperty.call(collectedArrayValues, itemArrayPath)) {
        // Create a properly typed object for the collection
        if (configurationValues[itemArrayPath] === undefined) {
          configurationValues[itemArrayPath] = {};
        }

        // Ensure configurationValues[itemArrayPath] is treated as a Record.
        // Record keys are sorted, and configuration values are also persisted with sorting.
        const configObject = configurationValues[itemArrayPath] as Record<string, string>;

        // Iterate by index, not by keys array values
        collectedArrayValues[itemArrayPath].forEach((arrayValue, index) => {
          // Ensure we really have that paired collectedArrayValues[] value corresponding to the same key for maps.
          // Empty key+value pairs are skipped (removed from config).
          // Also, element-list is evaluated for the cases collectedArrayKeys is not set.
          if (arrayValue === '') {
            return;
          }

          if (collectedArrayKeys[itemArrayPath]
                && collectedArrayKeys[itemArrayPath][index] !== undefined
                && collectedArrayKeys[itemArrayPath][index] !== ''
          ) {
            // Case "map"
            // Now populate configurationValues[itemArrayPath][myKey] = myValue
            const arrayKey = collectedArrayKeys[itemArrayPath][index];
            configObject[arrayKey] = arrayValue;
          } else {
            // Case "list"
            // Now populate configurationValues[itemArrayPath][numericalKey] = myValue
            configObject[index] = arrayValue;
          }
        });
      }
    }

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
