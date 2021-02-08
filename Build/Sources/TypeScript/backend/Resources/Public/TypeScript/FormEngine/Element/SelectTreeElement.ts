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

import {SelectTree} from './SelectTree';
import {TreeToolbar} from './TreeToolbar';

export class SelectTreeElement {
  private readonly treeWrapper: HTMLElement = null;
  private readonly recordField: HTMLInputElement = null;

  constructor(treeWrapperId: string, treeRecordFieldId: string, callback: Function) {
    this.treeWrapper = <HTMLElement>document.getElementById(treeWrapperId);
    this.recordField = <HTMLInputElement>document.getElementById(treeRecordFieldId);
    const tree = new SelectTree();

    const settings = {
      dataUrl: this.generateRequestUrl(),
      showIcons: true,
      showCheckboxes: true,
      readOnlyMode: parseInt(this.recordField.dataset.readOnly, 10) === 1,
      input: this.recordField,
      exclusiveNodesIdentifiers: this.recordField.dataset.treeExclusiveKeys,
      validation: JSON.parse(this.recordField.dataset.formengineValidationRules)[0],
      expandUpToLevel: this.recordField.dataset.treeExpandUpToLevel,
    };
    tree.initialize(this.treeWrapper, settings);
    tree.dispatch.on('nodeSelectedAfter.requestUpdate', () => { callback(); } );
    this.listenForVisibleTree();

    new TreeToolbar(this.treeWrapper);
  }

  /**
   * If the Select item is in an invisible tab, it needs to be rendered once the tab
   * becomes visible.
   */
  private listenForVisibleTree(): void {
    if (!this.treeWrapper.offsetParent) {
      // Search for the parents that are tab containers
      let idOfTabContainer = this.treeWrapper.closest('.tab-pane').getAttribute('id');
      if (idOfTabContainer) {
        let btn = document.querySelector('[aria-controls="' + idOfTabContainer + '"]');
        btn.addEventListener('shown.bs.tab', () => { this.treeWrapper.dispatchEvent(new Event('svg-tree:visible')); });
      }
    }
  }

  private generateRequestUrl(): string {
    const params = {
      tableName: this.recordField.dataset.tablename,
      fieldName: this.recordField.dataset.fieldname,
      uid: this.recordField.dataset.uid,
      recordTypeValue: this.recordField.dataset.recordtypevalue,
      dataStructureIdentifier: this.recordField.dataset.datastructureidentifier,
      flexFormSheetName: this.recordField.dataset.flexformsheetname,
      flexFormFieldName: this.recordField.dataset.flexformfieldname,
      flexFormContainerName: this.recordField.dataset.flexformcontainername,
      flexFormContainerIdentifier: this.recordField.dataset.flexformcontaineridentifier,
      flexFormContainerFieldName: this.recordField.dataset.flexformcontainerfieldname,
      flexFormSectionContainerIsNew: this.recordField.dataset.flexformsectioncontainerisnew,
      command: this.recordField.dataset.command,
    };
    return TYPO3.settings.ajaxUrls.record_tree_data + '&' + new URLSearchParams(params);
  }
}
