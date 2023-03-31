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

import { LitElement, html, css } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import {
  EditorView,
  ViewUpdate,
  lineNumbers,
  highlightSpecialChars,
  drawSelection,
  keymap,
  KeyBinding,
  placeholder
} from '@codemirror/view';
import { Extension, EditorState, Compartment } from '@codemirror/state';
import { syntaxHighlighting, defaultHighlightStyle } from '@codemirror/language';
import { defaultKeymap, indentWithTab } from '@codemirror/commands';
import { oneDark } from '@codemirror/theme-one-dark';
import { executeJavaScriptModuleInstruction, loadModule, resolveSubjectRef, JavaScriptItemPayload } from '@typo3/core/java-script-item-processor';
import '@typo3/backend/element/spinner-element';

/**
 * Module: @typo3/t3editor/element/code-mirror-element
 * Renders CodeMirror into FormEngine
 */
@customElement('typo3-t3editor-codemirror')
export class CodeMirrorElement extends LitElement {
  static styles = css`
    @media (prefers-color-scheme: dark) {
      :host {
        --codemirror-border-color: #7d8799;
        --codemirror-panel-background-color: #282c34;
      }

      .cm-scroller {
        color-scheme: dark;
      }
    }

    @media (prefers-color-scheme: light) {
      :host {
        --codemirror-border-color: #dddddd;
        --codemirror-panel-background-color: #f5f5f5;
      }
    }

    :host {
      display: flex;
      flex-direction: column;
      position: relative;
      border: 1px solid var(--codemirror-border-color);
      border-radius: var(--typo3-input-border-radius);
      transition: outline-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    :host([fullscreen]) {
      position: fixed;
      inset: 64px 0 0;
      z-index: 9;
    }

    :host(:focus) {
      --codemirror-border-color: #80bcf3;
      box-shadow: 0 0 0 0.25rem rgba(0,120,230,.25);
    }

    typo3-backend-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    #codemirror-parent {
      min-height: calc(8px + 12px * 1.4 * var(--rows, 18));
    }

    #codemirror-parent,
    .cm-editor {
      display: flex;
      flex-direction: column;
      flex: 1;
      max-height: 100%;
    }

    #codemirror-parent .cm-focused {
      outline: none;
    }

    .cm-scroller {
      min-height: 100%;
      max-height: calc(100vh - 10rem);
      flex: 1;
    }

    :host([fullscreen]) .cm-scroller {
      min-height: initial;
      max-height: 100%;
    }

    :host([autoheight]) .cm-scroller {
      max-height: initial;
    }

    .panel {
      font-size: .85em;
      color: var(--typo3-component-color);
      background-color: var(--codemirror-panel-background-color);
      border-style: solid;
      border-color: var(--codemirror-border-color);
      border-width: 0;
      border-top-width: 1px;
      padding: .25em .5em;
    }

    .panel-top {
      border-top-width: 0;
      border-bottom-width: 1px;
      order: -1;
    }
  `;

  @property({ type: Object }) mode: JavaScriptItemPayload = null;
  @property({ type: Array }) addons: JavaScriptItemPayload[] = [];
  @property({ type: Array }) keymaps: JavaScriptItemPayload[] = [];

  @property({ type: Number }) lineDigits: number = 0;
  @property({ type: Boolean, reflect: true }) autoheight: boolean = false;
  @property({ type: Boolean }) nolazyload: boolean = false;
  @property({ type: Boolean }) readonly: boolean = false;
  @property({ type: Boolean, reflect: true }) fullscreen: boolean = false;

  @property({ type: String }) label: string;
  @property({ type: String }) placeholder: string;
  @property({ type: String }) panel: string = 'bottom';

  @state() editorTheme: Compartment = null;
  @state() editorView: EditorView = null;

  render() {
    return html`
      <div id="codemirror-parent" @keydown=${(e: KeyboardEvent) => this.onKeydown(e)}></div>
      ${this.label ? html`<div class="panel panel-${this.panel}">${this.label}</div>` : ''}
      ${this.editorView === null ? html`<typo3-backend-spinner size="large" variant="dark"></typo3-backend-spinner>` : ''}
    `;
  }

  firstUpdated(): void {
    if (this.nolazyload) {
      this.initializeEditor(<HTMLTextAreaElement>this.firstElementChild);
      return;
    }
    const observerOptions = {
      root: document.body
    };
    const observer = new IntersectionObserver((entries: IntersectionObserverEntry[]): void => {
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

  private onKeydown(event: KeyboardEvent): void {
    if (event.ctrlKey && event.altKey && event.key === 'f') {
      event.preventDefault();
      this.fullscreen = true;
    }
    if (event.key === 'Escape' && this.fullscreen) {
      event.preventDefault();
      this.fullscreen = false;
    }
  }

  private async initializeEditor(textarea: HTMLTextAreaElement): Promise<void> {
    const updateListener = EditorView.updateListener.of((v: ViewUpdate) => {
      if (v.docChanged) {
        textarea.value = v.state.doc.toString();
        textarea.dispatchEvent(new CustomEvent('change', { bubbles: true }));
      }
    });

    if (this.lineDigits > 0) {
      this.style.setProperty('--rows', this.lineDigits.toString());
    } else if (textarea.getAttribute('rows')) {
      this.style.setProperty('--rows', textarea.getAttribute('rows'));
    }

    this.editorTheme = new Compartment();
    const extensions: Extension[] = [
      this.editorTheme.of([]),
      updateListener,
      lineNumbers(),
      highlightSpecialChars(),
      drawSelection(),
      EditorState.allowMultipleSelections.of(true),
      syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
    ];

    if (this.readonly) {
      extensions.push(EditorState.readOnly.of(true));
    }

    if (this.placeholder) {
      extensions.push(placeholder(this.placeholder));
    }

    if (this.mode) {
      const modeImplementation = <Extension[]>await executeJavaScriptModuleInstruction(this.mode);
      extensions.push(...modeImplementation);
    }

    if (this.addons.length > 0) {
      extensions.push(...await Promise.all(this.addons.map(moduleInstruction => executeJavaScriptModuleInstruction(moduleInstruction))));
    }

    const keymaps: KeyBinding[] = [
      ...defaultKeymap,
      indentWithTab,
    ];
    if (this.keymaps.length > 0) {
      const dynamicKeymaps: KeyBinding[][] = await Promise.all(this.keymaps.map(keymap => loadModule(keymap).then((module) => resolveSubjectRef(module, keymap))));
      dynamicKeymaps.forEach(keymap => keymaps.push(...keymap));
    }
    extensions.push(keymap.of(keymaps));

    this.editorView = new EditorView({
      state: EditorState.create({
        doc: textarea.value,
        extensions
      }),
      parent: this.renderRoot.querySelector('#codemirror-parent'),
      root: this.renderRoot as ShadowRoot
    });

    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    this.toggleDarkMode(darkModeMediaQuery.matches);

    darkModeMediaQuery.addEventListener('change', (e: MediaQueryListEvent): void => {
      this.toggleDarkMode(e.matches);
    });
  }

  private toggleDarkMode(enabled: boolean): void {
    this.editorView.dispatch({
      effects: this.editorTheme.reconfigure(
        enabled ? oneDark : []
      )
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-t3editor-codemirror': CodeMirrorElement;
  }
}
