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

import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property, query } from 'lit/decorators.js';
import type { ModuleState } from '../module';
import coreLabels from '~labels/core.core';

/**
 * Bookmarklets and browser extensions (WCAG 1.4.12) inject letter-spacing,
 * word-spacing and line-height into the backend scaffold document. The module
 * iframe is a separate document, so those styles never reach the page module
 * or its content-element previews, and a one-time injection is lost on the
 * next module navigation.
 */
const textSpacingStyleId = 'typo3-text-spacing';
const textSpacingPattern = /letter-spacing|word-spacing/i;

const collectTextSpacingCss = (doc: Document): string => {
  const chunks: string[] = [];

  doc.querySelectorAll('style').forEach((style: HTMLStyleElement) => {
    if (style.id === textSpacingStyleId) {
      return;
    }
    const css = style.textContent ?? '';
    if (textSpacingPattern.test(css)) {
      chunks.push(css);
    }
  });

  for (const sheet of doc.adoptedStyleSheets ?? []) {
    const css = extractTextSpacingRules(sheet);
    if (css !== '') {
      chunks.push(css);
    }
  }

  const inlineCss = inlineTextSpacingCss(doc);
  if (inlineCss !== '') {
    chunks.push(inlineCss);
  }

  return chunks.join('\n');
};

const extractTextSpacingRules = (sheet: CSSStyleSheet): string => {
  let rules: CSSRuleList;
  try {
    rules = sheet.cssRules;
  } catch {
    // Cross-origin sheets are not readable.
    return '';
  }

  const matched: string[] = [];
  const paragraphSpacing: string[] = [];
  const visit = (ruleList: CSSRuleList): void => {
    for (const rule of ruleList) {
      if (rule instanceof CSSStyleRule) {
        if (textSpacingPattern.test(rule.cssText)) {
          matched.push(rule.cssText);
        } else if (rule.style.getPropertyPriority('margin-bottom') === 'important' && /^p$/i.test(rule.selectorText.trim())) {
          paragraphSpacing.push(rule.cssText);
        }
      } else if (rule instanceof CSSGroupingRule) {
        visit(rule.cssRules);
      }
    }
  };
  visit(rules);

  if (matched.length === 0) {
    return '';
  }
  return matched.concat(paragraphSpacing).join('\n');
};

const inlineTextSpacingCss = (doc: Document): string => {
  const source = [doc.documentElement, doc.body].find((element) =>
    element?.style.letterSpacing || element?.style.wordSpacing
  );
  if (!source) {
    return '';
  }

  const declarations = ['letter-spacing', 'word-spacing', 'line-height'].flatMap((property) => {
    const value = source.style.getPropertyValue(property);
    if (value === '') {
      return [];
    }
    const priority = source.style.getPropertyPriority(property);
    return [`${property}: ${value}${priority === 'important' ? ' !important' : ''}`];
  });
  if (declarations.length === 0) {
    return '';
  }

  let css = `* { ${declarations.join('; ')}; }`;
  const paragraph = doc.querySelector('p');
  const margin = paragraph?.style.getPropertyValue('margin-bottom') ?? '';
  if (paragraph !== null && margin !== '') {
    const priority = paragraph.style.getPropertyPriority('margin-bottom');
    css += `\np { margin-bottom: ${margin}${priority === 'important' ? ' !important' : ''}; }`;
  }
  return css;
};

const applyTextSpacingCss = (doc: Document, css: string): void => {
  const existing = doc.getElementById(textSpacingStyleId) as HTMLStyleElement | null;
  if (css === '') {
    existing?.remove();
    return;
  }
  if (existing?.textContent === css && existing.parentNode === doc.head && doc.head.lastElementChild === existing) {
    return;
  }

  const style = existing ?? doc.createElement('style');
  style.id = textSpacingStyleId;
  style.textContent = css;
  doc.head.appendChild(style);
};

/**
 * Module: @typo3/backend/module/iframe
 */
export const componentName = 'typo3-iframe-module';

@customElement('typo3-iframe-module')
export class IframeModuleElement extends LitElement {

  @property({ type: String }) endpoint: string = '';

  @query('iframe', true) iframe!: HTMLIFrameElement;

  private textSpacingObserver: MutationObserver | null = null;
  private textSpacingFrame = 0;

  public override attributeChangedCallback(name: string, old: string, value: string): void {
    super.attributeChangedCallback(name, old, value);

    if (name === 'endpoint' && value === old) {
      // Trigger explicit reload if value has been reset to current value,
      // lit doesn't re-set the attribute in this case.
      this.iframe.setAttribute('src', value);
    }
  }

