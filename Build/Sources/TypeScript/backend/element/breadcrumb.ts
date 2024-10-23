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

import { html, LitElement, type TemplateResult, render, type PropertyValues, nothing } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import { classMap } from 'lit/directives/class-map.js';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/dropdown';

export enum BreadcrumbMode {
  FULL = 'full',
  PATH = 'path',
}

export interface BreadcrumbNodeInterface {
  identifier: string;
  label: string;
  icon: string|null;
  iconOverlay: string|null;
  url: string|null;
  forceShowIcon: boolean;
  // calculated properties
  __width?: number;
}

/**
 * Module: @typo3/backend/element/breadcrumb
 *
 * @example
 * <typo3-breadcrumb
 *    nodes='[
 *      {
 *        "identifier": "12",
 *        "type": "page",
 *        "label": "Title",
 *        "icon": "icon-identifier",
 *        "iconOverlay": "icon-overlay-identifier",
 *      }
 *    ]'
 * ></typo3-breadcrumb>
 *
 * @internal this is subject to change
 */
@customElement('typo3-breadcrumb')
export class Breadcrumb extends LitElement
{
  @property({ type: Array }) nodes: BreadcrumbNodeInterface[] = [];
  @property({ type: Boolean }) alignRight: boolean = false;
  @property({ type: String }) label: string = 'Breadcrumb';
  @property({ type: String }) mode: BreadcrumbMode = BreadcrumbMode.FULL;
  private identifier: string;
  private collapsedNodes: BreadcrumbNodeInterface[] = [];
  private resizeObserver!: ResizeObserver;
  private intersectionObserver!: IntersectionObserver;
  private adjustRafId: number | null = null;
  private dropdownButtonWidth: number | null = null;
  private ellipsisWidth: number | null = null;
  private measurementContainer: HTMLElement | null = null;

  public override connectedCallback()
  {
    super.connectedCallback();
    this.identifier = Date.now().toString(36) + Math.random().toString(36).slice(2);
    this.resizeObserver = new ResizeObserver(() => this.scheduleAdjust());
    this.resizeObserver.observe(this);
    this.intersectionObserver = new IntersectionObserver((entries) => {
      if (entries.some(e => e.isIntersecting)) {
        this.scheduleAdjust();
      }
    });
    this.intersectionObserver.observe(this);
  }

