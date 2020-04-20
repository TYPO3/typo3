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

import documentService = require('TYPO3/CMS/Core/DocumentService');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

type HTMLFormChildElement = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

/**
 * Module: TYPO3/CMS/Backend/GlobalEventHandler
 *
 * + `data-global-event="change"`
 *   + `data-action-submit="..."` submits form data
 *     + `$form` parent form element of current element is submitted
 *     + `<any CSS selector>` queried element is submitted (if implementing HTMLFormElement)
 *   + `data-action-navigate="..."` navigates to URL
 *     + `$value` URL taken from value of current element
 *     + `$data` URL taken from `data-navigate-value`
 *     + `$data=~s/$value/` URL taken from `data-navigate-value`, substituting literal `${value}`
 * + `data-global-event="click"`
 *   + @todo
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
  };

  constructor() {
    documentService.ready().then((): void => this.registerEvents());
  };

  private registerEvents(): void {
    new RegularEvent('change', this.handleChangeEvent.bind(this))
      .delegateTo(document, this.options.onChangeSelector);
    new RegularEvent('click', this.handleClickEvent.bind(this))
      .delegateTo(document, this.options.onClickSelector);
  }

  private handleChangeEvent(evt: Event, resolvedTarget: HTMLElement): void {
    evt.preventDefault();
    this.handleSubmitAction(evt, resolvedTarget)
      || this.handleNavigateAction(evt, resolvedTarget);
  }

  private handleClickEvent(evt: Event, resolvedTarget: HTMLElement): void {
    evt.preventDefault();
  }

  private handleSubmitAction(evt: Event, resolvedTarget: HTMLElement): boolean {
    const action: string = resolvedTarget.dataset.actionSubmit;
    if (!action) {
      return false;
    }
    // @example [data-action-submit]="$form"
    if (action === '$form' && this.isHTMLFormChildElement(resolvedTarget)) {
      (resolvedTarget as HTMLFormChildElement).form.submit();
      return true;
    }
    const formCandidate = document.querySelector(action);
    if (formCandidate instanceof HTMLFormElement) {
      formCandidate.submit();
      return true;
    }
    return false;
  }

  private handleNavigateAction(evt: Event, resolvedTarget: HTMLElement): boolean {
    const action: string = resolvedTarget.dataset.actionNavigate;
    if (!action) {
      return false;
    }
    const value = this.resolveHTMLFormChildElementValue(resolvedTarget);
    const navigateValue = resolvedTarget.dataset.navigateValue;
    if (action === '$data=~s/$value/' && navigateValue && value !== null) {
      // replacing `${value}` and its URL encoded representation
      window.location.href = navigateValue.replace(/(\$\{value\}|%24%7Bvalue%7D)/gi, value);
      return true;
    }
    if (action === '$data' && navigateValue) {
      window.location.href = navigateValue;
      return true;
    }
    if (action === '$value' && value) {
      window.location.href = value;
      return true;
    }
    return false;
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
      return element.checked ? element.value : '';
    } else if (element instanceof HTMLInputElement) {
      return element.value;
    }
    return null;
  }
}

export = new GlobalEventHandler();
