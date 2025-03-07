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

import { html, css, LitElement, type TemplateResult, type HasChanged } from 'lit';
import { customElement, property, query } from 'lit/decorators';
import { ModuleUtility, type ModuleState } from '@typo3/backend/module';

const IFRAME_COMPONENT = '@typo3/backend/module/iframe';

interface DecoratedModuleState {
  slotName: string;
  detail: ModuleState;
}

// Trigger a render cycle, even if property has been reset to
// the current value (this is to trigger a module refresh).
const alwaysUpdate: HasChanged = () => true;

/**
 * Module: @typo3/backend/module/router
 */
@customElement('typo3-backend-module-router')
export class ModuleRouter extends LitElement {
  public static override styles = css`
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

  @property({ type: String, hasChanged: alwaysUpdate }) module: string = '';
  @property({ type: String, hasChanged: alwaysUpdate }) endpoint: string = '';
  @property({ type: String, attribute: 'state-tracker' }) stateTrackerUrl: string;
  @property({ type: String, attribute: 'sitename' }) sitename: string;
  @property({ type: String, attribute: 'entry-point' }) entryPoint: string;
  @property({ type: String, attribute: 'install-tool-path' }) installToolPath: string;
  @query('slot', true) slotElement: HTMLSlotElement;

  // Not a @property, since changes must not cause a module-reload
  sitenameFirst: boolean = false;
  titleComponents: string[]|null = null;

  constructor() {
    super();

    this.addEventListener('typo3-module-load', ({ target, detail }: CustomEvent<ModuleState>) => {
      const slotName = (target as HTMLElement).getAttribute('slot');
      this.pushState({ slotName, detail });
    });

    this.addEventListener('typo3-module-loaded', ({ detail }: CustomEvent<ModuleState>) => {
      this.updateBrowserState(detail);
    });

    this.addEventListener('typo3-iframe-load', ({ detail }: CustomEvent<ModuleState>) => {
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
        this.slotElement.setAttribute('name', state.slotName);
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

    this.addEventListener('typo3-iframe-loaded', ({ detail }: CustomEvent<ModuleState>) => {
      this.updateBrowserState(detail);
      this.parentElement.dispatchEvent(new CustomEvent<ModuleState>('typo3-module-loaded', {
        bubbles: true,
        composed: true,
        detail
      }));
    });
  }

  public static override get observedAttributes(): string[] {
    return [
      ...super.observedAttributes,
      'sitename-first',
    ];
  }

  public override connectedCallback(): void {
    super.connectedCallback();
    this.sitenameFirst = this.hasAttribute('sitename-first');
  }

  public override attributeChangedCallback(name: string, oldValue: string|null, newValue: string): void {
    super.attributeChangedCallback(name, oldValue, newValue);
    if (name === 'sitename-first') {
      this.sitenameFirst = newValue !== null;
      this.updateBrowserTitle();
    }
  }

  protected override render(): TemplateResult {
    const moduleData = ModuleUtility.getFromName(this.module);
    const jsModule = moduleData.component || IFRAME_COMPONENT;

    return html`<slot name="${jsModule}"></slot>`;
  }

  protected override updated(): void {
    const moduleData = ModuleUtility.getFromName(this.module);
    const jsModule = moduleData.component || IFRAME_COMPONENT;

    this.markActive(jsModule, this.endpoint);
  }

  private async markActive(jsModule: string, endpoint: string | null, forceEndpointReset: boolean = true): Promise<void> {
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
      element = this.querySelector(`*[slot="${moduleName}"]`);
      if (element !== null) {
        // The element has been created parallelly during the asynchronous module load; use that instance
        return element;
      }
      if (!('componentName' in module)) {
        throw new Error(`module ${moduleName} is missing the "componentName" export`);
      }
      element = document.createElement(module.componentName);
    } catch (e) {
      console.error({ msg: `Error importing ${moduleName} as backend module`, err: e });
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

  private updateBrowserTitle(): void {
    let { titleComponents } = this;

    if (titleComponents === null) {
      // updateBrowserState has not been invoked yet, nothing to update for now
      return;
    }

    if (this.sitenameFirst) {
      titleComponents = titleComponents.toReversed();
    }
    document.title = titleComponents.join(' · ');
  }

  private updateBrowserState(state: ModuleState): void {
    const url = new URL(state.url || '', window.location.origin);
    const params = new URLSearchParams(url.search);

    const title = 'title' in state ? state.title : '';
    // update/reset document.title if state.title is not null
    // (state.title === null indicates "keep current title")
    if (title !== null) {
      const titleComponents = [this.sitename];
      if (title !== '') {
        titleComponents.unshift(title);
      }
      this.titleComponents = titleComponents;
      this.updateBrowserTitle();
    }

    if (!params.has('token')) {
      // InstallTool doesn't use a backend-route with a token,
      // but has backend-routes that act as wrappers.
      // Rewrite the URL for display in the browser URL bar.
      // @todo: rewrite installtool as webcomponent backend
      // module in order to advertise a proper module URL on it's own
      if (params.has('install[controller]')) {
        const controller = params.get('install[controller]');
        params.delete('install[controller]');
        params.delete('install[context]');
        params.delete('install[colorScheme]');
        params.delete('install[theme]');
        url.pathname = url.pathname.replace(this.installToolPath, this.entryPoint + 'module/tools/' + controller);
      } else {
        // non token-urls cannot be mapped by
        // the main backend controller right now
        return;
      }
    }

    params.delete('token');
    url.search = params.toString();

    const niceUrl = url.toString();
    window.history.replaceState(state, '', niceUrl);
  }

}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-module-router': ModuleRouter;
  }
}
