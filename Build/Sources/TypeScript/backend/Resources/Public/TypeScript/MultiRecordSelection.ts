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

import Notification = require('TYPO3/CMS/Backend/Notification');
import DocumentService = require('TYPO3/CMS/Core/DocumentService');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

enum Selectors {
  actionsSelector = '.t3js-multi-record-selection-actions',
  checkboxSelector = '.t3js-multi-record-selection-check',
  checkboxActionsSelector = '#multi-record-selection-check-actions',
}

enum Buttons {
  actionButton = 'button[data-multi-record-selection-action]',
  checkboxActionButton = 'button[data-multi-record-selection-check-action]',
  checkboxActionsToggleButton = 'button[data-bs-target="multi-record-selection-check-actions"]'
}

enum Actions {
  edit = 'edit'
}

enum CheckboxActions {
  checkAll = 'check-all',
  checkNone = 'check-none',
  toggle = 'toggle'
}

enum CheckboxState {
  any = '',
  checked = ':checked',
  unchecked = ':not(:checked)'
}

interface ActionConfiguration {
  idField: string;
}

interface EditActionConfiguration extends ActionConfiguration{
  table: string;
  returnUrl: string;
}

/**
 * Module: TYPO3/CMS/Backend/MultiRecordSelection
 */
class MultiRecordSelection {
  private static getCheckboxes(state: CheckboxState = CheckboxState.any): NodeListOf<HTMLInputElement> {
    return document.querySelectorAll(Selectors.checkboxSelector + state);
  }

  private static changeCheckboxState(checkbox: HTMLInputElement, check: boolean): void {
    if (checkbox.checked === check || checkbox.dataset.manuallyChanged) {
      // Return in case state did not change or another component has already changed it
      return;
    }
    checkbox.checked = check;
    checkbox.dispatchEvent(new Event('checkbox:state:changed', {bubbles: true, cancelable: false}));
  }

  private static getReturnUrl(returnUrl: string): string {
    if (returnUrl === '') {
      returnUrl = top.list_frame.document.location.pathname + top.list_frame.document.location.search;
    }
    return encodeURIComponent(returnUrl);
  }

  /**
   * This restores (initializes) a temporary state, which is required in case
   * the user returns to the listing using the browsers' history back feature,
   * which will not result in a new request.
   */
  private static restoreTemporaryState(): void {
    const checked: NodeListOf<HTMLInputElement> = MultiRecordSelection.getCheckboxes(CheckboxState.checked);
    // In case nothing is checked we don't have to do anything here
    if (!checked.length) {
      return;
    }
    checked.forEach((checkbox: HTMLInputElement) => {
      checkbox.closest('tr').classList.add('success');
    });
    const actionContainers: NodeListOf<HTMLElement> = document.querySelectorAll(Selectors.actionsSelector);
    actionContainers.length && actionContainers.forEach((container: HTMLElement): void => container.classList.remove('hidden'));
  }

  /**
   * Toggles the state of the actions, depending on the
   * currently selected elements and their nature.
   */
  private static toggleActionsState(): void {
    const actionContainers: NodeListOf<HTMLElement> = document.querySelectorAll(Selectors.actionsSelector);
    if (!actionContainers.length) {
      // Early return in case no action containers are defined
      return;
    }

    if (!MultiRecordSelection.getCheckboxes(CheckboxState.checked).length) {
      // In case no checkbox is checked, hide all action containers and return
      actionContainers.forEach((container: HTMLElement): void => container.classList.add('hidden'));
      return;
    }

    // Remove hidden state of all action containers, since checked checkboxes exist
    actionContainers.forEach((container: HTMLElement): void => container.classList.remove('hidden'));

    const actions: NodeListOf<HTMLButtonElement> = document.querySelectorAll([Selectors.actionsSelector, Buttons.actionButton].join(' '));
    if (!actions.length) {
      // Early return in case no action is defined
      return;
    }

    actions.forEach((action: HTMLButtonElement): void => {
      if (!action.dataset.multiRecordSelectionActionConfig) {
        // In case the action does not define any configuration, no toggling is possible
        return;
      }
      const configuration: ActionConfiguration = JSON.parse(action.dataset.multiRecordSelectionActionConfig);
      if (!configuration.idField) {
        // Return in case the idField (where to find the id on selected elements) is not defined
        return;
      }
      // Start the evaluation by disabling the action
      action.classList.add('disabled');
      // Get all currently checked elements
      const checked: NodeListOf<HTMLInputElement> = MultiRecordSelection.getCheckboxes(CheckboxState.checked);
      for (let i=0; i < checked.length; i++) {
        // Evaluate each checked element if it contains the specified idField
        if (checked[i].closest('tr').dataset[configuration.idField]) {
          // If a checked element contains the idField, remove the "disabled"
          // state and end the search since the action can be performed.
          action.classList.remove('disabled');
          break;
        }
      }
    });
  }

