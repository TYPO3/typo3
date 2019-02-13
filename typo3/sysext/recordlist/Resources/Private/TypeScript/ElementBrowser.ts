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

import * as $ from 'jquery';
import Modal = require('TYPO3/CMS/Backend/Modal');

interface RTESettings {
  parameters: string;
  configuration: string;
}

interface InlineSettings {
  objectId: number;
  checkUniqueAction: string;
  addAction: string;
  insertAction: string;
}

declare global {
  interface Document {
    list_frame: Window;
  }
  interface Window {
    jumpToUrl: Function;
    setFormValueFromBrowseWin: Function;
    editform: any;
    content: any;
    group_change: any;
  }
}

/**
 * Module: TYPO3/CMS/Recordlist/ElementBrowser
 * @exports TYPO3/CMS/Recordlist/ElementBrowser
 * ElementBrowser communication with parent windows
 */
class ElementBrowser {
  public hasActionMultipleCode: boolean = false;

  private thisScriptUrl: string = '';
  private mode: string = '';
  private formFieldName: string = '';
  private fieldReference: string = '';
  private fieldReferenceSlashed: string = '';
  private targetDoc: Window;
  private elRef: Element;
  private rte: RTESettings = {
    parameters: '',
    configuration: '',
  };
  private irre: InlineSettings = {
    objectId: 0,
    checkUniqueAction: '',
    addAction: '',
    insertAction: '',
  };

  constructor() {
    $((): void => {
      const data = $('body').data();
      this.thisScriptUrl = data.thisScriptUrl;
      this.mode = data.mode;
      this.formFieldName = data.formFieldName;
      this.fieldReference = data.fieldReference;
      this.fieldReferenceSlashed = data.fieldReferenceSlashed;
      this.rte.parameters = data.rteParameters;
      this.rte.configuration = data.rteConfiguration;
      this.irre.checkUniqueAction = data.irreCheckUniqueAction;
      this.irre.addAction = data.irreAddAction;
      this.irre.insertAction = data.irreInsertAction;
      this.irre.objectId = data.irreObjectId;
      this.hasActionMultipleCode = (this.irre.objectId > 0 && this.irre.insertAction !== '');
    });

    /**
     * Global jumpTo function
     *
     * Used by tree implementation
     *
     * @param {String} URL
     * @param {String} anchor
     * @returns {Boolean}
     */
    window.jumpToUrl = (URL: string, anchor?: string): boolean => {
      if (URL.charAt(0) === '?') {
        URL = this.thisScriptUrl + URL.substring(1);
      }
      const add_mode = URL.indexOf('mode=') === -1 ? '&mode=' + encodeURIComponent(this.mode) : '';
      window.location.href = URL + add_mode + (typeof (anchor) === 'string' ? anchor : '');
      return false;
    };

  }

  public setReferences(): boolean {
    if (this.getParent() && this.getParent().content && this.getParent().content.document.editform
      && this.getParent().content.document.editform[this.formFieldName]) {
      this.targetDoc = this.getParent().content.document;
      this.elRef = this.targetDoc.editform[this.formFieldName];
      return true;
    } else {
      return false;
    }
  }

  /**
   * Dynamically calls a function on a given context object.
   */
  public executeFunctionByName(functionName: string, context: any, ...args: Array<any>): any {
    const namespaces = functionName.split('.');
    const func = namespaces.pop();
    for (let i = 0; i < namespaces.length; i++) {
      context = context[namespaces[i]];
    }
    return context[func].apply(context, args);
  }

  /**
   * Returns the parent document object
   */
  public getParent(): Window | null {
    let opener: Window = null;
    if (
      typeof window.parent !== 'undefined' &&
      typeof window.parent.document.list_frame !== 'undefined' &&
      window.parent.document.list_frame.parent.document.querySelector('.t3js-modal-iframe') !== null
    ) {
      opener = window.parent.document.list_frame;
    } else if (
      typeof window.parent !== 'undefined' &&
      typeof window.parent.frames.list_frame !== 'undefined' &&
      window.parent.frames.list_frame.parent.document.querySelector('.t3js-modal-iframe') !== null
    ) {
      opener = window.parent.frames.list_frame;
    } else if (
      typeof window.frames !== 'undefined' &&
      typeof window.frames.frameElement !== 'undefined' &&
      window.frames.frameElement !== null &&
      window.frames.frameElement.classList.contains('t3js-modal-iframe')
    ) {
      opener = (<HTMLFrameElement>window.frames.frameElement).contentWindow.parent;
    } else if (window.opener) {
      opener = window.opener;
    }
    return opener;
  }

