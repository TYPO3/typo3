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
  cloneRowTrigger = '.t3js-localConfiguration-cloneRow',
  removeRowTrigger = '.t3js-localConfiguration-removeRow',
  arrayRowTrigger = '.t3js-localConfiguration-array-clone'
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

    // Remove a cloned row
    new RegularEvent('click', (event: Event, target: HTMLInputElement): void => {
      event.preventDefault();
      const row = target.closest(Identifiers.arrayRowTrigger);
      if (row) {
        row.parentNode?.removeChild(row);
      }
    }).delegateTo(currentModal, Identifiers.removeRowTrigger);

    // Add a fresh clone row
    new RegularEvent('click', (event: Event, target: HTMLInputElement): void => {

      event.preventDefault();
      const row = target.closest(Identifiers.arrayRowTrigger) as HTMLTableRowElement;
      if (!row) {
        return;
      }

      // Get input values from the original row
      const inputs = Array.from(row.querySelectorAll('input'));

      let arrayKey: string;
      let arrayValue: string;

      if (row.dataset.valuetype === 'map') {
        arrayKey = inputs[0].value.trim();
        arrayValue = inputs[1].value.trim();
      } else if (row.dataset.valuetype === 'element-list') {
        arrayValue = inputs[0].value.trim();
        arrayKey = 'empty';
      } else {
        return;
      }

      // Skip if map is lacking key/value, or element-list is lacking value
      if (!arrayKey || !arrayValue) {
        row.style.animation = 'record-pulse 0.5s ease-in-out 5';
        setTimeout(() => {
          row.style.animation = '';
        }, 2500);
        return;
      }

      // Insert the cloned row before the template row
      const clonedRow = row.cloneNode(true) as HTMLTableRowElement;
      row.parentNode?.insertBefore(clonedRow, row);

      // Clear input values in the template row (original row)
      inputs.forEach(input => {
        input.value = '';
      });

      // Replace clone button with remove button
      const buttonCell = clonedRow.querySelector(Identifiers.cloneRowTrigger);
      if (buttonCell) {
        buttonCell.classList.add('d-none');
        clonedRow.querySelector(Identifiers.removeRowTrigger)?.classList.remove('d-none');
      }
    }).delegateTo(currentModal, Identifiers.cloneRowTrigger);
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
