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

import { customElement, state, query } from 'lit/decorators.js';
import { html, LitElement, type TemplateResult, nothing } from 'lit';
import { classMap } from 'lit/directives/class-map.js';
import WorkspaceState, { type Workspace, type WorkspaceColor, workspaceColors, WorkspaceChangedEvent } from '@typo3/workspaces/workspace-state';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/dropdown';
import labels from '~labels/workspaces.messages';

/**
 * Module: @typo3/workspaces/element/workspace-selector-element
 *
 * Workspace selector UI component that displays the current workspace
 * and allows switching between available workspaces.
 *
 * Data is fetched from the WorkspaceState service (via AJAX endpoint).
 * Workspace switching is delegated to WorkspaceState which dispatches
 * 'typo3:workspace:switch' events for other components to react.
 *
 * @example
 * <typo3-backend-workspace-selector></typo3-backend-workspace-selector>
 *
 * @internal
 */
@customElement('typo3-backend-workspace-selector')
export class WorkspaceSelectorElement extends LitElement {
  @state() private loading: boolean = true;
  @state() private currentWorkspace: Workspace | null = null;
  @state() private workspaces: Workspace[] = [];

  @query('.dropdown-menu') private readonly dropdownMenu!: HTMLElement;

  public override connectedCallback(): void {
    super.connectedCallback();
    void this.loadWorkspaceData();
    document.addEventListener(WorkspaceChangedEvent.eventName, this.handleWorkspaceChanged);
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener(WorkspaceChangedEvent.eventName, this.handleWorkspaceChanged);
  }

  protected override createRenderRoot(): HTMLElement {
    return this;
  }

  protected override render(): TemplateResult {
    if (this.loading) {
      return html`
        <div class="workspace-selector-loading">
          <typo3-backend-spinner size="medium"></typo3-backend-spinner>
        </div>
      `;
    }

    const isLiveWorkspace = this.currentWorkspace === null || this.currentWorkspace.id === 0;
    const hasMultipleWorkspaces = this.workspaces.length > 1;
    const displayTitle = this.currentWorkspace?.title || (isLiveWorkspace ? 'Live' : '[No Title]');

    if (!hasMultipleWorkspaces) {
      return html`
        <span class="workspace-selector" disabled>
          <span class="workspace-selector-icon" aria-hidden="true">
            <typo3-backend-icon identifier="module-workspaces" size="medium"></typo3-backend-icon>
          </span>
          <span class="workspace-selector-name">${displayTitle}</span>
        </span>
      `;
    }

    return html`
      <div class="dropdown">
        <button
          class="workspace-selector dropdown-toggle"
          type="button"
          title="${labels.get('action.selectWorkspace')}"
          popovertarget="workspace-menu"
        >
          <span class="workspace-selector-icon" aria-hidden="true">
            <typo3-backend-icon identifier="module-workspaces" size="medium"></typo3-backend-icon>
          </span>
          <span class="workspace-selector-name">${displayTitle}</span>
          <span class="workspace-selector-indicator" aria-hidden="true">
            <typo3-backend-icon identifier="actions-exchange" size="small"></typo3-backend-icon>
          </span>
        </button>
        <div id="workspace-menu" class="dropdown-menu" popover>
          <ul class="dropdown-list">
            ${this.workspaces.map(workspace => html`<li>${this.renderWorkspaceItem(workspace)}</li>`)}
          </ul>
        </div>
      </div>
    `;
  }

  protected override updated(changedProperties: Map<string, unknown>): void {
    super.updated(changedProperties);

    // Update workspace color tokens
    if (changedProperties.has('currentWorkspace')) {
      if (this.currentWorkspace?.color && workspaceColors.includes(this.currentWorkspace.color as WorkspaceColor)) {
        const color = this.currentWorkspace.color;
        this.style.setProperty('--workspace-selector-active-color', `var(--typo3-state-${color}-color)`);
        this.style.setProperty('--workspace-selector-active-bg', `var(--typo3-state-${color}-bg)`);
        this.style.setProperty('--workspace-selector-active-border-color', `var(--typo3-state-${color}-border-color)`);
        this.style.setProperty('--workspace-selector-active-hover-color', `var(--typo3-state-${color}-hover-color)`);
        this.style.setProperty('--workspace-selector-active-hover-bg', `var(--typo3-state-${color}-hover-bg)`);
        this.style.setProperty('--workspace-selector-active-hover-border-color', `var(--typo3-state-${color}-hover-border-color)`);
        this.style.setProperty('--workspace-selector-active-focus-color', `var(--typo3-state-${color}-focus-color)`);
        this.style.setProperty('--workspace-selector-active-focus-bg', `var(--typo3-state-${color}-focus-bg)`);
        this.style.setProperty('--workspace-selector-active-focus-border-color', `var(--typo3-state-${color}-focus-border-color)`);
      } else {
        this.style.removeProperty('--workspace-selector-active-color');
        this.style.removeProperty('--workspace-selector-active-bg');
        this.style.removeProperty('--workspace-selector-active-border-color');
        this.style.removeProperty('--workspace-selector-active-hover-color');
        this.style.removeProperty('--workspace-selector-active-hover-bg');
        this.style.removeProperty('--workspace-selector-active-hover-border-color');
        this.style.removeProperty('--workspace-selector-active-focus-color');
        this.style.removeProperty('--workspace-selector-active-focus-bg');
        this.style.removeProperty('--workspace-selector-active-focus-border-color');
      }
    }
  }

  private renderWorkspaceItem(workspace: Workspace): TemplateResult {
    const isActive = workspace.id === this.currentWorkspace?.id;
    const itemStyle = workspace.color && workspaceColors.includes(workspace.color as WorkspaceColor) ? `border-inline-start-color: var(--typo3-state-${workspace.color}-bg)` : '';

    return html`
      <button
        type="button"
        class="${classMap({ 'dropdown-item': true, 'active': isActive })}"
        style="${itemStyle}"
        title="${workspace.description || workspace.title}"
        @click="${isActive ? nothing : () => this.switchWorkspace(workspace.id)}"
      >
        ${workspace.title}
      </button>
    `;
  }

  private async loadWorkspaceData(): Promise<void> {
    try {
      // Both methods share the same initialization, so sequential calls are efficient
      this.currentWorkspace = await WorkspaceState.getCurrentWorkspace();
      this.workspaces = await WorkspaceState.getWorkspaces();
    } catch (error) {
      console.error('Failed to load workspace data', error);
    } finally {
      this.loading = false;
    }
  }

  private readonly handleWorkspaceChanged = (): void => {
    this.dropdownMenu?.hidePopover();
    void this.loadWorkspaceData();
  };

  private async switchWorkspace(workspaceId: number): Promise<void> {
    try {
      await WorkspaceState.switchWorkspace(workspaceId);
    } catch (error) {
      console.error('Failed to switch workspace', error);
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-workspace-selector': WorkspaceSelectorElement;
  }
}
