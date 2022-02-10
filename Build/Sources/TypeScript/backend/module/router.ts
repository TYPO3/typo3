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
import {getRecordFromName, Module, ModuleState} from '../module';

const IFRAME_COMPONENT = '@typo3/backend/module/iframe';

interface DecoratedModuleState {
  slotName: string;
  detail: ModuleState;
}

// Trigger a render cycle, even if property has been reset to
// the current value (this is to trigger a module refresh).
const alwaysUpdate = (newVal: string, oldVal: string) => true;

/**
 * Module: @typo3/backend/module/router
 */
@customElement('typo3-backend-module-router')
export class ModuleRouter extends LitElement {

  @property({type: String, hasChanged: alwaysUpdate}) module: string = '';

  @property({type: String, hasChanged: alwaysUpdate}) endpoint: string = '';

  @property({type: String, attribute: 'state-tracker'}) stateTrackerUrl: string;

  @property({type: String, attribute: 'sitename'}) sitename: string;

  @property({type: Boolean, attribute: 'sitename-first'}) sitenameFirst: boolean;

  @query('slot', true) slotElement: HTMLSlotElement;

  public static styles = css`
    :host {
      width: 100%;
      min-height: 100%;
      flex: 1 0 auto;
      display: flex;
      flex-direction: row;
    }
    ::slotted(*) {
      min-height: 100%;
      width: 100%;
    }
  `;

  constructor() {
    super();

    this.addEventListener('typo3-module-load', ({target, detail}: CustomEvent<ModuleState>) => {
      const slotName = (target as HTMLElement).getAttribute('slot');
      this.pushState({ slotName, detail });
    });

    this.addEventListener('typo3-module-loaded', ({detail}: CustomEvent<ModuleState>) => {
      this.updateBrowserState(detail);
    });

    this.addEventListener('typo3-iframe-load', ({detail}: CustomEvent<ModuleState>) => {
      let state: DecoratedModuleState = {
        slotName: IFRAME_COMPONENT,
        detail: detail
      };

      if (state.detail.url.includes(this.stateTrackerUrl + '?state=')) {
        const parts = state.detail.url.split('?state=');
        state = <DecoratedModuleState>JSON.parse(decodeURIComponent(parts[1] || '{}'));
      }

      /*
       * Event came frame <typo3-iframe-module>, that means it may have been triggered by an
       * a) explicit iframe src attribute change or by
       * b) browser history backwards or forward navigation
       *
       * In case of b), the following code block manually synchronizes the slot attribute
       */
      if (this.slotElement.getAttribute('name') !== state.slotName) {
        // The "name" attribute of <slot> gets of out sync
        // due to browser history backwards or forward navigation.
        // Synchronize to the state as advertised by the iframe event.
        this.slotElement.setAttribute('name', state.slotName)
      }

      // Mark active and sync endpoint attribute for modules.
      // Do not reset endpoint for iframe modules as the URL has already been
      // updated and a reset would trigger a reload and another event cycle.
      this.markActive(
        state.slotName,
        this.slotElement.getAttribute('name') === IFRAME_COMPONENT ? null : state.detail.url,
        false
      );

      this.updateBrowserState(state.detail);

      // Send load event (e.g. to be handled by ModuleMenu).
      // Dispated via parent element to prevent routers own event handlers to be invoked.
      // @todo: Introduce a separate event (name) to prevent the parentElement workaround?
      this.parentElement.dispatchEvent(new CustomEvent<ModuleState>('typo3-module-load', {
        bubbles: true,
        composed: true,
        detail: state.detail
      }));
    });

    this.addEventListener('typo3-iframe-loaded', ({detail}: CustomEvent<ModuleState>) => {
      this.updateBrowserState(detail);
      this.parentElement.dispatchEvent(new CustomEvent<ModuleState>('typo3-module-loaded', {
        bubbles: true,
        composed: true,
        detail
      }));
    });
  }

  public render(): TemplateResult {
    const moduleData = getRecordFromName(this.module);
    const jsModule = moduleData.component || IFRAME_COMPONENT;

    return html`<slot name="${jsModule}"></slot>`;
  }

  protected updated(): void {
    const moduleData = getRecordFromName(this.module);
    const jsModule = moduleData.component || IFRAME_COMPONENT;

    this.markActive(jsModule, this.endpoint);
  }

  private async markActive(jsModule: string, endpoint: string|null, forceEndpointReset: boolean = true): Promise<void> {
    const element = await this.getModuleElement(jsModule);
    if (endpoint && (forceEndpointReset || element.getAttribute('endpoint') !== endpoint)) {
      element.setAttribute('endpoint', endpoint);
    }
    if (!element.hasAttribute('active')) {
      element.setAttribute('active', '');
    }
    for (let previous = element.previousElementSibling; previous !== null; previous = previous.previousElementSibling) {
      previous.removeAttribute('active');
    }
    for (let next = element.nextElementSibling; next !== null; next = next.nextElementSibling) {
      next.removeAttribute('active');
    }
  }

  private async getModuleElement(moduleName: string): Promise<Element> {
    let element = this.querySelector(`*[slot="${moduleName}"]`);
    if (element !== null) {
      return element;
    }

    try {
      const module = await import(moduleName + '.js');
      // @todo: Check if .componentName exists
      element = document.createElement(module.componentName);
    } catch (e) {
      console.error({msg: `Error importing ${moduleName} as backend module`, err: e})
      throw e;
    }

    element.setAttribute('slot', moduleName);
    this.appendChild(element);
    return element;
  }

  private async pushState(state: DecoratedModuleState): Promise<void> {
    const url = this.stateTrackerUrl + '?state=' + encodeURIComponent(JSON.stringify(state));
    // push dummy route to iframe. to trigger an implicit browser state update
    const component = await this.getModuleElement(IFRAME_COMPONENT);
    component.setAttribute('endpoint', url);
  }

  private updateBrowserState(state: ModuleState): void {
    const url = new URL(state.url || '', window.location.origin);
    const params = new URLSearchParams(url.search);

    const title = 'title' in state ? state.title : '';
    // update/reset document.title if state.title is not null
    // (state.title === null indicates "keep current title")
    if (title !== null) {
      const titleComponents = [ this.sitename ];
      if (title !== '') {
        titleComponents.unshift(title);
      }
      if (this.sitenameFirst) {
        titleComponents.reverse();
      }
      document.title = titleComponents.join(' · ');
    }

    if (!params.has('token')) {
      // non token-urls (e.g. backend install tool) cannot be mapped by
      // the main backend controller right now
      return;
    }

    params.delete('token');
    url.search = params.toString();

    const niceUrl = url.toString();
    window.history.replaceState(state, '', niceUrl);
  }

}
