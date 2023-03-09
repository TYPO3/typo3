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

import documentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';

type HTMLFormChildElement = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

/**
 * Module: @typo3/backend/global-event-handler
 *
 * + `data-global-event="change"`
 *   + `data-action-submit="..."` submits form data
 *     + `$form` parent form element of current element is submitted
 *     + `<any CSS selector>` queried element is submitted (if implementing HTMLFormElement)
 *   + `data-action-navigate="..."` navigates to URL
 *     + `$value` URL taken from value of current element
 *     + `$data` URL taken from `data-navigate-value`
 *     + `$data=~s/$value/` URL taken from `data-navigate-value`,
 *        substituting literal `${value}` and `$[value]` of current element
 * + `data-global-event="submit"`
 *   + `data-value-selector`="..."` retrieves `$value` from corresponding CSS selector
 *   + `data-action-navigate="..."` navigates to URL
 *     + `$form=~s/$value/` URL taken from `form[action]`,
 *        substituting literal `${value}` and `$[value]` taken from `data-value-selector`
 * + `data-global-event="click"`
 *   + `data-action-focus="..."` focus form field
 *   + `data-action-submit="..."` submits form data
 *     + `$form` parent form element of current element is submitted
 *     + `<any CSS selector>` queried element is submitted (if implementing HTMLFormElement)
 *   + `data-submit-values="{&quot;key&quot;:&quot;value&quot;}"` JSON encoded object (key/value pairs)
 *
 * @example
 * <form action="..." id="...">
 *   <input name="name" value="value" data-global-event="change" data-action-submit="$form">
 * </form>
 */
class GlobalEventHandler {
  private options = {
    onChangeSelector: '[data-global-event="change"]',
    onClickSelector: '[data-global-event="click"]',
    onSubmitSelector: 'form[data-global-event="submit"]',
  };

  constructor() {
    documentService.ready().then((): void => this.registerEvents());
  }

  private registerEvents(): void {
    new RegularEvent('change', this.handleChangeEvent.bind(this))
      .delegateTo(document, this.options.onChangeSelector);
    new RegularEvent('click', this.handleClickEvent.bind(this))
      .delegateTo(document, this.options.onClickSelector);
    new RegularEvent('submit', this.handleSubmitEvent.bind(this))
      .delegateTo(document, this.options.onSubmitSelector);
  }

  private handleChangeEvent(evt: Event, resolvedTarget: HTMLElement): void {
    evt.preventDefault();
    this.handleFormChildAction(evt, resolvedTarget)
      || this.handleFormChildNavigateAction(evt, resolvedTarget);
  }

  private handleClickEvent(evt: Event, resolvedTarget: HTMLElement): void {
    evt.preventDefault();
    this.handleFormChildAction(evt, resolvedTarget);
  }

  private handleSubmitEvent(evt: Event, resolvedTarget: HTMLFormElement): void {
    evt.preventDefault();
    this.handleFormNavigateAction(evt, resolvedTarget);
  }

  private handleFormChildAction(evt: Event, resolvedTarget: HTMLElement): boolean {
    const actionSubmit: string = resolvedTarget.dataset.actionSubmit;
    const actionFocus: string = resolvedTarget.dataset.actionFocus;
    if (!actionSubmit && !actionFocus) {
      return false;
    }

    let form: HTMLFormElement = null;
    const parentForm = resolvedTarget.closest('form');

    if (actionSubmit) {
      const formCandidate = actionSubmit !== '$form' ? document.querySelector(actionSubmit) : null;

      // @example `data-action-submit="$form"`
      if (actionSubmit === '$form' && this.isHTMLFormChildElement(resolvedTarget)) {
        form = (resolvedTarget as HTMLFormChildElement).form;
      } else if (actionSubmit === '$form' && parentForm) {
        form = parentForm;
        // @example `data-action-submit="form#identifier"`
      } else if (formCandidate instanceof HTMLFormElement) {
        form = formCandidate;
      }
      if (!(form instanceof HTMLFormElement)) {
        return false;
      }
      this.assignFormValues(form, resolvedTarget);
      form.submit();
    }

    if (actionFocus && parentForm) {
      if (!(parentForm instanceof HTMLFormElement)) {
        return false;
      }

      const formFieldElement: HTMLElement|null = parentForm.querySelector(actionFocus);
      if (formFieldElement === null) {
        return false;
      }

      formFieldElement.focus();
    }
    return true;
  }

