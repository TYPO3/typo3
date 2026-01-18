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

import { html, css, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { PseudoButtonLitElement } from './pseudo-button';
import { ScaffoldState, ScaffoldSidebarToggleEvent } from '../viewport/scaffold-state';
import '@typo3/backend/element/icon-element';

/**
 * Module: @typo3/backend/element/sidebar-toggle-element
 *
 * Sidebar toggle button that displays different icons and labels based on sidebar state.
 *
 * @example
 * <typo3-backend-sidebar-toggle
 *   label-collapse="Collapse sidebar"
 *   label-expand="Expand sidebar"
 * ></typo3-backend-sidebar-toggle>
 */
@customElement('typo3-backend-sidebar-toggle')
export class SidebarToggleElement extends PseudoButtonLitElement {
  static override styles = [
    ...PseudoButtonLitElement.styles,
    css`
      :host {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        user-select: none;
      }
      :host([disabled]) {
        pointer-events: none;
        opacity: 0.5;
        cursor: default;
      }
    `
  ];

  @property({ type: String, attribute: 'label-collapse' }) labelCollapse: string = 'Collapse sidebar';
  @property({ type: String, attribute: 'label-expand' }) labelExpand: string = 'Expand sidebar';
  @property({ type: Boolean, reflect: true }) disabled: boolean = false;

  @state() private expanded: boolean = false;

  public override connectedCallback(): void {
    super.connectedCallback();
    this.expanded = ScaffoldState.isLargeScreen() ? ScaffoldState.isSidebarExpanded() : ScaffoldState.isSidebarVisible();
    this.updateLabels();
    document.addEventListener(ScaffoldSidebarToggleEvent.eventName, this.handleSidebarToggle as EventListener);
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener(ScaffoldSidebarToggleEvent.eventName, this.handleSidebarToggle as EventListener);
  }

  protected override updated(): void {
    this.updateLabels();
    this.updateDisabledState();
  }

  protected override render(): TemplateResult {
    const iconIdentifier = this.expanded ? 'actions-menu-sidebar-collapsed' : 'actions-menu-sidebar-expanded';
    const overlay = this.disabled ? 'overlay-readonly' : null;
    return html`<typo3-backend-icon identifier="${iconIdentifier}" size="small" overlay="${overlay}"></typo3-backend-icon>`;
  }

  protected override buttonActivated(): void {
    if (!this.disabled) {
      ScaffoldState.toggleSidebar();
    }
  }

  private readonly handleSidebarToggle = (event: ScaffoldSidebarToggleEvent): void => {
    this.expanded = event.detail.expanded;
  };

  private updateLabels(): void {
    const label = this.expanded ? this.labelCollapse : this.labelExpand;
    this.title = label;
    this.setAttribute('aria-label', label);
  }

  private updateDisabledState(): void {
    if (this.disabled) {
      this.setAttribute('aria-disabled', 'true');
      this.tabIndex = -1;
    } else {
      this.removeAttribute('aria-disabled');
      this.tabIndex = 0;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-sidebar-toggle': SidebarToggleElement;
  }
}