  /**
   * The manually changed attribute can be set by components, using
   * this module while implementing custom logic to change checkbox
   * state. To not cancel each others action, all actions in this
   * module respect this attribute before changing checkbox state.
   * Therefore, this method is called prior to every action in
   * this module, which changes checkbox states. Otherwise old
   * state would may led to misbehaviour.
   */
  private static unsetManuallyChangedAttribute(): void {
    MultiRecordSelection.getCheckboxes().forEach((checkbox: HTMLInputElement): void => {
      checkbox.removeAttribute('data-manually-changed');
    });
  }

  constructor() {
    DocumentService.ready().then((): void => {
      MultiRecordSelection.restoreTemporaryState();
      this.registerActions();
      this.registerActionsEventHandlers();
      this.registerCheckboxActions();
      this.registerToggleCheckboxActions();
      this.registerDispatchCheckboxStateChangedEvent();
      this.registerCheckboxStateChangedEventHandler();
    });
  }

  private registerActions(): void {
    new RegularEvent('click', (e: Event, target: HTMLButtonElement): void => {
      const checked: NodeListOf<HTMLInputElement> = MultiRecordSelection.getCheckboxes(CheckboxState.checked);

      if (!target.dataset.multiRecordSelectionAction || !checked.length) {
        // Return if we don't deal with a valid action or in case there is
        // currently no element checked to perform the action on.
        return;
      }

      // Perform requested action
      switch (target.dataset.multiRecordSelectionAction) {
        case Actions.edit:
          e.preventDefault();
          const configuration: EditActionConfiguration = JSON.parse(target.dataset.multiRecordSelectionActionConfig || '');
          if (!configuration || !configuration.idField || !configuration.table) {
            break;
          }
          const list: Array<string> = [];
          checked.forEach((checkbox: HTMLInputElement) => {
            const checkboxContainer: HTMLElement = checkbox.closest('tr');
            if (checkboxContainer !== null && checkboxContainer.dataset[configuration.idField]) {
              list.push(checkboxContainer.dataset[configuration.idField]);
            }
          });
          if (list.length) {
            window.location.href = top.TYPO3.settings.FormEngine.moduleUrl
              + '&edit[' + configuration.table + '][' + list.join(',') + ']=edit'
              + '&returnUrl=' + MultiRecordSelection.getReturnUrl(configuration.returnUrl || '');
          } else {
            Notification.warning('The selected elements can not be edited.');
          }
          break;
        default:
          // Not all actions are handled here. Therefore we simply skip them and just
          // dispatch an event so those components can react on the triggered action.
          target.dispatchEvent(new Event('multiRecordSelection:action:' + target.dataset.multiRecordSelectionAction, {bubbles: true, cancelable: false}));
          break;
      }
    }).delegateTo(document, [Selectors.actionsSelector, Buttons.actionButton].join(' '));

    // After registering the event, toggle their state
    MultiRecordSelection.toggleActionsState();
  }