  private assignFormValues(form: HTMLFormElement, resolvedTarget: HTMLElement): boolean {
    const formValuesJson = resolvedTarget.dataset.formValues;
    const formValues = formValuesJson ? JSON.parse(formValuesJson) : null;
    if (formValues === null || !(formValues instanceof Object)) {
      return false;
    }
    // assign optional key/value pairs from `data-submit-values="{&quot;key&quot;:&quot;value&quot;}"`
    Object.entries(formValues).forEach(([name, value]) => {
      let item = form.querySelector('[name=' + CSS.escape(name) + ']');
      if (item instanceof HTMLElement) {
        this.assignHTMLFormChildElementValue(item as HTMLElement, value.toString());
      } else {
        item = document.createElement('input');
        item.setAttribute('type', 'hidden');
        item.setAttribute('name', name);
        item.setAttribute('value', value.toString());
        form.appendChild(item);
      }
    });
    return true;
  }

  private handleFormChildNavigateAction(evt: Event, resolvedTarget: HTMLElement): boolean {
    const actionNavigate: string = resolvedTarget.dataset.actionNavigate;
    if (!actionNavigate) {
      return false;
    }
    const value = this.resolveHTMLFormChildElementValue(resolvedTarget);
    const navigateValue = resolvedTarget.dataset.navigateValue;
    if (actionNavigate === '$data=~s/$value/' && navigateValue && value !== null) {
      window.location.href = this.substituteValueVariable(navigateValue, value);
      return true;
    }
    if (actionNavigate === '$data' && navigateValue) {
      window.location.href = navigateValue;
      return true;
    }
    if (actionNavigate === '$value' && value) {
      window.location.href = value;
      return true;
    }
    return false;
  }

  private handleFormNavigateAction(evt: Event, resolvedTarget: HTMLFormElement): boolean {
    const formAction = resolvedTarget.action;
    const actionNavigate: string = resolvedTarget.dataset.actionNavigate;
    if (!formAction || !actionNavigate) {
      return false;
    }
    const navigateValue = resolvedTarget.dataset.navigateValue;
    const valueSelector = resolvedTarget.dataset.valueSelector;
    const value = this.resolveHTMLFormChildElementValue(resolvedTarget.querySelector(valueSelector));
    if (actionNavigate === '$form=~s/$value/' && navigateValue && value !== null) {
      window.location.href = this.substituteValueVariable(navigateValue, value);
      return true;
    }
    if (actionNavigate === '$form') {
      window.location.href = formAction;
      return true;
    }
    return false;
  }

  private substituteValueVariable(haystack: string, substitute: string): string {
    // replacing `${value}` and `$[value]` and its URL encoded representation
    // (`${value}` is difficult to achieve with Fluid, that's why there's `$[value]` as well)
    return haystack.replace(/(\$\{value\}|%24%7Bvalue%7D|\$\[value\]|%24%5Bvalue%5D)/gi, substitute);
  }

  private isHTMLFormChildElement(element: HTMLElement): boolean {
    return element instanceof HTMLSelectElement
      || element instanceof HTMLInputElement
      || element instanceof HTMLTextAreaElement;
  }

  private resolveHTMLFormChildElementValue(element: HTMLElement): string | null {
    const type: string = element.getAttribute('type');
    if (element instanceof HTMLSelectElement) {
      return element.options[element.selectedIndex].value;
    } else if (element instanceof HTMLInputElement && type === 'checkbox') {
      // used for representing unchecked state as e.g. `data-empty-value="0"`
      const emptyValue: string = element.dataset.emptyValue;
      if (element.checked) {
        return element.value;
      } else if (typeof emptyValue !== 'undefined') {
        return emptyValue;
      } else {
        return '';
      }
    } else if (element instanceof HTMLInputElement) {
      return element.value;
    }
    return null;
  }

  private assignHTMLFormChildElementValue(element: HTMLElement, value: string): void {
    const type: string = element.getAttribute('type');
    if (element instanceof HTMLSelectElement) {
      Array.from(element.options).some((option: HTMLOptionElement, index: number) => {
        if (option.value === value) {
          element.selectedIndex = index;
          return true;
        }
        return false;
      });
    } else if (element instanceof HTMLInputElement && type === 'checkbox') {
      // used for representing unchecked state as e.g. `data-empty-value="0"`
      const emptyValue: string = element.dataset.emptyValue;
      if (typeof emptyValue !== 'undefined' && emptyValue === value) {
        element.checked = false;
      } else if (element.value === value) {
        element.checked = true;
      }
    } else if (element instanceof HTMLInputElement) {
      element.value = value;
    }
  }
}

export default new GlobalEventHandler();
