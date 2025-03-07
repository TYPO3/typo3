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

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import { lll } from '@typo3/core/lit-helper';
import Persistent from '../storage/persistent';
import '@typo3/backend/element/icon-element';

const selectorConverter = {
  fromAttribute(selector: string) {
    return document.querySelector(selector);
  }
};

enum ReadingModeDirection {
  ltr = 'ltr',
  rtl = 'rtl',
}

class ReadingMode {
  public static get(): ReadingModeDirection {
    return document.querySelector('html').dir === 'rtl' ? ReadingModeDirection.rtl : ReadingModeDirection.ltr;
  }
}

@customElement('typo3-backend-navigation-switcher')
export class ResizableNavigation extends LitElement {
  @property({ type: Number, attribute: 'minimum-width' }) minimumWidth: number = 250;
  @property({ type: Number, attribute: 'initial-width' }) initialWidth: number;
  @property({ type: String, attribute: 'persistence-identifier' }) persistenceIdentifier: string;

  @property({ attribute: 'parent', converter: selectorConverter }) parentContainer: HTMLElement;
  @property({ attribute: 'navigation', converter: selectorConverter }) navigationContainer: HTMLElement;

  @state() resizing: boolean = false;

  public override connectedCallback(): void {
    super.connectedCallback();
    const initialWidth = this.initialWidth || parseInt(Persistent.get(this.persistenceIdentifier), 10);
    this.setNavigationWidth(initialWidth);
    window.addEventListener('resize', this.fallbackNavigationSizeIfNeeded, { passive: true });
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    window.removeEventListener('resize', this.fallbackNavigationSizeIfNeeded);
  }

  // disable shadow dom
  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override async firstUpdated(): Promise<void> {
    // Give the browser a chance to paint
    await new Promise((r) => setTimeout(r, 0));
    // needed to avoid any issues related to browsers, as lit-decorators (eventOptions) do not work yet
    // properly https://lit-element.polymer-project.org/guide/events - @touchstart would throw warnings in browser console without passive=true
    this.querySelector('.scaffold-content-navigation-switcher-btn').addEventListener('touchstart', this.toggleNavigation, { passive: true });
    this.querySelector('.scaffold-content-navigation-drag').addEventListener('touchstart', this.startResizeNavigation, { passive: true });
  }

  protected override render(): TemplateResult {
    return html`
      <div class="scaffold-content-navigation-switcher">
        <button @click="${this.toggleNavigation}" class="btn btn-sm btn-default btn-borderless scaffold-content-navigation-switcher-btn scaffold-content-navigation-switcher-open" role="button" title="${lll('viewport_navigation_show')}">
          <typo3-backend-icon identifier="actions-chevron-right" size="small"></typo3-backend-icon>
        </button>
        <button @click="${this.toggleNavigation}" class="btn btn-sm btn-default btn-borderless scaffold-content-navigation-switcher-btn scaffold-content-navigation-switcher-close" role="button" title="${lll('viewport_navigation_hide')}">
          <typo3-backend-icon identifier="actions-chevron-left" size="small"></typo3-backend-icon>
        </button>
      </div>
      <div @mousedown="${this.startResizeNavigation}" class="scaffold-content-navigation-drag ${this.resizing ? 'resizing' : ''}"></div>
    `;
  }

  private readonly toggleNavigation = (event: MouseEvent | TouchEvent | KeyboardEvent) => {
    event.stopPropagation();
    this.parentContainer.classList.toggle('scaffold-content-navigation-expanded');

    if (event.currentTarget instanceof HTMLElement) {
      const sibling = (event.currentTarget.nextElementSibling ?? event.currentTarget.previousElementSibling) as HTMLElement;
      sibling.focus();
    }
  };

  private readonly fallbackNavigationSizeIfNeeded = (event: UIEvent) => {
    const window = <Window>event.currentTarget;
    if (this.getNavigationWidth() === 0) {
      return;
    }
    if (window.outerWidth < this.getNavigationWidth() + this.getNavigationPosition().left + this.minimumWidth) {
      this.autoNavigationWidth();
    }
  };

  private readonly handleMouseMove = (event: MouseEvent) => {
    this.resizeNavigation(<number>event.clientX);
  };

  private readonly handleTouchMove = (event: TouchEvent) => {
    this.resizeNavigation(<number>event.changedTouches[0].clientX);
  };

  private readonly resizeNavigation = (position: number) => {
    let width : number = 0;
    if (ReadingMode.get() === ReadingModeDirection.ltr) {
      width = Math.round(position) - Math.round(this.getNavigationPosition().left);
    } else {
      width = Math.round(this.getNavigationPosition().right) - Math.round(position);
    }

    this.setNavigationWidth(width);
  };

  private readonly startResizeNavigation = (event: MouseEvent | TouchEvent) => {
    if (event instanceof MouseEvent && event.button === 2) {
      return;
    }
    event.stopPropagation();
    this.resizing = true;
    document.addEventListener('mousemove', this.handleMouseMove, false);
    document.addEventListener('mouseup', this.stopResizeNavigation, false);
    document.addEventListener('touchmove', this.handleTouchMove, false);
    document.addEventListener('touchend', this.stopResizeNavigation, false);
  };

  private readonly stopResizeNavigation = () => {
    this.resizing = false;
    document.removeEventListener('mousemove', this.handleMouseMove, false);
    document.removeEventListener('mouseup', this.stopResizeNavigation, false);
    document.removeEventListener('touchmove', this.handleTouchMove, false);
    document.removeEventListener('touchend', this.stopResizeNavigation, false);
    Persistent.set(this.persistenceIdentifier, <string><unknown>this.getNavigationWidth());
    document.dispatchEvent(new CustomEvent('typo3:navigation:resized'));
  };

  private getNavigationPosition(): DOMRect {
    return this.navigationContainer.getBoundingClientRect();
  }

  private getNavigationWidth(): number {
    return <number>this.navigationContainer.offsetWidth;
  }

  private autoNavigationWidth(): void {
    this.navigationContainer.style.width = 'auto';
  }

  private setNavigationWidth(width: number): void {
    // Allow only 50% of the main document
    const maxWidth = Math.round(this.parentContainer.getBoundingClientRect().width / 2);
    if (width > maxWidth) {
      width = maxWidth;
    }
    width = width > this.minimumWidth ? width : this.minimumWidth;
    this.navigationContainer.style.width = width + 'px';
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-navigation-switcher': ResizableNavigation;
  }
}
