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

import {html, customElement, property, internalProperty, eventOptions, LitElement, TemplateResult} from 'lit-element';
import {lll} from 'TYPO3/CMS/Core/lit-helper';
import Persistent = require('../Storage/Persistent');
import 'TYPO3/CMS/Backend/Element/IconElement';

const selectorConverter = {
  fromAttribute(selector: string) {
    return document.querySelector(selector);
  }
};

@customElement('typo3-backend-navigation-switcher')
class ResizableNavigation extends LitElement {
  @property({type: Number, attribute: 'minimum-width'}) minimumWidth: number = 250;
  @property({type: Number, attribute: 'initial-width'}) initialWidth: number;
  @property({type: String, attribute: 'persistence-identifier'}) persistenceIdentifier: string;

  @property({attribute: 'parent', converter: selectorConverter}) parentContainer: HTMLElement;
  @property({attribute: 'navigation', converter: selectorConverter}) navigationContainer: HTMLElement;

  @internalProperty() resizing: boolean = false;

  public connectedCallback(): void {
    super.connectedCallback();
    const initialWidth = this.initialWidth || parseInt(Persistent.get(this.persistenceIdentifier), 10);
    this.setNavigationWidth(initialWidth);
    window.addEventListener('resize', this.fallbackNavigationSizeIfNeeded, {passive: true});
  }

  public disconnectedCallback(): void {
    super.disconnectedCallback();
    window.removeEventListener('resize', this.fallbackNavigationSizeIfNeeded);
  }

  // disable shadow dom
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div class="scaffold-content-navigation-switcher">
        <button @mouseup="${this.toggleNavigation}" @touchstart="${this.toggleNavigation}" class="btn btn-default btn-borderless scaffold-content-navigation-switcher-btn scaffold-content-navigation-switcher-open" role="button" title="${lll('viewport_navigation_show')}">
          <typo3-backend-icon identifier="actions-chevron-right" size="small"></typo3-backend-icon>
        </button>
        <button @mouseup="${this.toggleNavigation}" @touchstart="${this.toggleNavigation}" class="btn btn-default btn-borderless scaffold-content-navigation-switcher-btn scaffold-content-navigation-switcher-close" role="button" title="${lll('viewport_navigation_hide')}">
          <typo3-backend-icon identifier="actions-chevron-left" size="small"></typo3-backend-icon>
        </button>
      </div>
      <div @mousedown="${this.startResizeNavigation}" @touchstart="${this.startResizeNavigation}" class="scaffold-content-navigation-drag ${this.resizing ? 'resizing' : ''}"></div>
    `;
  }

  private toggleNavigation = (event: MouseEvent | TouchEvent) => {
    if (event instanceof MouseEvent && event.button === 2) {
      return;
    }
    event.stopPropagation();
    this.parentContainer.classList.toggle('scaffold-content-navigation-expanded');
  }

  private fallbackNavigationSizeIfNeeded = (event: UIEvent) => {
    let window = <Window>event.currentTarget;
    if (this.getNavigationWidth() === 0) {
      return;
    }
    if (window.outerWidth < this.getNavigationWidth() + this.getNavigationPosition().left + this.minimumWidth) {
      this.autoNavigationWidth();
    }
  }

  private handleMouseMove = (event: MouseEvent) => {
    this.resizeNavigation(<number>event.clientX);
  }

  private handleTouchMove = (event: TouchEvent) => {
    this.resizeNavigation(<number>event.changedTouches[0].clientX);
  }

  private resizeNavigation = (position: number) => {
    let width = Math.round(position) - Math.round(this.getNavigationPosition().left);
    this.setNavigationWidth(width);
  }

  //@eventOptions({passive: true})
  private startResizeNavigation = (event: MouseEvent | TouchEvent) => {
    if (event instanceof MouseEvent && event.button === 2) {
      return;
    }
    event.stopPropagation();
    this.resizing = true;
    document.addEventListener('mousemove', this.handleMouseMove, false);
    document.addEventListener('mouseup', this.stopResizeNavigation, false);
    document.addEventListener('touchmove', this.handleTouchMove, false);
    document.addEventListener('touchend', this.stopResizeNavigation, false);
  }

  private stopResizeNavigation = () => {
    this.resizing = false;
    document.removeEventListener('mousemove', this.handleMouseMove, false);
    document.removeEventListener('mouseup', this.stopResizeNavigation, false);
    document.removeEventListener('touchmove', this.handleTouchMove, false);
    document.removeEventListener('touchend', this.stopResizeNavigation, false);
    Persistent.set(this.persistenceIdentifier, <string><unknown>this.getNavigationWidth());
    document.dispatchEvent(new CustomEvent('typo3:navigation:resized'));
  }

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