  public insertElement(
    table: string,
    uid: number,
    type: string,
    filename: string,
    fp: string,
    filetype: string,
    imagefile: string,
    action: string,
    close: boolean,
  ): boolean {
    let performAction = true;

    // Call a check function in the opener window (e.g. for uniqueness handling):
    if (this.irre.objectId && this.irre.checkUniqueAction) {
      if (this.getParent()) {
        const res = this.executeFunctionByName(this.irre.checkUniqueAction, this.getParent(), this.irre.objectId, table, uid, type);
        if (!res.passed) {
          if (res.message) {
            alert(res.message);
          }
          performAction = false;
        }
      } else {
        alert('Error - reference to main window is not set properly!');
        this.focusOpenerAndClose();
      }
    }
    // Call performing function and finish this action:
    if (performAction) {
      // Call helper function to manage data in the opener window:
      if (this.irre.objectId && this.irre.addAction) {
        if (this.getParent()) {
          this.executeFunctionByName(
            this.irre.addAction, this.getParent(), this.irre.objectId,
            table, uid, type, this.fieldReferenceSlashed,
          );
        } else {
          alert('Error - reference to main window is not set properly!');
          this.focusOpenerAndClose();
        }
      }
      if (this.irre.objectId && this.irre.insertAction) {
        if (this.getParent()) {
          this.executeFunctionByName(this.irre.insertAction, this.getParent(), this.irre.objectId, table, uid, type);
          if (close) {
            this.focusOpenerAndClose();
          }
        } else {
          alert('Error - reference to main window is not set properly!');
          if (close) {
            this.focusOpenerAndClose();
          }
        }
      } else if (this.fieldReference && !this.rte.parameters && !this.rte.configuration) {
        this.addElement(filename, table + '_' + uid, fp, close);
      } else {
        if (
          this.getParent() && this.getParent().content && this.getParent().content.document.editform
          && this.getParent().content.document.editform[this.formFieldName]
        ) {
          this.getParent().group_change(
            'add',
            this.fieldReference,
            this.rte.parameters,
            this.rte.configuration,
            this.targetDoc.editform[this.formFieldName],
            this.getParent().content.document,
          );
        } else {
          alert('Error - reference to main window is not set properly!');
        }
        if (close) {
          this.focusOpenerAndClose();
        }
      }
    }
    return false;
  }

  public insertMultiple(table: string, uid: number): boolean {
    let type = '';
    if (this.irre.objectId && this.irre.insertAction) {
      // Call helper function to manage data in the opener window:
      if (this.getParent()) {
        this.executeFunctionByName(
          this.irre.insertAction + 'Multiple', this.getParent(),
          this.irre.objectId, table, uid, type, this.fieldReference,
        );
      } else {
        alert('Error - reference to main window is not set properly!');
        this.focusOpenerAndClose();
      }
    }
    return false;
  }

  public addElement(elName: string, elValue: string, altElValue: string, close: boolean): void {
    if (this.getParent() && this.getParent().setFormValueFromBrowseWin) {
      this.getParent().setFormValueFromBrowseWin(this.fieldReference, altElValue ? altElValue : elValue, elName);
      if (close) {
        this.focusOpenerAndClose();
      }
    } else {
      alert('Error - reference to main window is not set properly!');
      this.focusOpenerAndClose();
    }
  }

  public focusOpenerAndClose = (): void => {
    if (this.getParent()) {
      this.getParent().focus();
    }
    Modal.dismiss();
    close();
  }
}

export = new ElementBrowser();
