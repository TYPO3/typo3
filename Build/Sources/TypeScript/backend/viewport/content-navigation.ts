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

import { html, css, LitElement, type PropertyValues, type TemplateResult } from 'lit';
import { customElement, property, query, state } from 'lit/decorators.js';
import { classMap } from 'lit/directives/class-map.js';
import { styleMap, type StyleInfo } from 'lit/directives/style-map.js';
import { lll } from '@typo3/core/lit-helper';
import Persistent from '../storage/persistent';

export enum ContentNavigationSlotEnum {
  navigation = 'navigation',
  content = 'content',
}

export class NavigationToggleEvent extends CustomEvent<{ focusTarget: ContentNavigationSlotEnum }> {
  static readonly eventName = 'typo3:content-navigation:toggle';

  constructor(focusTarget: ContentNavigationSlotEnum) {
    super(NavigationToggleEvent.eventName, {
      bubbles: false,
      detail: { focusTarget }
    });
  }
}

export class NavigationStateChangeEvent extends CustomEvent<{ collapsed: boolean; hidden: boolean; identifier: string }> {
  static readonly eventName = 'typo3:content-navigation:state-change';

  constructor(collapsed: boolean, hidden: boolean, identifier: string) {
    super(NavigationStateChangeEvent.eventName, {
      bubbles: true,
      composed: true,
      detail: { collapsed, hidden, identifier }
    });
  }
}

/**
 * Module: @typo3/backend/viewport/content-navigation
 *
 * @example
 * <typo3-backend-content-navigation
 *   identifier="my-view"
 *   navigation-title="Navigation"
 *   navigation-min-width="250"
 * >
 *   <div slot="navigation">
 *     Navigation content
 *   </div>
 *   <div slot="content">
 *     Main content (fills remaining space)
 *   </div>
 * </typo3-backend-content-navigation>
 */
@customElement('typo3-backend-content-navigation')
export class ContentNavigation extends LitElement {
  public static override styles = css`
    :host {
      --content-navigation-divider-color: transparent;
      --content-navigation-divider-color-active: currentColor;
      --content-navigation-divider-width: 0.5rem;
      --content-navigation-flyout-box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);

      display: flex;
      position: relative;
      width: 100%;
      height: 100%;
      container-type: inline-size;
    }

    :host([resizing]) {
      cursor: col-resize;
      user-select: none;
    }

    :host([resizing]) * {
      cursor: col-resize;
    }

    .panel {
      height: 100%;
    }

    .panel--navigation {
      position: relative;
      flex: 0 0 auto;
    }

    .panel--content {
      position: relative;
      flex: 1 1 auto;
      min-width: 0;
    }

    .panel--content ::slotted(*) {
      position: relative;
    }

    .panel--collapsed {
      display: none;
    }

    .divider {
      position: relative;
      z-index: 1;
      flex: 0 0 1px;
      background-color: var(--content-navigation-divider-color);
    }

    .divider-handle {
      position: absolute;
      inset-block: 0;
      inset-inline: calc(var(--content-navigation-divider-width) * -0.5);
      width: var(--content-navigation-divider-width);
      cursor: col-resize;
      touch-action: none;
      transition: background-color 0.2s ease-in-out;
    }

    .divider-handle:hover,
    .divider.resizing .divider-handle {
      background-color: var(--content-navigation-divider-color-active);
    }

    @container (max-width: 750px) {
      .panel--navigation {
        position: absolute;
        inset-block: 0;
        inset-inline-start: 0;
        z-index: 2;
        border-inline-end: 1px solid var(--content-navigation-divider-color);
        box-shadow: var(--content-navigation-flyout-box-shadow);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        max-width: 100%;
      }

      .panel--navigation.panel--collapsed {
        display: block;
        transform: translateX(-100%);
        box-shadow: none;
      }

      :host([dir="rtl"]) .panel--navigation.panel--collapsed,
      :host(:dir(rtl)) .panel--navigation.panel--collapsed {
        transform: translateX(100%);
      }

      .divider {
        display: none;
      }

      .panel--content {
        flex: 1 1 100%;
        z-index: 1;
      }
    }
  `;

  /**
   * Breakpoint at which the navigation switches to flyout mode.
   * Important: This value must match the container query in the styles above.
   */
  private static readonly FLYOUT_BREAKPOINT = 750;

