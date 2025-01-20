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
 * Module: @typo3/backend/code-editor/element/code-mirror-element
 * Renders CodeMirror into FormEngine
 */
@customElement('typo3-t3editor-codemirror')
export class CodeMirrorElement extends LitElement {
  static styles = css`
    :host {
      position: relative;
      display: block;
    }

    :host([fullscreen]) {
      position: fixed;
      inset: 64px 0 0;
      z-index: 9;
    }

    :host([fullscreen]) .cm-scroller {
      min-height: initial;
      max-height: 100%;
    }

    :host([autoheight]) .cm-scroller {
      max-height: initial;
    }

    .codemirror-label {
      font-size: .875em;
      opacity: .75;
    }

    .codemirror-label-top {
      margin-bottom: .25rem;
    }

    .codemirror-label-bottom {
      margin-top: .25rem;
    }

    typo3-backend-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .cm-editor {
      overflow: hidden;
      border-radius: var(--typo3-input-border-radius);
      border: var(--typo3-input-border-width) solid var(--typo3-input-border-color);
      transition: outline-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .cm-focused {
      border-color: var(--typo3-input-focus-border-color);
      outline-offset: 0;
      outline: .25rem solid color-mix(in srgb, var(--typo3-form-control-focus-border-color), transparent 25%);
    }

    .cm-gutters {
      height: auto !important;
      position: relative !important;
    }

    .cm-content {
      min-height: calc(8px + 12px * 1.4 * var(--rows, 18)) !important;
    }

    .cm-scroller {
      min-height: 100%;
      max-height: calc(100dvh - 10rem);
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
      ${this.label && this.panel === 'top' ? html`<div class="codemirror-label codemirror-label-top">${this.label}</div>` : ''}
      <div id="codemirror-parent" @keydown=${(e: KeyboardEvent) => this.onKeydown(e)}></div>
      ${this.label && this.panel === 'bottom' ? html`<div class="codemirror-label codemirror-label-bottom">${this.label}</div>` : ''}
      ${this.editorView === null ? html`<typo3-backend-spinner size="large"></typo3-backend-spinner>` : ''}
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

  /**
   * @internal
   */
  public setContent(newContent: string): void {
    if (this.editorView !== null) {
      this.editorView.dispatch({
        changes: {
          from: 0,
          to: this.editorView.state.doc.length,
          insert: newContent
        }
      });
    }
  }

  /**
   * @internal
   */
  public getContent(): string {
    return this.editorView.state.doc.toString();
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

    this.toggleDarkMode(this.darkModeEnabled());
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    darkModeMediaQuery.addEventListener('change', (): void => {
      this.toggleDarkMode(this.darkModeEnabled());
    });
  }

  private darkModeEnabled(): boolean {
    const computedStyle = window.getComputedStyle(this);
    const colorScheme = computedStyle.colorScheme;
    if (colorScheme === 'light only' || colorScheme === 'light') {
      return false;
    } else if (colorScheme === 'dark only' || colorScheme === 'dark') {
      return true;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
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
