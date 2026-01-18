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

import { html, nothing, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import { NavigationToggleEvent, NavigationStateChangeEvent, ContentNavigationSlotEnum, type ContentNavigation } from './content-navigation';
import '@typo3/backend/element/icon-element';

export enum ContentNavigationToggleActionEnum {
  collapse = 'collapse',
  expand = 'expand',
}

interface ContentNavigationToggleContext {
  contentNavigation: ContentNavigation;
  slot: ContentNavigationSlotEnum;
}

/**
 * Module: @typo3/backend/viewport/content-navigation-toggle
 *
 * A toggle button component that can be placed anywhere within or related to
 * a content-navigation component. It shows/hides based on the navigation state
 * and renders the appropriate icon and title based on the action.
 *
 * @example
 * <!-- Collapse button in tree toolbar -->
 * <typo3-backend-content-navigation-toggle action="collapse"></typo3-backend-content-navigation-toggle>
 *
 * @example
 * <!-- Expand button in docheader -->
 * <typo3-backend-content-navigation-toggle action="expand"></typo3-backend-content-navigation-toggle>
 */
@customElement('typo3-backend-content-navigation-toggle')
export class ContentNavigationToggle extends PseudoButtonLitElement {
  @property({ type: String }) action?: ContentNavigationToggleActionEnum;

  @state() private context: ContentNavigationToggleContext | null = null;

  private mutationObserver: MutationObserver | null = null;
  private resizeObserver: ResizeObserver | null = null;
  private readonly boundStateChangeHandler = this.handleStateChange.bind(this);
  private readonly boundFocusRequestHandler = this.handleFocusRequest.bind(this);

  public override connectedCallback(): void {
    super.connectedCallback();
    this.hidden = true;

    if (!this.action) {
      console.error('<typo3-backend-content-navigation-toggle> requires an "action" attribute (collapsed or expanded)');
      return;
    }

    this.discoverContext();
    this.setupStateSync();
    this.setupFocusListener();
    this.setupResizeObserver();
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.cleanupStateSync();
    this.cleanupFocusListener();
    this.cleanupResizeObserver();
  }

  protected override render(): TemplateResult {
    if (!this.context || !this.action) {
      return html`${nothing}`;
    }

    const iconIdentifier = this.action === ContentNavigationToggleActionEnum.collapse ? 'actions-panel-collapse-start' : 'actions-panel-expand-start';

    return html`<typo3-backend-icon identifier=${iconIdentifier} size="small"></typo3-backend-icon>`;
  }

  protected buttonActivated(): void {
    this.context?.contentNavigation.toggleNavigation();
  }

  private shouldBeVisible(): boolean {
    if (!this.context || !this.action) {
      return false;
    }

    const { contentNavigation } = this.context;

    if (this.action === ContentNavigationToggleActionEnum.collapse) {
      return contentNavigation.shouldShowCollapseButton();
    } else {
      return contentNavigation.shouldShowExpandButton();
    }
  }

  private shouldRender(): boolean {
    return this.context !== null && !this.hidden;
  }

  private discoverContext(): void {
    const contentNavigation = this.findContentNavigation();
    if (!contentNavigation) {
      return;
    }

    this.context = {
      contentNavigation,
      slot: this.detectSlot(contentNavigation)
    };
    this.updateVisibility();
  }

  private findContentNavigation(): ContentNavigation | null {
    const directNav = this.closest('typo3-backend-content-navigation') as ContentNavigation | null;
    if (directNav) {
      return directNav;
    }

    try {
      const iframe = window.frameElement;
      if (iframe) {
        return iframe.closest('typo3-backend-content-navigation') as ContentNavigation | null;
      }
    } catch {
      // Cross-origin iframe access denied
    }

    return null;
  }

  private detectSlot(contentNav: ContentNavigation): ContentNavigationSlotEnum {
    let element = this.parentElement;
    while (element !== null) {
      if (element.parentElement === contentNav) {
        const slotName = element.getAttribute('slot');
        if (slotName === ContentNavigationSlotEnum.navigation) {
          return ContentNavigationSlotEnum.navigation;
        }
        break;
      }
      element = element.parentElement;
    }
    return ContentNavigationSlotEnum.content;
  }

  private getTargetDocument(): Document {
    try {
      return window.top?.document ?? document;
    } catch {
      return document;
    }
  }

  private setupStateSync(): void {
    if (!this.context) {
      return;
    }

    const { contentNavigation } = this.context;

    this.mutationObserver = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.type === 'attributes') {
          this.updateVisibility();
        }
      }
    });

    this.mutationObserver.observe(contentNavigation, {
      attributes: true,
      attributeFilter: ['navigation-collapsed', 'navigation-hidden']
    });

    this.getTargetDocument().addEventListener(NavigationStateChangeEvent.eventName, this.boundStateChangeHandler);
  }

  private cleanupStateSync(): void {
    if (this.mutationObserver) {
      this.mutationObserver.disconnect();
      this.mutationObserver = null;
    }

    this.getTargetDocument().removeEventListener(NavigationStateChangeEvent.eventName, this.boundStateChangeHandler);
  }

  private handleStateChange(event: Event): void {
    const { contentNavigation } = this.context || {};

    if (contentNavigation && event.target === contentNavigation) {
      this.updateVisibility();
    }
  }

  private updateVisibility(): void {
    this.hidden = !this.shouldBeVisible();
    this.updateTitle();
  }

  private updateTitle(): void {
    if (!this.context || !this.action) {
      return;
    }

    const { contentNavigation } = this.context;
    this.title = this.action === ContentNavigationToggleActionEnum.collapse
      ? contentNavigation.navigationLabelCollapse
      : contentNavigation.navigationLabelExpand;
  }

  private setupFocusListener(): void {
    if (!this.context) {
      return;
    }

    this.context.contentNavigation.addEventListener(
      NavigationToggleEvent.eventName,
      this.boundFocusRequestHandler
    );
  }

  private cleanupFocusListener(): void {
    if (!this.context) {
      return;
    }

    this.context.contentNavigation.removeEventListener(
      NavigationToggleEvent.eventName,
      this.boundFocusRequestHandler
    );
  }

  private setupResizeObserver(): void {
    if (!this.context) {
      return;
    }

    this.resizeObserver = new ResizeObserver(() => {
      this.updateVisibility();
    });

    this.resizeObserver.observe(this.context.contentNavigation);
    this.updateVisibility();
  }

  private cleanupResizeObserver(): void {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
      this.resizeObserver = null;
    }
  }

  private handleFocusRequest(event: NavigationToggleEvent): void {
    const { slot } = this.context || {};

    if (slot === event.detail.focusTarget && this.shouldRender()) {
      this.updateComplete.then(() => {
        this.focus();
      });
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-content-navigation-toggle': ContentNavigationToggle;
  }
}
