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
import { customElement, property, state } from 'lit/decorators.js';
import { classMap } from 'lit/directives/class-map.js';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import AjaxRequest from '@typo3/core/ajax/ajax-request';

export enum Types {
  iframe = 'iframe',
  content = 'content',
  ajax = 'ajax',
}

export enum Sizes {
  small = 'small',
  medium = 'medium',
  large = 'large',
}

export enum Placements {
  end = 'end',
  start = 'start',
  top = 'top',
  bottom = 'bottom',
}

export interface ContextPanelOptions {
  type?: Types;
  url?: string;
  content?: string;
  size?: Sizes;
  placement?: Placements;
  staticBackdrop?: boolean;
}

/**
 * Creates a context panel (offcanvas) element
 *
 * Handles module registration, element creation, and the open lifecycle.
 * Consumers only need to provide options and listen for events on the returned element.
 *
 * @example
 * const panel = await createContextPanel({ url: '/typo3/some-module' });
 * panel.addEventListener('typo3-context-panel-hidden', () => { ... });
 *
 * @example
 * const panel = await createContextPanel({ type: Types.ajax, url: '/typo3/ajax/endpoint', size: Sizes.small });
 *
 * @example
 * const panel = await createContextPanel({ type: Types.content, content: '<p>Hello</p>' });
 */
export async function createContextPanel(options: ContextPanelOptions): Promise<ContextPanelElement> {
  await topLevelModuleImport('@typo3/backend/element/context-panel.js');

  const panel = top.document.createElement('typo3-backend-context-panel') as ContextPanelElement;
  if (options.type) {
    panel.type = options.type;
  }
  if (options.url) {
    panel.url = options.url;
  }
  if (options.content) {
    panel.content = options.content;
  }
  if (options.size) {
    panel.size = options.size;
  }
  if (options.placement) {
    panel.placement = options.placement;
  }
  if (options.staticBackdrop !== undefined) {
    panel.staticBackdrop = options.staticBackdrop;
  }
  top.document.body.appendChild(panel);

  return new Promise((resolve) => {
    requestAnimationFrame(() => {
      panel.open();
      resolve(panel);
    });
  });
}

/**
 * Module: @typo3/backend/element/context-panel
 *
 * A standalone offcanvas panel web component that slides in from the
 * right edge of the viewport. Supports three content modes:
 *
 * - **iframe** (default): loads `url` in an iframe
 * - **content**: renders inline HTML (child elements or `content`)
 * - **ajax**: fetches `url` and renders the response as inline HTML
 *
 * Prefer using {@link createContextPanel} to create and open a panel.
 */
@customElement('typo3-backend-context-panel')
export class ContextPanelElement extends LitElement {
  @property({ type: String }) type: Types = Types.iframe;
  @property({ type: String }) url: string = '';
  @property({ type: String, attribute: false }) content: string = '';
  @property({ type: String }) size: Sizes = Sizes.medium;
  @property({ type: String }) placement: Placements = Placements.end;
  @property({ type: Boolean }) visible: boolean = false;
  @property({ type: Boolean }) staticBackdrop: boolean = true;

  @state() private activeUrl: string = '';

  private keydownHandler: ((e: KeyboardEvent) => void) | null = null;
  private ajaxLoaded: boolean = false;