  /**
   * Other components can dispatch the "multiRecordSelection:actions"
   * events to influence the display depending on their custom logic.
   */
  private registerActionsEventHandlers(): void {
    new RegularEvent('multiRecordSelection:actions:show', (): void => {
      const actionContainers: NodeListOf<HTMLElement> = document.querySelectorAll(Selectors.actionsSelector);
      actionContainers && actionContainers.forEach((container: HTMLElement): void => container.classList.remove('hidden'));
    }).bindTo(document);
    new RegularEvent('multiRecordSelection:actions:hide', (): void => {
      const actionContainers: NodeListOf<HTMLElement> = document.querySelectorAll(Selectors.actionsSelector);
      actionContainers && actionContainers.forEach((container: HTMLElement): void => container.classList.add('hidden'));
    }).bindTo(document);
  }

  private registerCheckboxActions(): void {
    new RegularEvent('click', (e: Event, target: HTMLButtonElement): void => {
      e.preventDefault();

      const checkboxes: NodeListOf<HTMLInputElement> = MultiRecordSelection.getCheckboxes();
      if (!target.dataset.multiRecordSelectionCheckAction || !checkboxes.length) {
        // Return if we don't deal with a valid action or in case there
        // are no checkboxes (elements) to perform the action on.
        return;
      }

      // Unset manually changed attribute so we can be sure, in case this is
      // set on a checkbox, while executing the requested action, the checkbox
      // was already changed by another component.
      MultiRecordSelection.unsetManuallyChangedAttribute();

      // Perform requested action
      switch (target.dataset.multiRecordSelectionCheckAction) {
        case CheckboxActions.checkAll:
          checkboxes.forEach((checkbox: HTMLInputElement) => {
            MultiRecordSelection.changeCheckboxState(checkbox, true);
          });
          break;
        case CheckboxActions.checkNone:
          checkboxes.forEach((checkbox: HTMLInputElement) => {
            MultiRecordSelection.changeCheckboxState(checkbox, false);
          });
          break;
        case CheckboxActions.toggle:
          checkboxes.forEach((checkbox: HTMLInputElement) => {
            MultiRecordSelection.changeCheckboxState(checkbox, !checkbox.checked);
          });
          break;
        default:
          // Unknown action
          Notification.warning('Unknown checkbox action');
      }

      // To prevent possible side effects we simply clean up and unset the attribute here again
      MultiRecordSelection.unsetManuallyChangedAttribute();
    }).delegateTo(document, [Selectors.checkboxActionsSelector, Buttons.checkboxActionButton].join(' '));
  }

  private registerDispatchCheckboxStateChangedEvent(): void {
    new RegularEvent('change', (e: Event, target: HTMLInputElement): void => {
      target.dispatchEvent(new Event('checkbox:state:changed', {bubbles: true, cancelable: false}));
    }).delegateTo(document, Selectors.checkboxSelector);
  }

  private registerCheckboxStateChangedEventHandler(): void {
    new RegularEvent('checkbox:state:changed', (e: Event): void => {
      const checkbox: HTMLInputElement = <HTMLInputElement>e.target;
      if (checkbox.checked) {
        checkbox.closest('tr').classList.add('success');
      } else {
        checkbox.closest('tr').classList.remove('success');
      }

      // Toggle actions for changed checkbox state
      MultiRecordSelection.toggleActionsState();
    }).bindTo(document);
  }

  private registerToggleCheckboxActions(): void {
    new RegularEvent('click', (): void => {
      const checkAll: HTMLButtonElement = document.querySelector('button[data-multi-record-selection-check-action="' + CheckboxActions.checkAll + '"]');
      if (checkAll !== null) {
        checkAll.classList.toggle('disabled', !MultiRecordSelection.getCheckboxes(CheckboxState.unchecked).length)
      }

      const checkNone: HTMLButtonElement = document.querySelector('button[data-multi-record-selection-check-action="' + CheckboxActions.checkNone + '"]');
      if (checkNone !== null) {
        checkNone.classList.toggle('disabled', !MultiRecordSelection.getCheckboxes(CheckboxState.checked).length);
      }
    }).delegateTo(document, Buttons.checkboxActionsToggleButton);
  }
}

export = new MultiRecordSelection();
