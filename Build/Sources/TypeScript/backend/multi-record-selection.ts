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

import Notification from '@typo3/backend/notification';
import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import {ActionConfiguration, ActionEventDetails} from '@typo3/backend/multi-record-selection-action';

enum Selectors {
  actionsSelector = '.t3js-multi-record-selection-actions',
  checkboxSelector = '.t3js-multi-record-selection-check',
  checkboxActionsSelector = '.t3js-multi-record-selection-check-actions',
  checkboxActionsToggleSelector = '.t3js-multi-record-selection-check-actions-toggle',
  rowSelectionSelector = '[data-multi-record-selection-row-selection] tr'
}

enum Buttons {
  actionButton = 'button[data-multi-record-selection-action]',
  checkboxActionButton = 'button[data-multi-record-selection-check-action]',
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

/**
 * Module: @typo3/backend/multi-record-selection
 */
class MultiRecordSelection {
  private lastChecked: HTMLInputElement = null;

  private static getCheckboxes(state: CheckboxState = CheckboxState.any, identifier: string = ''): NodeListOf<HTMLInputElement> {
    return document.querySelectorAll(MultiRecordSelection.getCombinedSelector(Selectors.checkboxSelector + state, identifier));
  }

  private static getCombinedSelector(selector: string, identifier: string): string {
    return identifier !== '' ? ['[data-multi-record-selection-identifier="' + identifier + '"]',  selector].join (' ') : selector;
  }

  private static getIdentifier(element: HTMLElement): string {
    return (element.closest('[data-multi-record-selection-identifier]') as HTMLElement)?.dataset.multiRecordSelectionIdentifier || '';
  }

  private static changeCheckboxState(checkbox: HTMLInputElement, check: boolean): void {
    if (checkbox.checked === check || checkbox.dataset.manuallyChanged) {
      // Return in case state did not change or another component has already changed it
      return;
    }
    checkbox.checked = check;
    checkbox.dispatchEvent(new CustomEvent('multiRecordSelection:checkbox:state:changed',{
      detail: { identifier: MultiRecordSelection.getIdentifier(checkbox) }, bubbles: true, cancelable: false
    }));
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
    // Highlight each checked checkbox and toggle the corresponding actions. Since
    // the evaluation for toggling actions does a lot of things, we don't want to
    // perform this for every checked checkbox. Therefore we store the identifiers,
    // which were already evaluated and do not call the evaluation for them again.
    let actionsToggled: boolean = false;
    let identifiers: Array<string> = [];
    checked.forEach((checkbox: HTMLInputElement) => {
      checkbox.closest('tr').classList.add('success');
      const identifier: string = MultiRecordSelection.getIdentifier(checkbox);
      if (identifier !== '' && !identifiers.includes(identifier)) {
        identifiers.push(identifier);
        actionsToggled = true;
        MultiRecordSelection.toggleActionsState(identifier);
      }
    });
    // If none of the checked checkboxes contain an identifier, call the toggling one time anyways.
    if (!actionsToggled) {
      MultiRecordSelection.toggleActionsState();
    }
  }