  public override disconnectedCallback()
  {
    this.resizeObserver.disconnect();
    this.intersectionObserver.disconnect();
    if (this.adjustRafId !== null) {
      cancelAnimationFrame(this.adjustRafId);
      this.adjustRafId = null;
    }
    super.disconnectedCallback();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override willUpdate(changedProperties: PropertyValues) {
    if (changedProperties.has('nodes')) {
      this.createMeasurementContainer();
      this.measureEllipsis();
      this.measureDropdownToggle();
      this.measureNodeWidths();
    }
  }

  protected override render(): TemplateResult
  {
    const visibleNodes = this.nodes.filter(
      (node) => !this.collapsedNodes.includes(node)
    );

    const breadcrumbClasses = classMap({
      'breadcrumb': true,
      'breadcrumb-collapsible': true,
      'breadcrumb-right': this.alignRight,
      'breadcrumb-condensed': this.mode === BreadcrumbMode.PATH
    });

    const renderedList = html`
        ${this.renderCollapsedNodesIndicator()}
        ${visibleNodes.map((node) => { /* eslint-disable @stylistic/indent */
          const classes = classMap({ 'breadcrumb-item': true, 'breadcrumb-item-first': this.isFirstNode(node), 'breadcrumb-item-last': this.isLastNode(node) });
          return html`<div class=${classes} role="presentation">${this.renderBreadcrumbElement(node)}</div>`;
        })}
    `;

    // PATH mode is a purely presentational path label (e.g. for record list rows),
    // not a navigation element, so no <nav> landmark is used.
    return this.mode === BreadcrumbMode.FULL
      ? html`
        <nav class=${breadcrumbClasses} aria-label="${this.label}">
          ${renderedList}
        </nav>
      `
      : html`
        <span class="visually-hidden">
          ${this.label ? `${this.label}: ` : nothing}${this.nodes.map(n => n.label).join(' / ')}
        </span>
        <div class=${breadcrumbClasses} aria-hidden="true">
          ${renderedList}
        </div>
      `;
  }

  private scheduleAdjust(): void
  {
    if (this.adjustRafId !== null) {
      return;
    }
    this.adjustRafId = requestAnimationFrame(() => {
      this.adjustRafId = null;
      this.adjustBreadcrumb();
    });
  }

  private createMeasurementContainer(): void
  {
    if (this.measurementContainer === null) {
      this.measurementContainer = document.createElement('div');
      this.measurementContainer.classList.add('breadcrumb-measurement');
      this.measurementContainer.ariaHidden = 'true';
      this.measurementContainer.style.position = 'absolute';
      this.measurementContainer.style.visibility = 'hidden';
      this.measurementContainer.style.pointerEvents = 'none';
      this.measurementContainer.style.width = 'auto';
      this.measurementContainer.style.whiteSpace = 'nowrap';
      this.renderRoot.appendChild(this.measurementContainer);
    }

    // Path mode uses condensed styling
    this.measurementContainer.classList.toggle('breadcrumb-condensed', this.mode === BreadcrumbMode.PATH);
  }

  private measureDropdownToggle(): void
  {
    if (this.dropdownButtonWidth === null) {
      const toggleTemplate = html`<div class="breadcrumb-item dropdown">${this.renderDropdownToggle()}</div>`;
      render(toggleTemplate, this.measurementContainer);
      const toggleElement = this.measurementContainer.querySelector('div');
      this.dropdownButtonWidth = toggleElement?.offsetWidth ?? 0;
      render(nothing, this.measurementContainer);
    }
  }

  private measureEllipsis(): void
  {
    if (this.ellipsisWidth === null) {
      render(this.renderEllipsis(), this.measurementContainer);
      const ellipsisElement = this.measurementContainer.querySelector('div');
      this.ellipsisWidth = ellipsisElement?.offsetWidth ?? 0;
      render(nothing, this.measurementContainer);
    }
  }

  private measureNodeWidths(): void
  {
    const originalNodes = this.nodes;
    const updatedNodes = this.nodes.map(node => {
      // Skip nodes that already have a width
      if (node.__width) {
        return node;
      }

      const template = html`
        <div
          class=${classMap({ 'breadcrumb-item': true, 'breadcrumb-item-first': this.isFirstNode(node), 'breadcrumb-item-last': this.isLastNode(node) })}
          role="presentation"
        >
          ${this.renderBreadcrumbElement(node)}
        </div>
      `;
      render(template, this.measurementContainer);
      const element = this.measurementContainer.querySelector('div');
      const width = element?.offsetWidth ?? 0;
      render(nothing, this.measurementContainer);

      return { ...node, __width: width };
    });

    const hasChanged = !this.areNodeCollectionsEqual(originalNodes, updatedNodes);
    if (hasChanged) {
      this.nodes = updatedNodes;
    }
  }

  private adjustBreadcrumb(): void
  {
    const breadcrumbContainer = this.renderRoot?.querySelector('.breadcrumb');
    if (!breadcrumbContainer) {
      return;
    }

    // Indicator and node widths may be cached as 0 if the element was hidden
    // (e.g. inside a closed dropdown) during the initial willUpdate measurement.
    // Re-measure now that clientWidth may have changed to a real value.
    if (!this.ellipsisWidth) {
      this.ellipsisWidth = null;
      this.measureEllipsis();
    }
    if (!this.dropdownButtonWidth) {
      this.dropdownButtonWidth = null;
      this.measureDropdownToggle();
    }
    if (this.nodes.some(node => !node.__width)) {
      this.measureNodeWidths();
    }

    const collapseIndicatorWidth = this.mode === BreadcrumbMode.PATH ? this.ellipsisWidth : this.dropdownButtonWidth;

    // Walk from the last node toward the root, greedily keeping nodes that fit.
    // The indicator width is always reserved in the budget so that collapsing is
    // triggered slightly early rather than risking overflow once it appears.
    // Once a node doesn't fit, collapse it and every remaining node toward root
    // to ensure the visible set is always a contiguous run ending at the last node.
    // If no node triggers collapse the indicator is never rendered (collapsedNodes stays empty).
    const lastNode = this.nodes[this.nodes.length - 1];
    let totalWidth = lastNode?.__width || 0;
    let collapsing = false;
    const newCollapsedNodes: BreadcrumbNodeInterface[] = [];

    for (const node of this.nodes.slice(0, -1).reverse()) {
      if (!collapsing && breadcrumbContainer.clientWidth > totalWidth + collapseIndicatorWidth + node.__width) {
        totalWidth += node.__width;
      } else {
        collapsing = true;
        newCollapsedNodes.push(node);
      }
    }

    newCollapsedNodes.reverse();

    // Only re-render when the collapsed set actually changed, avoiding a full
    // Lit update on every RAF tick during resize when nothing has moved.
    const hasChanged = newCollapsedNodes.length !== this.collapsedNodes.length
      || newCollapsedNodes.some((node, i) => node !== this.collapsedNodes[i]);

    if (hasChanged) {
      this.collapsedNodes = newCollapsedNodes;
      this.requestUpdate();
    }
  }

  private renderCollapsedNodesIndicator(): TemplateResult | typeof nothing
  {
    if (this.collapsedNodes.length === 0) {
      return nothing;
    }

    return this.mode === BreadcrumbMode.PATH ? this.renderEllipsis() : this.renderCollapsedNodesDropdown();
  }

  private renderEllipsis(): TemplateResult
  {
    return html`
      <div class="breadcrumb-item breadcrumb-ellipsis" aria-hidden="true" title="${this.collapsedNodes.map(n => n.label).join(' / ')}">
        <span class="breadcrumb-element">...</span>
      </div>
    `;
  }

  private renderCollapsedNodesDropdown(): TemplateResult
  {
    return html`
      <div class="breadcrumb-item dropdown" role="presentation">
        ${this.renderDropdownToggle()}
        <div class="dropdown-menu" id="breadcrumb-menu-${this.identifier}" popover="auto">
          ${this.collapsedNodes.map((node) => this.renderDropdownItem(node))}
        </div>
      </div>
    `;
  }

  private renderDropdownToggle(): TemplateResult
  {
    return html`
      <button
        type="button"
        class="breadcrumb-element"
        popovertarget="breadcrumb-menu-${this.identifier}"
      >
        ...
      </button>
    `;
  }

  private renderDropdownItem(node: BreadcrumbNodeInterface): TemplateResult|null
  {
    return node.url !== null ? html`
      <button
        type="button"
        class="dropdown-item"
        title="${node.label}"
        @click=${(e: Event) => this.triggerAction(e, node)}
      >
          ${this.renderIcon(node)}
          <span class="dropdown-item-label">${node.label}</span>
      </button>
    ` : html`
      <div
        class="dropdown-item"
        title="${node.label}"
      >
          ${this.renderIcon(node)}
          <span class="dropdown-item-label">${node.label}</span>
      </div>
    `;
  }

  private renderBreadcrumbElement(node: BreadcrumbNodeInterface): TemplateResult|null
  {
    const shouldShowIcon = this.mode === BreadcrumbMode.FULL && (node.forceShowIcon || this.isLastNode(node));
    const shouldRenderButton = this.mode === BreadcrumbMode.FULL && node.url !== null;

    return shouldRenderButton ? html`
      <button
        type="button"
        class="breadcrumb-element"
        title="${node.label}"
        aria-current=${this.mode === BreadcrumbMode.FULL && this.isLastNode(node) ? 'page' : nothing}
        @click=${(e: Event) => this.triggerAction(e, node)}
      >
        ${shouldShowIcon ? this.renderIcon(node) : nothing}
        <span class="breadcrumb-element-label">${node.label}</span>
      </button>
    ` : html `
      <div
        class="breadcrumb-element"
        title="${node.label}"
        aria-current=${this.mode === BreadcrumbMode.FULL && this.isLastNode(node) ? 'page' : nothing}
      >
        ${shouldShowIcon ? this.renderIcon(node) : nothing}
        <span class="breadcrumb-element-label">${node.label}</span>
      </div>
    `;
  }

  private renderIcon(node: BreadcrumbNodeInterface): TemplateResult|null
  {
    return node.icon
      ? html`
          <typo3-backend-icon
            identifier="${node.icon}"
            overlay="${node.iconOverlay}"
            size="small"
          ></typo3-backend-icon>
        `
      : null;
  }

  private triggerAction(e: Event, node: BreadcrumbNodeInterface): void
  {
    import('@typo3/backend/viewport').then(({ default: Viewport }): void => {
      Viewport.ContentContainer.setUrl(node.url);
    });
  }

  private isFirstNode(node: BreadcrumbNodeInterface) {
    return this.nodes[0] === node;
  }

  private isLastNode(node: BreadcrumbNodeInterface) {
    return this.nodes[this.nodes.length - 1] === node;
  }

  private areNodeCollectionsEqual(nodes: BreadcrumbNodeInterface[], newNodes: BreadcrumbNodeInterface[]): boolean
  {
    if (newNodes.length !== nodes.length){
      return false;
    }

    for (let i = 0; i < newNodes.length; i++) {
      if (!this.areNodesEqual(nodes[i], newNodes[i])) {
        return false;
      }
    }

    return true;
  }

  private areNodesEqual(nodeA: BreadcrumbNodeInterface, nodeB: BreadcrumbNodeInterface): boolean
  {
    return nodeA.identifier === nodeB.identifier &&
           nodeA.label === nodeB.label &&
           nodeA.icon === nodeB.icon &&
           nodeA.iconOverlay === nodeB.iconOverlay &&
           nodeA.__width === nodeB.__width;
  }
}