  @property({ type: String }) identifier: string = '';
  @property({ type: Number, attribute: 'navigation-min-width' }) navigationMinWidth: number = 280;
  @property({ type: Number, attribute: 'navigation-max-width' }) navigationMaxWidth?: number;
  @property({ type: Number, attribute: 'navigation-initial-width' }) navigationInitialWidth?: number;
  @property({ type: Boolean, attribute: 'navigation-hidden', reflect: true }) navigationHidden: boolean = false;
  @property({ type: Boolean, attribute: 'navigation-collapsed', reflect: true }) navigationCollapsed: boolean = false;
  @property({ type: String, attribute: 'navigation-label-collapse' }) navigationLabelCollapse: string = lll('viewport.navigation.hide');
  @property({ type: String, attribute: 'navigation-label-expand' }) navigationLabelExpand: string = lll('viewport.navigation.show');
  @property({ type: Boolean, reflect: true }) resizing: boolean = false;

  @query('slot[name="navigation"]') readonly navigationSlot!: HTMLSlotElement | null;
  @query('slot[name="content"]') readonly contentSlot!: HTMLSlotElement | null;

  @state() private navigationWidth?: number;
  private resizeReferencePosition: number = 0;

  public override connectedCallback(): void {
    super.connectedCallback();
    this.loadPersistedWidth();
    window.addEventListener('resize', this.handleWindowResize, { passive: true });
    this.addEventListener('typo3:tree:node-selected', this.handleNodeSelected);
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    window.removeEventListener('resize', this.handleWindowResize);
    this.removeEventListener('typo3:tree:node-selected', this.handleNodeSelected);
  }

  public expandNavigation(): void {
    if (this.navigationCollapsed) {
      this.toggleNavigation();
    }
  }

  public collapseNavigation(): void {
    if (!this.navigationCollapsed) {
      this.toggleNavigation();
    }
  }

  public toggleNavigation(): void {
    this.navigationCollapsed = !this.navigationCollapsed;
    const focusTarget = this.navigationCollapsed ? ContentNavigationSlotEnum.content : ContentNavigationSlotEnum.navigation;
    this.updateComplete.then(() => {
      this.dispatchEvent(new NavigationToggleEvent(focusTarget));
    });
  }

  public isCollapsed(): boolean {
    return this.navigationCollapsed;
  }

  public showNavigation(): void {
    this.navigationHidden = false;
  }

  public hideNavigation(): void {
    this.navigationHidden = true;
  }

  public isNavigationHidden(): boolean {
    return this.navigationHidden;
  }

  public isFlyoutMode(): boolean {
    const containerWidth = this.getBoundingClientRect().width;
    return containerWidth < ContentNavigation.FLYOUT_BREAKPOINT;
  }

  public shouldShowCollapseButton(): boolean {
    if (this.navigationHidden) {
      return false;
    }
    return this.isFlyoutMode() || !this.navigationCollapsed;
  }

  public shouldShowExpandButton(): boolean {
    if (this.navigationHidden) {
      return false;
    }
    return this.isFlyoutMode() || this.navigationCollapsed;
  }

  public setNavigationWidth(width: number): void {
    const minWidth = this.navigationMinWidth;
    const maxWidth = this.getMaxWidth();
    width = Math.max(minWidth, Math.min(width, maxWidth));
    this.navigationWidth = width;
    this.updateNavigationElement(width);
  }

  protected override render(): TemplateResult {
    const width = this.navigationWidth || this.navigationInitialWidth;

    const navigationStyles: StyleInfo = {};
    if (!this.navigationHidden) {
      const isFlyout = this.isFlyoutMode();
      if (width) {
        const constrainedWidth = isFlyout ? width : Math.min(width, this.getMaxWidth());
        navigationStyles.width = `${constrainedWidth}px`;
      }
      if (this.navigationMinWidth) {
        navigationStyles.minWidth = `${this.navigationMinWidth}px`;
      }
      if (isFlyout) {
        navigationStyles.maxWidth = '100%';
      } else if (this.navigationMaxWidth) {
        navigationStyles.maxWidth = `${this.getMaxWidth()}px`;
      }
    }

    const navigationClasses = {
      'panel': true,
      'panel--navigation': true,
      'panel--collapsed': this.navigationCollapsed
    };

    const dividerClasses = {
      'divider': true,
      'resizing': this.resizing
    };

    return html`
      ${!this.navigationHidden ? html`
        <div
          class=${classMap(navigationClasses)}
          style=${styleMap(navigationStyles)}
          data-panel="navigation"
        >
          <slot name="navigation"></slot>
        </div>
        ${!this.navigationCollapsed ? html`
          <div class=${classMap(dividerClasses)}>
            <div
              class="divider-handle"
              @pointerdown=${this.startResize}
            ></div>
          </div>
        ` : ''}
      ` : ''}
      <div class="panel panel--content" data-panel="content">
        <slot name="content"></slot>
      </div>
    `;
  }

