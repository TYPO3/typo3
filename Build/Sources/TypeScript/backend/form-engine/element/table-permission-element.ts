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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import { selector } from '@typo3/core/literals';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';

enum Permissions {
  none = 'none',
  select = 'select',
  modify = 'modify',
}

/**
 * Module: @typo3/backend/form-engine/element/table-permissions-element
 *
 * Functionality for the tablePermission form element
 *
 * @example
 * <typo3-formengine-element-tablepermission selectStateFieldName="<field>" modifyStateFieldName="<field>">
 *   ...
 * </typo3-formengine-element-tablepermission>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class TablePermissionElement extends HTMLElement {
  private selectStateField: HTMLInputElement = null;
  private modifyStateField: HTMLInputElement = null;

  public async connectedCallback(): Promise<void> {
    await DocumentService.ready();
    this.selectStateField = <HTMLInputElement>this.querySelector(selector`input[name=${this.getAttribute('selectStateFieldName') || '' as string}]`);
    this.modifyStateField = <HTMLInputElement>this.querySelector(selector`input[name=${this.getAttribute('modifyStateFieldName') || '' as string}]`);

    if (this.selectStateField === null || this.modifyStateField === null) {
      return;
    }

    this.registerEventHandler();
  }

  private registerEventHandler() {
    new RegularEvent('change', (e: CustomEvent): void => {
      this.handleSingleItemChange(e.target as HTMLInputElement);
    }).delegateTo(this.querySelector('table'), '.t3js-table-permissions-item');
    new RegularEvent('multiRecordSelection:checkbox:state:changed', (e: CustomEvent): void => {
      const name = (e.target as HTMLInputElement).name;
      if (this.querySelectorAll(selector`input[name="${name}"]:checked`).length === 0) {
        const item: HTMLInputElement = this.querySelector(selector`input[name="${name}"]`) as HTMLInputElement;
        item.value = Permissions.none;
        this.handleSingleItemChange(item);
        (this.querySelector(selector`input[name="${name}"][value="${Permissions.none}"]`) as HTMLInputElement).checked = true;
      }
    }).delegateTo(this.querySelector('table') , MultiRecordSelectionSelectors.checkboxSelector);
  }

  private handleSingleItemChange(target: HTMLInputElement): void {
    switch (target.value) {
      case Permissions.select:
        this.addItem(target.dataset.table, this.selectStateField);
        this.removeItem(target.dataset.table, this.modifyStateField);
        break;
      case Permissions.modify:
        this.addItem(target.dataset.table, this.selectStateField);
        this.addItem(target.dataset.table, this.modifyStateField);
        break;
      case Permissions.none:
      default:
        this.removeItem(target.dataset.table, this.selectStateField);
        this.removeItem(target.dataset.table, this.modifyStateField);
        break;
    }
  }

  private removeItem(table: string, field: HTMLInputElement) {
    field.value = (field.value.length ? field.value.split(',') : []).filter(item => item !== table).join(',');
  }

  private addItem(table: string, field: HTMLInputElement) {
    const list: string[] = field.value.length ? field.value.split(',') : [];
    if (list.includes(table)) {
      return;
    }
    list.push(table);
    field.value = list.join(',');
  }
}

window.customElements.define('typo3-formengine-element-tablepermission', TablePermissionElement);
