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

import './Element/CodeMirrorElement';
import DocumentService = require('TYPO3/CMS/Core/DocumentService');

/**
 * Module: TYPO3/CMS/T3editor/T3editor
 * Renders CodeMirror into FormEngine
 * @exports TYPO3/CMS/T3editor/T3editor
 * @deprecated since v11.1, will be removed in v12
 */
class T3editor {

  /**
   * @param {string} position
   * @param {string} label
   * @returns {HTMLElement}
   */
  public static createPanelNode(position: string, label: string): HTMLElement {
    const node = document.createElement('div');
    node.setAttribute('class', 'CodeMirror-panel CodeMirror-panel-' + position);
    node.setAttribute('id', 'panel-' + position);

    const span = document.createElement('span');
    span.textContent = label;

    node.appendChild(span);

    return node;
  }

  /**
   * The constructor, set the class properties default values
   */
  constructor() {
    console.warn('TYPO3/CMS/T3editor/T3editor has been marked as deprecated. Please use TYPO3/CMS/T3editor/Element/CodeMirrorElement instead.');
    this.initialize();
  }

  /**
   * Initialize the events
   */
  public initialize(): void {
    DocumentService.ready().then((): void => {
      this.observeEditorCandidates();
    });
  }

  /**
   * Initializes CodeMirror on available texteditors
   */
  public observeEditorCandidates(): void {
    document.querySelectorAll('textarea.t3editor').forEach((textarea: HTMLTextAreaElement): void => {
      if (textarea.parentElement.tagName.toLowerCase() === 'typo3-t3editor-codemirror') {
        return;
      }
      const editor = document.createElement('typo3-t3editor-codemirror');
      const config = JSON.parse(textarea.getAttribute('data-codemirror-config'));
      editor.setAttribute('mode', config.mode);
      editor.setAttribute('label', config.label);
      editor.setAttribute('addons', config.addons);
      editor.setAttribute('options', config.options);

      this.wrap(textarea, editor);
    });
  }

  private wrap(toWrap: HTMLElement, wrapper: HTMLElement) {
    toWrap.parentElement.insertBefore(wrapper, toWrap);
    wrapper.appendChild(toWrap);
  };
}

// create an instance and return it
export = new T3editor();
