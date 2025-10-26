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
import { customElement, property } from 'lit/decorators';
import { classMap } from 'lit/directives/class-map';
import ModuleMenu from '../module-menu';
import { InputTransformer } from '@typo3/core/ajax/input-transformer';
import '@typo3/backend/element/icon-element';
import 'bootstrap'; // for data-bs-toggle="dropdown"

interface BreadcrumbNodeInterface {
  identifier: string;
  label: string;
  icon: string|null;
  iconOverlay: string|null;
  forceShowIcon: boolean;
  route: BreadcrumbNodeRouteInterface|null;
  // calculated properties
  __width?: number;
}

interface BreadcrumbNodeRouteInterface {
  module: string;
  params: Record<string, string>,
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
  private identifier: string;
  private collapsedNodes: BreadcrumbNodeInterface[] = [];
  private resizeObserver!: ResizeObserver;
  private dropdownButtonWidth: number | null = null;
  private measurementContainer: HTMLElement | null = null;

  public override connectedCallback()
  {
    super.connectedCallback();
    this.identifier = Date.now().toString(36) + Math.random().toString(36).slice(2);
    this.resizeObserver = new ResizeObserver(() => this.adjustBreadcrumb());
    this.resizeObserver.observe(this);
  }

  public override disconnectedCallback()
  {
    this.resizeObserver.disconnect();
    super.disconnectedCallback();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override async willUpdate(changedProperties: PropertyValues) {
    if (changedProperties.has('nodes')) {
      await this.createMeasurementContainer();
      await this.measureDropdownToggle();
      await this.measureNodeWidths();
    }
  }

  protected override render(): TemplateResult
  {
    const visibleNodes = this.nodes.filter(
      (node) => !this.collapsedNodes.includes(node)
    );

    return html`
      <nav class="breadcrumb breadcrumb-collapsible${this.alignRight ? ' breadcrumb-right' : ''}" aria-label="${this.label}">
        ${this.collapsedNodes.length > 0 ? html`
          <div class="breadcrumb-item dropdown" role="presentation">
            ${this.renderDropdownToggle()}
            <div class="dropdown-menu">
              ${this.collapsedNodes.map((node) => this.renderDropdownItem(node))}
            </div>
          </div>
        ` : null}
        ${visibleNodes.map((node) => { /* eslint-disable @stylistic/indent */
          const classes = classMap({ 'breadcrumb-item': true, 'breadcrumb-item-first': this.isFirstNode(node), 'breadcrumb-item-last': this.isLastNode(node) });
          return html`<div class=${classes} role="presentation">${this.renderBreadcrumbElement(node)}</div>`;
        })}
      </nav>
    `;
  }

  private async createMeasurementContainer(): Promise<void>
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
  }

  private async measureDropdownToggle(): Promise<void>
  {
    if (this.dropdownButtonWidth === null) {
      const toggleTemplate = html`<div class="breadcrumb-item dropdown">${this.renderDropdownToggle()}</div>`;
      render(toggleTemplate, this.measurementContainer!);
      const toggleElement = this.measurementContainer!.querySelector('div');
      this.dropdownButtonWidth = toggleElement?.offsetWidth ?? 0;
      render(nothing, this.measurementContainer);
    }
  }

  private async measureNodeWidths(): Promise<void>
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
      render(template, this.measurementContainer!);
      const element = this.measurementContainer!.querySelector('div');
      const width = element?.offsetWidth ?? 0;
      render(nothing, this.measurementContainer);

      return { ...node, __width: width };
    });

    const hasChanged = !this.areNodeCollectionsEqual(originalNodes, updatedNodes);
    if (hasChanged) {
      this.nodes = updatedNodes;
    }
  }

  private async adjustBreadcrumb(): Promise<void>
  {
    const breadcrumbContainer = this.renderRoot?.querySelector('.breadcrumb');
    if (!breadcrumbContainer) {
      return;
    }

    this.collapsedNodes = [];
    const lastNode = this.nodes[this.nodes.length - 1];
    let totalWidth = lastNode?.__width || 0;

    for (const node of this.nodes.slice(0, -1).reverse()) {
      if (breadcrumbContainer.clientWidth > (totalWidth + this.dropdownButtonWidth + node.__width)) {
        totalWidth += node.__width;
      } else {
        this.collapsedNodes.push(node);
      }
    }

    this.collapsedNodes.reverse();
    this.requestUpdate();
  }

  private renderDropdownToggle(): TemplateResult
  {
    return html`
      <button
        type="button"
        id="breadcrumb-toggle-${this.identifier}"
        class="breadcrumb-element"
        aria-haspopup="true"
        aria-expanded="false"
        data-bs-toggle="dropdown"
      >
        ...
      </button>
    `;
  }

  private renderDropdownItem(node: BreadcrumbNodeInterface): TemplateResult|null
  {
    return node.route !== null ? html`
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
    return node.route !== null ? html`
      <button
        type="button"
        class="breadcrumb-element"
        title="${node.label}"
        aria-current=${this.isLastNode(node) ? 'page' : 'false'}
        @click=${(e: Event) => this.triggerAction(e, node)}
      >
        ${node.forceShowIcon || this.isLastNode(node) ? this.renderIcon(node) : nothing}
        <span class="breadcrumb-element-label">${node.label}</span>
      </button>
    ` : html `
      <div
        class="breadcrumb-element"
        title="${node.label}"
        aria-current=${this.isLastNode(node) ? 'page' : 'false'}
      >
        ${this.isLastNode(node) ? this.renderIcon(node) : nothing}
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
    const params = new URLSearchParams(InputTransformer.toSearchParams(node.route.params)).toString();
    ModuleMenu.App.showModule(
      node.route.module,
      params,
      e,
    );
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