  public override connectedCallback(): void {
    super.connectedCallback();
    this.observeParentTextSpacing();
    this.scheduleTextSpacingSync();
    if (this.endpoint) {
      this.dispatch('typo3-iframe-load', { url: this.endpoint, title: null });
    }
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.textSpacingObserver?.disconnect();
    this.textSpacingObserver = null;
    if (this.textSpacingFrame !== 0) {
      window.cancelAnimationFrame(this.textSpacingFrame);
      this.textSpacingFrame = 0;
    }
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Disable shadow root as <iframe> needs to be accessible
    // via top.list_frame for legacy-code and backwards compatibility.
    return this;
  }

  protected override render(): TemplateResult | symbol {
    if (!this.endpoint) {
      return nothing;
    }

    return html`
      <iframe
        src=${this.endpoint}
        name="list_frame"
        id="typo3-contentIframe"
        class="scaffold-content-module-iframe t3js-scaffold-content-module-iframe"
        title=${coreLabels.get('iframe.listFrame')}
        @load=${this._loaded}
      ></iframe>
    `;
  }

  private observeParentTextSpacing(): void {
    this.textSpacingObserver?.disconnect();
    this.textSpacingObserver = new MutationObserver(() => this.scheduleTextSpacingSync());
    this.textSpacingObserver.observe(document.head, {
      childList: true,
      subtree: true,
      characterData: true,
    });
    this.textSpacingObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['style'],
    });
    if (document.body) {
      this.textSpacingObserver.observe(document.body, {
        attributes: true,
        attributeFilter: ['style'],
        childList: true,
      });
    }
  }

  private scheduleTextSpacingSync(): void {
    if (this.textSpacingFrame !== 0) {
      return;
    }
    this.textSpacingFrame = window.requestAnimationFrame(() => {
      this.textSpacingFrame = 0;
      this.syncTextSpacing(this.querySelector('iframe'));
    });
  }

  private syncTextSpacing(iframe: HTMLIFrameElement | null): void {
    if (!iframe) {
      return;
    }
    try {
      const doc = iframe.contentDocument;
      if (!doc?.head) {
        return;
      }
      applyTextSpacingCss(doc, collectTextSpacingCss(document));
    } catch (error) {
      // A cross-origin frame throws on document access. Ignore that; anything
      // else must not break module navigation either.
      if (!(error instanceof DOMException && error.name === 'SecurityError')) {
        console.error('Failed to apply text spacing to the module iframe.', error);
      }
    }
  }

  private registerPagehideHandler(iframe: HTMLIFrameElement): void {
    try {
      iframe.contentWindow!.addEventListener('pagehide', (e: Event) => this._pagehide(e, iframe), { once: true });
    } catch (e) {
      console.error('Failed to access contentWindow of module iframe – using a foreign origin?');
      throw e;
    }
  }

  private retrieveModuleStateFromIFrame(iframe: HTMLIFrameElement): ModuleState {
    try {
      return {
        url: iframe.contentWindow!.location.href,
        title: iframe.contentDocument!.title,
        module: iframe.contentDocument!.body.querySelector('.module[data-module-name]')?.getAttribute('data-module-name') ?? undefined
      };
    } catch {
      console.error('Failed to access contentWindow of module iframe – using a foreign origin?');
      return { url: this.endpoint, title: null };
    }
  }

  private _loaded({ target }: Event) {
    const iframe = <HTMLIFrameElement> target;

    // The event handler for the "pagehide" event needs to be attached
    // after every iframe load (for the current iframes's contentWindow).
    this.registerPagehideHandler(iframe);
    this.syncTextSpacing(iframe);

    const state = this.retrieveModuleStateFromIFrame(iframe);
    this.dispatch('typo3-iframe-loaded', state);
  }

  private _pagehide(e: Event, iframe: HTMLIFrameElement) {
    // Asynchronous execution needed because the URL changes immediately after
    // the `pagehide` event is dispatched, but has not been changed right now.
    new Promise((resolve) => window.setTimeout(resolve, 0)).then(() => {
      if (iframe.contentWindow !== null) {
        this.dispatch('typo3-iframe-load', { url: iframe.contentWindow.location.href, title: null });
      }
    });
  }

  private dispatch(type: 'typo3-iframe-load' | 'typo3-iframe-loaded', state: ModuleState) {
    this.dispatchEvent(
      new CustomEvent<ModuleState>(type, { detail: state, bubbles: true, composed: true })
    );
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-iframe-module': IframeModuleElement;
  }

  interface HTMLElementEventMap {
    'typo3-iframe-load': CustomEvent<ModuleState>;
    'typo3-iframe-loaded': CustomEvent<ModuleState>;
  }

  interface DocumentEventMap {
    'typo3-iframe-load': CustomEvent<ModuleState>;
    'typo3-iframe-loaded': CustomEvent<ModuleState>;
  }
}
