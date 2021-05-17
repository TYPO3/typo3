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

import CodeMirror from 'codemirror';
import {LitElement, html, css, CSSResult} from 'lit';
import {customElement, property, state} from 'lit/decorators';
import FormEngine = require('TYPO3/CMS/Backend/FormEngine');

import 'TYPO3/CMS/Backend/Element/SpinnerElement'

/**
 * Module: TYPO3/CMS/T3editor/Element/CodeMirrorElement
 * Renders CodeMirror into FormEngine
 */
@customElement('typo3-t3editor-codemirror')
export class CodeMirrorElement extends LitElement {
  @property() mode: string;
  @property() label: string;
  @property({type: Array}) addons: string[] = [];
  @property({type: Object}) options: { [key: string]: any[] } = {};
  @state() loaded: boolean = false;

  static styles = css`
   :host {
     display: block;
     position: relative;
   }
   typo3-backend-spinner {
     position: absolute;
     top: 50%;
     left: 50%;
     transform: translate(-50%, -50%);
   }
  `;

  render() {
    return html`
      <slot></slot>
      <slot name="codemirror"></slot>
      ${this.loaded ? '' : html`<typo3-backend-spinner size="large" variant="dark"></typo3-backend-spinner>`}
    `;
  }

  firstUpdated(): void {
    const observerOptions = {
      root: document.body
    };
    let observer = new IntersectionObserver((entries: IntersectionObserverEntry[]): void => {
      entries.forEach((entry: IntersectionObserverEntry): void => {
        if (entry.intersectionRatio > 0) {
          observer.unobserve(entry.target);
          if (this.firstElementChild && this.firstElementChild.nodeName.toLowerCase() === 'textarea') {
            this.initializeEditor(<HTMLTextAreaElement>this.firstElementChild);
          }
        }
      });
    }, observerOptions);

    observer.observe(this);
  }

  private createPanelNode(position: string, label: string): HTMLElement {
    const node = document.createElement('div');
    node.setAttribute('class', 'CodeMirror-panel CodeMirror-panel-' + position);
    node.setAttribute('id', 'panel-' + position);

    const span = document.createElement('span');
    span.textContent = label;

    node.appendChild(span);

    return node;
  }

  private initializeEditor(textarea: HTMLTextAreaElement): void {
    const modeParts = this.mode.split('/');
    const options = this.options;

    // load mode + registered addons
    require([this.mode, ...this.addons], (): void => {
      const cm = CodeMirror((node: HTMLElement): void => {
        const wrapper = document.createElement('div');
        wrapper.setAttribute('slot', 'codemirror');
        wrapper.appendChild(node);
        this.insertBefore(wrapper, textarea);
      }, {
        value: textarea.value,
        extraKeys: {
          'Ctrl-F': 'findPersistent',
          'Cmd-F': 'findPersistent',
          'Ctrl-Alt-F': (codemirror: any): void => {
            codemirror.setOption('fullScreen', !codemirror.getOption('fullScreen'));
          },
          'Ctrl-Space': 'autocomplete',
          'Esc': (codemirror: any): void => {
            if (codemirror.getOption('fullScreen')) {
              codemirror.setOption('fullScreen', false);
            }
          },
        },
        fullScreen: false,
        lineNumbers: true,
        lineWrapping: true,
        mode: modeParts[modeParts.length - 1],
      });

      // set options
      Object.keys(options).map((key: string): void => {
        cm.setOption(key, options[key]);
      });

      // Mark form as changed if code editor content has changed
      cm.on('change', (): void => {
        textarea.value = cm.getValue();
        FormEngine.Validation.markFieldAsChanged(textarea);
      });

      const bottomPanel = this.createPanelNode('bottom', this.label);
      cm.addPanel(
        bottomPanel,
        {
          position: 'bottom',
          stable: false,
        },
      );

      // cm.addPanel() changes the height of the editor, thus we have to override it here again
      if (textarea.getAttribute('rows')) {
        const lineHeight = 18;
        const paddingBottom = 4;
        cm.setSize(null, parseInt(textarea.getAttribute('rows'), 10) * lineHeight + paddingBottom + bottomPanel.getBoundingClientRect().height);
      } else {
        // Textarea has no "rows" attribute configured, don't limit editor in space
        cm.getWrapperElement().style.height = (document.body.getBoundingClientRect().height - cm.getWrapperElement().getBoundingClientRect().top - 80) + 'px';
        cm.setOption('viewportMargin', Infinity);
      }

      this.loaded = true;
    });
  }
}