  protected override updated(changedProperties: PropertyValues<this>): void {
    super.updated(changedProperties);
    if (changedProperties.has('navigationCollapsed') || changedProperties.has('navigationHidden')) {
      this.dispatchEvent(new NavigationStateChangeEvent(
        this.navigationCollapsed,
        this.navigationHidden,
        this.identifier
      ));
    }
  }

  private getPersistenceKey(): string | null {
    if (!this.identifier) {
      return null;
    }
    return `resize.${this.identifier}.navigation`;
  }

  private loadPersistedWidth(): void {
    const key = this.getPersistenceKey();
    if (key) {
      const stored = Persistent.get(key);
      if (stored) {
        const width = parseInt(stored, 10);
        if (!isNaN(width) && width > 0) {
          this.navigationWidth = width;
          return;
        }
      }
    }
    if (this.navigationInitialWidth && !this.navigationWidth) {
      this.navigationWidth = this.navigationInitialWidth;
    }
  }

  private persistWidth(): void {
    const key = this.getPersistenceKey();
    if (!key) {
      return;
    }
    if (this.navigationWidth) {
      Persistent.set(key, String(this.navigationWidth));
    }
  }

  private getMaxWidth(): number {
    const containerWidth = this.getBoundingClientRect().width;
    let maxWidth = Math.round(containerWidth / 2);
    if (this.navigationMaxWidth) {
      maxWidth = Math.min(maxWidth, this.navigationMaxWidth);
    }
    return maxWidth;
  }

  private isRtl(): boolean {
    return getComputedStyle(this).direction === 'rtl';
  }

  private updateNavigationElement(width: number): void {
    const panelElement = this.shadowRoot?.querySelector('[data-panel="navigation"]') as HTMLElement;
    if (panelElement) {
      panelElement.style.width = `${width}px`;
    }
  }

  private readonly startResize = (event: PointerEvent) => {
    if (this.isFlyoutMode() || event.button !== 0) {
      return;
    }
    event.stopPropagation();
    event.preventDefault();

    const panelElement = this.shadowRoot?.querySelector('[data-panel="navigation"]') as HTMLElement;
    if (panelElement) {
      const rect = panelElement.getBoundingClientRect();
      this.resizeReferencePosition = this.isRtl() ? rect.right : rect.left;
    }

    this.resizing = true;
    const target = event.target as HTMLElement;
    target.setPointerCapture(event.pointerId);
    target.addEventListener('pointermove', this.handlePointerMove);
    target.addEventListener('pointerup', this.handlePointerUp);
    target.addEventListener('pointercancel', this.handlePointerUp);
    target.addEventListener('lostpointercapture', this.handlePointerUp);
  };

  private readonly handlePointerMove = (event: PointerEvent) => {
    this.resizeNavigation(event.clientX);
  };

  private readonly handlePointerUp = (event: PointerEvent) => {
    const target = event.currentTarget as HTMLElement;
    target.removeEventListener('pointermove', this.handlePointerMove);
    target.removeEventListener('pointerup', this.handlePointerUp);
    target.removeEventListener('pointercancel', this.handlePointerUp);
    target.removeEventListener('lostpointercapture', this.handlePointerUp);
    this.stopResize();
  };

  private readonly resizeNavigation = (position: number) => {
    if (!this.resizing) {
      return;
    }

    const panelElement = this.shadowRoot?.querySelector('[data-panel="navigation"]') as HTMLElement;
    if (!panelElement) {
      return;
    }

    const minWidth = this.navigationMinWidth;
    const maxWidth = this.getMaxWidth();

    let width = this.isRtl()
      ? Math.round(this.resizeReferencePosition - position)
      : Math.round(position - this.resizeReferencePosition);

    width = Math.max(minWidth, Math.min(width, maxWidth));
    panelElement.style.width = `${width}px`;
    this.navigationWidth = width;
  };

  private readonly stopResize = () => {
    this.resizing = false;
    this.persistWidth();
  };

  private readonly handleWindowResize = () => {
    if (this.navigationWidth && !this.isFlyoutMode()) {
      const maxWidth = this.getMaxWidth();
      if (this.navigationWidth > maxWidth) {
        this.setNavigationWidth(maxWidth);
      }
    }
  };

  private readonly handleNodeSelected = () => {
    if (this.isFlyoutMode() && !this.navigationCollapsed) {
      this.collapseNavigation();
    }
  };
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-content-navigation': ContentNavigation;
  }
}
