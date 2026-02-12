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

import { customElement, state } from 'lit/decorators.js';
import { html, LitElement, type TemplateResult, css, nothing } from 'lit';
import WorkspaceState, { type Workspace, type WorkspaceColor, workspaceColors, WorkspaceChangedEvent } from '@typo3/workspaces/workspace-state';
import Persistent from '@typo3/backend/storage/persistent';
import '@typo3/backend/element/icon-element';
import { lll } from '@typo3/core/lit-helper';

/**
 * Module: @typo3/workspaces/element/workspace-top-indicator-element
 *
 * A top bar indicator that shows a colored line and workspace name badge.
 * Only visible when working in a workspace (not in Live).
 *
 * @example
 * <typo3-backend-workspace-top-indicator></typo3-backend-workspace-top-indicator>
 *
 * @internal
 */
@customElement('typo3-backend-workspace-top-indicator')
export class WorkspaceTopIndicatorElement extends LitElement {
  static override styles = css`
    :host {
      --indicator-font-size: .75rem;
      --indicator-color: color-mix(in srgb, var(--typo3-scaffold-sidebar-bg), var(--typo3-scaffold-sidebar-color) 95%);
      --indicator-bg: color-mix(in srgb, var(--typo3-scaffold-sidebar-bg), var(--typo3-scaffold-sidebar-color) 20%);
      --indicator-height: calc(var(--indicator-badge-height) * .75);
      --indicator-badge-padding-x: .75rem;
      --indicator-badge-padding-y: .35rem;
      --indicator-badge-height: calc(var(--indicator-font-size) + var(--indicator-badge-padding-y) * 2);
      --indicator-z-index: 10001;
      display: block;
      position: relative;
      color: var(--indicator-color);
      height: var(--indicator-height);
      background-color: var(--indicator-bg);
      font-size: var(--indicator-font-size);
      line-height: 1;
      z-index: var(--indicator-z-index);
      user-select: none;
      transition:
        margin-top .25s ease-out,
        background-color .25s ease-out,
        color .25s ease-out,
        display .25s ease-out allow-discrete;
    }

    @starting-style {
      :host {
        margin-top: calc(-1 * var(--indicator-height));
      }
    }

    :host([hidden]) {
      display: none;
      margin-top: calc(-1 * var(--indicator-height));
    }

    .workspace-indicator-badge {
      position: absolute;
      top: 0;
      left: var(--indicator-badge-padding-x);
      right: var(--indicator-badge-padding-x);
      width: fit-content;
      max-width: calc(100% - var(--indicator-badge-padding-x) * 2);
      margin-inline: auto;
      box-sizing: border-box;
      padding: var(--indicator-badge-padding-y) var(--indicator-badge-padding-x);
      border-radius: 0 0 .5rem .5rem;
      background-color: inherit;
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
      transition: background-color .25s ease-out;
    }
  `;

  @state() private currentWorkspace: Workspace | null = null;
  @state() private workspaces: Workspace[] = [];
  @state() private showLiveIndicator: boolean = !Persistent.isset('showWorkspaceLiveIndicator') || Persistent.get('showWorkspaceLiveIndicator') == 1;

  public override connectedCallback(): void {
    super.connectedCallback();
    void this.loadWorkspaceData();
    document.addEventListener(WorkspaceChangedEvent.eventName, this.handleWorkspaceChanged);
    document.addEventListener('typo3:persistent:update', this.handlePersistentUpdate);
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener(WorkspaceChangedEvent.eventName, this.handleWorkspaceChanged);
    document.removeEventListener('typo3:persistent:update', this.handlePersistentUpdate);
  }

  protected override render(): TemplateResult | typeof nothing {
    if (!this.currentWorkspace) {
      return nothing;
    }

    // Hide if in Live workspace and either only one workspace exists or user opted out
    this.hidden = this.currentWorkspace.id === 0 && (this.workspaces.length <= 1 || !this.showLiveIndicator);

    return html`
      <span class="workspace-indicator-badge" title="${this.currentWorkspace.description || this.currentWorkspace.title}">
        ${lll('workspaces.messages:indicator.workspacePrefix')}:
        ${this.currentWorkspace.title}
      </span>
    `;
  }

  protected override updated(): void {
    if (this.currentWorkspace?.color && workspaceColors.includes(this.currentWorkspace.color as WorkspaceColor)) {
      const color = this.currentWorkspace.color;
      this.style.setProperty('--indicator-color', `var(--typo3-state-${color}-color)`);
      this.style.setProperty('--indicator-bg', `var(--typo3-state-${color}-bg)`);
    } else {
      this.style.removeProperty('--indicator-color');
      this.style.removeProperty('--indicator-bg');
    }
  }

  private async loadWorkspaceData(): Promise<void> {
    try {
      this.currentWorkspace = await WorkspaceState.getCurrentWorkspace();
      this.workspaces = await WorkspaceState.getWorkspaces();
    } catch (error) {
      console.error('Failed to load workspace data', error);
    }
  }

  private readonly handleWorkspaceChanged = (): void => {
    void this.loadWorkspaceData();
  };

  private readonly handlePersistentUpdate = (e: Event): void => {
    const { fieldName, value } = (e as CustomEvent).detail;
    if (fieldName === 'showWorkspaceLiveIndicator') {
      this.showLiveIndicator = value == 1;
    }
  };
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-workspace-top-indicator': WorkspaceTopIndicatorElement;
  }
}
