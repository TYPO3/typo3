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

import { css, html, LitElement, type TemplateResult } from 'lit';
import { customElement, query, state } from 'lit/decorators.js';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/backend/layout-module/sticky-language-header
 *
 * Carries the header information of a language column twice: the default slot
 * stays in the flow, the "sticky" slot holds a copy that is placed below the
 * doc header, aligned with its column, for as long as the header itself is
 * scrolled out of view. Without a "sticky" slot the element is inert.
 */
@customElement('typo3-backend-page-layout-sticky-language-header')
export class PageLayoutStickyLanguageHeader extends LitElement {
  static override styles = css`
    /* the global border-box reset does not cross the shadow boundary */
    :host,
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    :host {
      display: block;
    }

    .sticky {
      display: flex;
      align-items: center;
      gap: .5rem;
      position: fixed;
      z-index: calc(var(--typo3-zindex-header) - 2);
      padding: .5rem .75rem;
      white-space: nowrap;
      overflow: hidden;
      pointer-events: none;
      color: var(--typo3-component-color);
      background-color: var(--typo3-component-bg);
      border: var(--typo3-component-border-width) solid var(--typo3-component-border-color);
      border-top: 0;
      border-radius: 0 0 var(--pagemodule-grid-cell-border-radius) var(--pagemodule-grid-cell-border-radius);
      box-shadow: var(--typo3-component-box-shadow-flyout);
      opacity: 0;
      /*
       * The doc header is overlapped by a pixel: it ends on a fractional
       * position, and two boxes meeting there round apart when they are
       * rasterized, which lets the page show through the seam. Offset via top,
       * a transform on a fixed element causes repaint artifacts.
       */
      top: calc(var(--sticky-top, 0px) - 1px - .5rem);
      transition: opacity .2s ease-out, top .2s ease-out;
    }

    .sticky-active {
      opacity: 1;
      top: calc(var(--sticky-top, 0px) - 1px);
    }

    ::slotted([slot="sticky"]) {
      display: flex;
      align-items: center;
      gap: .5rem;
      min-width: 0;
    }

    @media (prefers-reduced-motion: reduce) {
      .sticky {
        transition: none;
      }
    }
  `;

  // how far the box shadow reaches beyond the border box
  private static readonly shadowBleed: number = 64;

  @state() protected active: boolean = false;

  @query('.inline') private readonly inline: HTMLElement;
  @query('.sticky') private readonly sticky: HTMLElement;

  private scrollEvent: RegularEvent | null = null;
  private resizeEvent: RegularEvent | null = null;
  private ticking: boolean = false;

  public override connectedCallback(): void {
    super.connectedCallback();
    const sticky = this.querySelector(':scope > [slot="sticky"]');
    if (sticky === null) {
      return;
    }
    // the slot is rendered with [hidden] so it does not flash before the element
    // is upgraded, and the global reset declares that one !important
    sticky.removeAttribute('hidden');
    this.scrollEvent = new RegularEvent('scroll', this.scheduleSync, true);
    this.scrollEvent.bindTo(document);
    this.resizeEvent = new RegularEvent('resize', this.scheduleSync);
    this.resizeEvent.bindTo(window);
  }

  public override disconnectedCallback(): void {
    this.scrollEvent?.release();
    this.resizeEvent?.release();
    this.scrollEvent = null;
    this.resizeEvent = null;
    super.disconnectedCallback();
  }

  protected override firstUpdated(): void {
    this.scheduleSync();
  }

  protected override render(): TemplateResult {
    return html`
      <div class="inline"><slot></slot></div>
      <div class="sticky${this.active ? ' sticky-active' : ''}" aria-hidden="true">
        <slot name="sticky"></slot>
      </div>`;
  }

  private readonly scheduleSync = (): void => {
    if (this.ticking) {
      return;
    }
    this.ticking = true;
    requestAnimationFrame((): void => {
      this.ticking = false;
      this.sync();
    });
  };

  private sync(): void {
    const container = this.closest('.t3-grid-container');
    if (container === null || !this.inline || !this.sticky) {
      return;
    }
    // the lower edge of the doc header in viewport coordinates, which is where the
    // element is placed: whichever of its bars still reaches into the viewport wins,
    // so it holds for a missing button bar and for a bar that wrapped into two rows
    const topOffset: number = Math.max(0, ...Array.from(
      this.ownerDocument.querySelectorAll('.module-docheader'),
      (bar: Element): number => bar.getBoundingClientRect().bottom,
    ));
    const containerRect = container.getBoundingClientRect();
    const inlineRect = this.inline.getBoundingClientRect();
    this.active = inlineRect.bottom <= topOffset && containerRect.bottom > topOffset;
    if (!this.active) {
      return;
    }
    this.sticky.style.setProperty('--sticky-top', topOffset + 'px');
    this.sticky.style.left = inlineRect.left + 'px';
    this.sticky.style.width = inlineRect.width + 'px';
    const cutStart = Math.max(0, containerRect.left - inlineRect.left);
    const cutEnd = Math.max(0, inlineRect.right - containerRect.right);
    if (cutStart > 0 || cutEnd > 0) {
      // clip-path takes the border box as its reference, so the region has to reach
      // beyond it on every side that is not cut, or the box shadow is clipped away too
      const bleed = -PageLayoutStickyLanguageHeader.shadowBleed + 'px';
      const start = cutStart > 0 ? cutStart + 'px' : bleed;
      const end = cutEnd > 0 ? cutEnd + 'px' : bleed;
      this.sticky.style.clipPath = `inset(${bleed} ${end} ${bleed} ${start})`;
    } else {
      this.sticky.style.clipPath = '';
    }
    this.sticky.style.visibility = cutStart < inlineRect.width && cutEnd < inlineRect.width ? '' : 'hidden';
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-page-layout-sticky-language-header': PageLayoutStickyLanguageHeader;
  }
}