  public open(): void {
    // Force the browser to compute the initial (off-screen) position before
    // setting visible. Without this, the first open after page load may skip
    // the slide-in transition because Lit's initial render and the visible
    // state change are batched into the same paint frame.
    const panel = this.querySelector('.context-panel') as HTMLElement | null;
    panel?.getBoundingClientRect();
    this.visible = true;
    panel?.focus();
    document.body.style.overflow = 'hidden';
    this.keydownHandler = (e: KeyboardEvent): void => {
      if (e.key === 'Escape') {
        // Do not close the panel when a modal is open on top
        if (document.querySelector('typo3-backend-modal')) {
          return;
        }
        e.preventDefault();
        this.close();
      }
    };
    document.addEventListener('keydown', this.keydownHandler);

    if (panel) {
      // Defer content loading until the slide-in transition has finished
      // to avoid a flicker between the empty panel and the loaded content.
      if (this.type !== Types.content) {
        panel.addEventListener('transitionend', () => {
          this.activeUrl = this.url;
          if (this.type === Types.ajax && !this.ajaxLoaded) {
            void this.loadAjaxContent();
          }
        }, { once: true });
      }
    } else if (this.type !== Types.content) {
      this.activeUrl = this.url;
      if (this.type === Types.ajax && !this.ajaxLoaded) {
        void this.loadAjaxContent();
      }
    }

    this.dispatchEvent(new CustomEvent('typo3-context-panel-shown', { bubbles: true }));
  }

  public close(): void {
    this.visible = false;
    this.activeUrl = '';
    document.body.style.overflow = '';
    if (this.keydownHandler) {
      document.removeEventListener('keydown', this.keydownHandler);
      this.keydownHandler = null;
    }
    // Wait for the slide-out transition, then dispatch hidden event
    const panel = this.querySelector('.context-panel') as HTMLElement | null;
    if (panel) {
      const handler = (): void => {
        panel.removeEventListener('transitionend', handler);
        this.dispatchEvent(new CustomEvent('typo3-context-panel-hidden', { bubbles: true }));
      };
      panel.addEventListener('transitionend', handler);
    } else {
      this.dispatchEvent(new CustomEvent('typo3-context-panel-hidden', { bubbles: true }));
    }
  }

  protected override createRenderRoot(): HTMLElement {
    return this;
  }

  protected override render(): TemplateResult | typeof nothing {
    if (this.type !== Types.content && !this.url) {
      return nothing;
    }

    const backdropClasses = {
      'context-panel-backdrop': true,
      'context-panel-visible': this.visible,
    };

    const panelClasses: Record<string, boolean> = {
      'context-panel': true,
      ['context-panel-' + this.placement]: true,
      'context-panel-visible': this.visible,
      ['context-panel-' + this.size]: true,
    };

    return html`
      <div class=${classMap(backdropClasses)} @click=${this.onBackdropClick}></div>
      <div class=${classMap(panelClasses)} tabindex="-1">
        <div class="context-panel-body">
          ${this.renderContent()}
        </div>
      </div>
    `;
  }

  protected override updated(): void {
    if (this.type === Types.content || this.type === Types.ajax) {
      const container = this.querySelector('.context-panel-content');
      if (container && container.innerHTML !== this.content) {
        container.innerHTML = this.content;
      }
    }
  }

  private renderContent(): TemplateResult | typeof nothing {
    switch (this.type) {
      case Types.iframe:
        return this.activeUrl
          ? html`<iframe src="${this.activeUrl}" name="context_panel_frame"></iframe>`
          : nothing;
      case Types.ajax:
      case Types.content:
        return html`<div class="context-panel-content"></div>`;
      default:
        return nothing;
    }
  }

  private async loadAjaxContent(): Promise<void> {
    try {
      const response = await new AjaxRequest(this.url).get();
      this.content = await response.resolve();
      this.ajaxLoaded = true;
    } catch {
      this.content = '<div class="alert alert-danger">Failed to load content.</div>';
    }
  }

  private onBackdropClick(): void {
    const event = new CustomEvent('typo3-context-panel-close-request', {
      bubbles: true,
      cancelable: true,
    });
    this.dispatchEvent(event);
    if (event.defaultPrevented) {
      return;
    }
    if (!this.staticBackdrop) {
      this.close();
      return;
    }
    const panel = this.querySelector('.context-panel') as HTMLElement | null;
    if (panel) {
      panel.classList.add('context-panel-shake');
      panel.addEventListener('animationend', () => {
        panel.classList.remove('context-panel-shake');
      }, { once: true });
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-context-panel': ContextPanelElement;
  }
}