  /**
   * Toggles the state of the actions, depending on the
   * currently selected elements and their nature.
   */
  private static toggleActionsState(identifier: string = ''): void {
    const actionContainers: NodeListOf<HTMLElement> = document.querySelectorAll(
      MultiRecordSelection.getCombinedSelector(Selectors.actionsSelector, identifier)
    );

    if (!actionContainers.length) {
      // Early return in case no action containers are defined
      return;
    }

    if (!MultiRecordSelection.getCheckboxes(CheckboxState.checked, identifier).length) {
      // In case no checkbox is checked, hide all action containers and return
      actionContainers.forEach((container: HTMLElement): void => MultiRecordSelection.changeActionContainerVisibility(container, false));
      return;
    }

    // Remove hidden state of all action containers, since checked checkboxes exist
    actionContainers.forEach((container: HTMLElement): void => MultiRecordSelection.changeActionContainerVisibility(container));

    const actions: NodeListOf<HTMLButtonElement> = document.querySelectorAll(
      [MultiRecordSelection.getCombinedSelector(Selectors.actionsSelector, identifier), Buttons.actionButton].join(' ')
    );

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
      const checked: NodeListOf<HTMLInputElement> = MultiRecordSelection.getCheckboxes(CheckboxState.checked, identifier);
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
   * This primarily just adds/removes the "hidden" class of the container. In case
   * the container is in a panel, it also toggles the other panel heading elements.
   * Note: This only works in case the container is not in the wrapper class, which
   * should only be used for containers, outside of a panel.
   *
   * @param {HTMLElement} container The container to change the visibility for
   * @param {boolean} visible Whether the container should be visible or not
   */
  private static changeActionContainerVisibility(container: HTMLElement, visible: boolean = true): void {
    const panelElements: HTMLCollection = container.closest('.multi-record-selection-panel')?.children;
    if (visible) {
      if (panelElements) {
        for (let i=0; i < panelElements.length; i++) { panelElements[i].classList.add('hidden') }
      }
      container.classList.remove('hidden');
    } else {
      if (panelElements) {
        for (let i=0; i < panelElements.length; i++) { panelElements[i].classList.remove('hidden') }
      }
      container.classList.add('hidden');
    }
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
  private static unsetManuallyChangedAttribute(identifier: string): void {
    MultiRecordSelection.getCheckboxes(CheckboxState.any, identifier).forEach((checkbox: HTMLInputElement): void => {
      checkbox.removeAttribute('data-manually-changed');
    });
  }

  constructor() {
    DocumentService.ready().then((): void => {
      MultiRecordSelection.restoreTemporaryState();
      this.registerActions();
      this.registerActionsEventHandlers();
      this.registerCheckboxActions();
      this.registerCheckboxKeyboardActions();
      this.registerCheckboxTableRowSelectionAction();
      this.registerToggleCheckboxActions();
      this.registerDispatchCheckboxStateChangedEvent();
      this.registerCheckboxStateChangedEventHandler();
    });
  }

  private registerActions(): void {
    new RegularEvent('click', (e: Event, target: HTMLButtonElement): void => {
      if (!target.dataset.multiRecordSelectionAction) {
        // Return if we don't deal with a valid action
      }

      const identifier: string = MultiRecordSelection.getIdentifier(target);
      const configuration: any = JSON.parse((target.dataset.multiRecordSelectionActionConfig || '{}'));
      const checked: NodeListOf<HTMLInputElement> = MultiRecordSelection.getCheckboxes(CheckboxState.checked, identifier);

      if (!checked.length) {
        // Return in case there is currently no element checked to perform the action on.
        return;
      }

      // This component does not implement any specific action itself, but just dispatches
      // an event so the implementing components can react on the triggered action. To decrease
      // selections in those components, most of the information are passed within the custom event.
      // Those are e.g. the checked checkboxes, the instance identifier and the action configuration.
      target.dispatchEvent(new CustomEvent(
        'multiRecordSelection:action:' + target.dataset.multiRecordSelectionAction,
        {
          detail: <ActionEventDetails> { identifier: identifier, checkboxes: checked, configuration: configuration },
          bubbles: true,
          cancelable: false
        }
      ));
    }).delegateTo(document, [Selectors.actionsSelector, Buttons.actionButton].join(' '));
  }

  /**
   * Other components can dispatch the "multiRecordSelection:actions"
   * events to influence the display depending on their custom logic.
   */
  private registerActionsEventHandlers(): void {
    new RegularEvent('multiRecordSelection:actions:show', (e: CustomEvent): void => {
      const identifier: string = e.detail?.identifier || '';
      const actionContainers: NodeListOf<HTMLElement> = document.querySelectorAll(MultiRecordSelection.getCombinedSelector(Selectors.actionsSelector, identifier));
      actionContainers.length && actionContainers.forEach((container: HTMLElement): void => MultiRecordSelection.changeActionContainerVisibility(container));
    }).bindTo(document);
    new RegularEvent('multiRecordSelection:actions:hide', (e: CustomEvent): void => {
      const identifier: string = e.detail?.identifier || '';
      const actionContainers: NodeListOf<HTMLElement> = document.querySelectorAll(MultiRecordSelection.getCombinedSelector(Selectors.actionsSelector, identifier));
      actionContainers.length && actionContainers.forEach((container: HTMLElement): void => MultiRecordSelection.changeActionContainerVisibility(container, false));
    }).bindTo(document);
  }

  private registerCheckboxActions(): void {
    new RegularEvent('click', (e: Event, target: HTMLButtonElement): void => {
      e.preventDefault();

      if (!target.dataset.multiRecordSelectionCheckAction) {
        // Return if we don't deal with a valid action
        return;
      }

      const identifier: string = MultiRecordSelection.getIdentifier(target);
      const checkboxes: NodeListOf<HTMLInputElement> = MultiRecordSelection.getCheckboxes(CheckboxState.any, identifier);

      if (!checkboxes.length) {
        // Return in case there are no checkboxes (elements) to perform the action on.
        return;
      }

      // Unset manually changed attribute so we can be sure, in case this is
      // set on a checkbox, while executing the requested action, the checkbox
      // was already changed by another component.
      MultiRecordSelection.unsetManuallyChangedAttribute(identifier);

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
      MultiRecordSelection.unsetManuallyChangedAttribute(identifier);
    }).delegateTo(document, [Selectors.checkboxActionsSelector, Buttons.checkboxActionButton].join(' '));
  }

  private registerCheckboxKeyboardActions(): void {
    new RegularEvent('click', (e: PointerEvent, target: HTMLInputElement): void => this.handleCheckboxKeyboardActions(e, target))
      .delegateTo(document, Selectors.checkboxSelector);
  }

  private registerCheckboxTableRowSelectionAction(): void {
    new RegularEvent('click', (e: PointerEvent, target: HTMLElement): void => {
      const eventTargetTagName: string = (e.target as HTMLElement).tagName;
      if (eventTargetTagName !== 'TH' && eventTargetTagName !== 'TD') {
        // Only change checkbox state if the target is the row itself
        return;
      }
      const checkbox: HTMLInputElement = target.querySelector(Selectors.checkboxSelector);
      if (checkbox === null) {
        // Return in case the table row does not contain a checkbox, handled by this component
        return;
      }
      // Note: Since we only change the state of one checkbox, we don't have to unset the
      // manually changed flag and also do not need to evaluate any instance identifier.
      MultiRecordSelection.changeCheckboxState(checkbox, !checkbox.checked);
      // After changing the target checkbox state, let's check if a keyboard action
      // should be performed as well. We also prevent the keyboard actions from unsetting
      // any state, e.g. the "manually changed flag", as this might have been set by any
      // component triggered by the above checkbox state change operation.
      this.handleCheckboxKeyboardActions(e, checkbox, false)
    }).delegateTo(document, Selectors.rowSelectionSelector);

    // In case row selection is enabled and a keyboard "shortcut" is used, prevent text selection on the rows
    new RegularEvent('mousedown', (e: PointerEvent): void => (e.shiftKey || e.altKey || e.ctrlKey) && e.preventDefault())
      .delegateTo(document, Selectors.rowSelectionSelector);
  }

  private registerDispatchCheckboxStateChangedEvent(): void {
    new RegularEvent('change', (e: Event, target: HTMLInputElement): void => {
      target.dispatchEvent(new CustomEvent('multiRecordSelection:checkbox:state:changed',{
        detail: { identifier: MultiRecordSelection.getIdentifier(target) }, bubbles: true, cancelable: false
      }));
    }).delegateTo(document, Selectors.checkboxSelector);
  }

  private registerCheckboxStateChangedEventHandler(): void {
    new RegularEvent('multiRecordSelection:checkbox:state:changed', (e: CustomEvent): void => {
      const checkbox: HTMLInputElement = <HTMLInputElement>e.target;
      const identifier: string = e.detail?.identifier || '';

      if (checkbox.checked) {
        checkbox.closest('tr').classList.add('success');
      } else {
        checkbox.closest('tr').classList.remove('success');
      }

      // Toggle actions for changed checkbox state
      MultiRecordSelection.toggleActionsState(identifier);
    }).bindTo(document);
  }

  private registerToggleCheckboxActions(): void {
    new RegularEvent('click', (e: Event, target: HTMLButtonElement): void => {
      const identifier: string = MultiRecordSelection.getIdentifier(target);

      const checkAll: HTMLButtonElement = document.querySelector([
        MultiRecordSelection.getCombinedSelector(Selectors.checkboxActionsSelector, identifier),
        'button[data-multi-record-selection-check-action="' + CheckboxActions.checkAll + '"]'
      ].join(' '));

      if (checkAll !== null) {
        checkAll.classList.toggle('disabled', !MultiRecordSelection.getCheckboxes(CheckboxState.unchecked, identifier).length)
      }

      const checkNone: HTMLButtonElement = document.querySelector([
        MultiRecordSelection.getCombinedSelector(Selectors.checkboxActionsSelector, identifier),
        'button[data-multi-record-selection-check-action="' + CheckboxActions.checkNone + '"]'
      ].join(' '));

      if (checkNone !== null) {
        checkNone.classList.toggle('disabled', !MultiRecordSelection.getCheckboxes(CheckboxState.checked, identifier).length);
      }
    }).delegateTo(document, Selectors.checkboxActionsToggleSelector);
  }

  private handleCheckboxKeyboardActions(e: PointerEvent, target: HTMLInputElement, cleanUpState: boolean = true): void {
    const identifier: string = MultiRecordSelection.getIdentifier(target);

    // If lastChecked is not set, does no longer exist in visible DOM (e.g. because the list is paginated
    // and lastChecked is on a prev/next page), is not in the same table as current target (according to
    // the identifier) or no shortcut is used at all, add the current target as lastChecked and return.
    if (!this.lastChecked
      || !document.body.contains(this.lastChecked)
      || MultiRecordSelection.getIdentifier(this.lastChecked) !== identifier
      || (!e.shiftKey && !e.altKey && !e.ctrlKey)
    ) {
      this.lastChecked = target;
      return;
    }

    if (cleanUpState) {
      // In case clean up is *NOT* prevented, unset manually changed attribute.
      // Usually clean up will be prevented by actions, which have already
      // performed checkbox change operations.
      MultiRecordSelection.unsetManuallyChangedAttribute(identifier);
    }

    // With the shift key, it's possible to check / uncheck a range of checkboxes
    if (e.shiftKey) {
      // To easily calculate the start and end position we need checkboxes as an array
      const checkboxes: Array<HTMLInputElement> = Array.from(MultiRecordSelection.getCheckboxes(CheckboxState.any, identifier));
      // The current target is the start position
      const start = checkboxes.indexOf(target);
      // The last manually clicked / checked checkbox is the end
      const end = checkboxes.indexOf(this.lastChecked);
      // Get the checkboxes which should be changed (we use min() and max() to allow ranges in both directions)
      const checkboxesToChange = checkboxes.slice(Math.min(start, end), Math.max(start, end) + 1);
      checkboxesToChange.forEach((checkbox: HTMLInputElement): void => {
        // Change the state of each checkbox in question. Do not change the current target since we
        // use it's current checked state, making both "check all" and "uncheck all" possible.
        if (checkbox !== target) {
          MultiRecordSelection.changeCheckboxState(checkbox, target.checked);
        }
      });
    }

    // We can now store the current target as lastChecked so it can be used in the next run
    this.lastChecked = target;

    // With the alt or ctrl key, it's possible to toggle the current selection
    if (e.altKey || e.ctrlKey) {
      MultiRecordSelection.getCheckboxes(CheckboxState.any, identifier).forEach((checkbox: HTMLInputElement): void => {
        // Toggle all checkboxes except the current target as this was already done by clicking on it
        if (checkbox !== target) {
          MultiRecordSelection.changeCheckboxState(checkbox, !checkbox.checked);
        }
      })
    }

    // To prevent possible side effects we simply clean up and unset the attribute here again
    MultiRecordSelection.unsetManuallyChangedAttribute(identifier);
  }
}

export default new MultiRecordSelection();
