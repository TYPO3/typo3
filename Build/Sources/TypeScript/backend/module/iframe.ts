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

import {html, css, LitElement, TemplateResult} from 'lit';
import {customElement, property, query} from 'lit/decorators';
import {ModuleState} from '../module';
import {lll} from '@typo3/core/lit-helper';

/**
 * Module: @typo3/backend/module/iframe
 */
export const componentName = 'typo3-iframe-module';

@customElement(componentName)
export class IframeModuleElement extends LitElement {

  @property({type: String}) endpoint: string = '';

  @query('iframe', true) iframe: HTMLIFrameElement;

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Disable shadow root as <iframe> needs to be accessible
    // via top.list_frame for legacy-code and backwards compatibility.
    return this;
  }

  public render(): TemplateResult {
    if (!this.endpoint) {
      return html``;
    }

    return html`
      <iframe
        src="${this.endpoint}"
        name="list_frame"
        id="typo3-contentIframe"
        class="scaffold-content-module-iframe t3js-scaffold-content-module-iframe"
        title="${lll('iframe.listFrame')}"
        scrolling="no"
        @load="${this._loaded}"
      ></iframe>
    `;
  }

  public attributeChangedCallback(name: string, old: string, value: string) {
    super.attributeChangedCallback(name, old, value);

    if (name === 'endpoint' && value === old) {
      // Trigger explicit reload if value has been reset to current value,
      // lit doesn't re-set the attribute in this case.
      this.iframe.setAttribute('src', value);
    }
  }

  public connectedCallback(): void {
    super.connectedCallback();
    if (this.endpoint) {
      this.dispatch('typo3-iframe-load', { url: this.endpoint, title: null });
    }
  }

  private registerUnloadHandler(iframe: HTMLIFrameElement): void {
    try {
      iframe.contentWindow.addEventListener('unload', (e: Event) => this._unload(e, iframe), { once: true});
    } catch (e) {
      console.error('Failed to access contentWindow of module iframe – using a foreign origin?');
      throw e;
    }
  }

  private retrieveModuleStateFromIFrame(iframe: HTMLIFrameElement): ModuleState {
    try {
      return {
        url: iframe.contentWindow.location.href,
        title: iframe.contentDocument.title,
        module: iframe.contentDocument.body.querySelector('.module[data-module-name]')?.getAttribute('data-module-name')
      };
    } catch (e) {
      console.error('Failed to access contentWindow of module iframe – using a foreign origin?');
      return { url: this.endpoint, title: null };
    }
  }

  private _loaded({target}: Event) {
    const iframe = <HTMLIFrameElement> target;

    // The event handler for the "unload" event needs to be attached
    // after every iframe load (for the current iframes's contentWindow).
    this.registerUnloadHandler(iframe);

    const state = this.retrieveModuleStateFromIFrame(iframe);
    this.dispatch('typo3-iframe-loaded', state);
  }

  private _unload(e: Event, iframe: HTMLIFrameElement) {
    // Asynchronous execution needed because the URL changes immediately after
    // the `unload` event is dispatched, but has not been changed right now.
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
