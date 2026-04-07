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

import { html, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import PersistentStorage from '@typo3/backend/storage/persistent';
import layoutLabels from '~labels/backend.layout';
import '@typo3/backend/element/icon-element';
import { HiddenContentCountChangedEvent } from './page-layout-event';

/**
 * Module: @typo3/backend/layout-module/toggle-hidden-element
 *
 * Toolbar toggle button for showing/hiding hidden content elements
 * in the page module. Updates its counter reactively when content
 * elements are toggled.
 */
@customElement('typo3-backend-page-layout-toggle-hidden')
export class PageLayoutToggleHidden extends PseudoButtonLitElement {
  @property({ type: Boolean, reflect: true }) active: boolean = false;
  @property({ type: Number }) count: number = 0;

  public override connectedCallback(): void {
    super.connectedCallback();
    this.syncDropdownToggleStatus();
    document.addEventListener(HiddenContentCountChangedEvent.eventName, this.onCountChanged);
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener(HiddenContentCountChangedEvent.eventName, this.onCountChanged);
  }

  public override updated(changedProperties: Map<string, unknown>): void {
    if (changedProperties.has('active')) {
      this.syncDropdownToggleStatus();
    }
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    return html`<span class="dropdown-item-status"></span><typo3-backend-icon identifier="actions-eye" size="small"></typo3-backend-icon>${layoutLabels.get('hiddenCE')} (${this.count})`;
  }

  protected override buttonActivated(): void {
    if (this.hasAttribute('disabled')) {
      return;
    }
    this.setAttribute('disabled', '');

    const show = !this.active;
    const hiddenElements = document.querySelectorAll('.t3js-hidden-record') as NodeListOf<HTMLElement>;

    for (const hiddenElement of hiddenElements) {
      hiddenElement.style.display = 'flow-root';
      const scrollHeight = hiddenElement.scrollHeight;

      // Always set `overflow: clip` after storing scrollHeight
      // * For hidden state `height: 0px` is already set.
      // * For visible state setting `overflow: clip` is fine anyway.
      hiddenElement.style.overflow = 'clip';

      if (!show) {
        // * Invisible elements must not be accessible/focusable by keyboard.
        // * Spacing between content elements is kept uniform by collapsed margins,
        //   hidden elements have a height of 0 and the margins of the surrounding elements
        //   cannot collapse, causing a visual gap.
        // Therefore do not display the element at all by setting `display: none`.
        hiddenElement.addEventListener('transitionend', (): void => {
          hiddenElement.style.display = 'none';
          hiddenElement.style.overflow = '';
        }, { once: true });

        // We use requestAnimationFrame() as we have to set the container's height at first before resizing to
        // collapsed-element-height. This results in a smooth animation.
        requestAnimationFrame((): void => {
          hiddenElement.style.height = scrollHeight + 'px';
          requestAnimationFrame((): void => {
            hiddenElement.style.height = '0px';
          });
        });
      } else {
        hiddenElement.addEventListener('transitionend', (): void => {
          hiddenElement.style.display = '';
          hiddenElement.style.overflow = '';
          hiddenElement.style.height = '';
        }, { once: true });

        hiddenElement.style.height = scrollHeight + 'px';
      }
    }

    this.active = show;
    PersistentStorage.set('moduleData.web_layout.showHidden', show ? '1' : '0').then((): void => {
      this.removeAttribute('disabled');
    });
  }

  private syncDropdownToggleStatus(): void {
    this.dataset.dropdowntoggleStatus = this.active ? 'active' : 'inactive';
  }

  private readonly onCountChanged = (event: Event): void => {
    this.count = (event as HiddenContentCountChangedEvent).detail.count;
  };
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-page-layout-toggle-hidden': PageLayoutToggleHidden;
  }
}
