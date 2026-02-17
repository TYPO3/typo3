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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage';
import ModuleMenu from '@typo3/backend/module-menu';
import { UrlFactory } from '@typo3/core/factory/url-factory';
import '@typo3/workspaces/element/workspace-top-indicator-element';
import labels from '~labels/workspaces.messages';

export const workspaceColors = ['red', 'orange', 'yellow', 'lime', 'green', 'teal', 'blue', 'indigo', 'purple', 'magenta'] as const;
export type WorkspaceColor = typeof workspaceColors[number];

export interface Workspace {
  id: number;
  title: string;
  color: string;
  description: string;
}

interface WorkspaceInfoResponse {
  current: Workspace;
  workspaces: Workspace[];
}

export class WorkspaceChangedEvent extends CustomEvent<void> {
  static readonly eventName = 'typo3:workspace:changed';

  constructor() {
    super(WorkspaceChangedEvent.eventName, {
      bubbles: true,
    });
  }
}

/**
 * WorkspaceState - Service for workspace state management.
 *
 * Provides:
 * - Current workspace information
 * - List of available workspaces
 * - Workspace switching functionality
 * - Event dispatch on workspace changes
 */
class WorkspaceStateService {

  private currentWorkspace: Workspace | null = null;
  private workspaces: Workspace[] = [];
  private initialized: boolean = false;
  private initPromise: Promise<void> | null = null;

  constructor() {
    // Listen for workspace data refresh signals from the backend
    document.addEventListener('typo3:workspaces:refresh', () => {
      void this.refresh();
    });

    // Initialize immediately to fetch workspace data and show indicator
    void this.initialize();
  }

  private get defaultWorkspace(): Workspace {
    return {
      id: 0,
      title: labels.get('workspaceInfo.live.title'),
      color: 'red',
      description: labels.get('workspaceInfo.live.description'),
    };
  }

  /**
   * Initialize the workspace state by fetching from the server.
   * Called automatically on first access, but can be called explicitly.
   */
  public async initialize(): Promise<void> {
    if (this.initialized) {
      return;
    }
    if (this.initPromise) {
      return this.initPromise;
    }

    this.initPromise = this.fetchWorkspaceInfo();
    await this.initPromise;
    this.initialized = true;

    this.ensureWorkspaceIndicator();
  }

  /**
   * Get the current workspace information.
   */
  public async getCurrentWorkspace(): Promise<Workspace> {
    await this.initialize();
    return this.currentWorkspace!;
  }

  /**
   * Get all available workspaces for the current user.
   */
  public async getWorkspaces(): Promise<Workspace[]> {
    await this.initialize();
    return this.workspaces;
  }

  /**
   * Check if currently in a workspace (not live).
   */
  public async isInWorkspace(): Promise<boolean> {
    const current = await this.getCurrentWorkspace();
    return current.id > 0;
  }

  /**
   * Switch to a different workspace.
   * Dispatches 'typo3:workspace:changed' event on success.
   */
  public async switchWorkspace(workspaceId: number): Promise<void> {
    const currentWorkspaceId = this.currentWorkspace?.id ?? 0;
    if (currentWorkspaceId === workspaceId) {
      return;
    }

    try {
      const response: AjaxResponse = await (new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_switch)).post({
        workspaceId: workspaceId,
        pageId: ModuleStateStorage.current('web').identifier,
      });

      const data = await response.resolve();

      // If no workspaceId returned, keep current state
      if (data.workspaceId === undefined || data.workspaceId === null) {
        return;
      }

      // Update internal state
      const selectedWorkspace = this.workspaces.find(ws => ws.id === data.workspaceId);
      if (selectedWorkspace) {
        this.currentWorkspace = selectedWorkspace;
      }

      // Dispatch event for other components
      this.notifyChanged();

      // Handle navigation
      this.handlePostSwitchNavigation(data);
    } catch (error) {
      console.error('Failed to switch workspace', error);
      throw error;
    }
  }

  public async refresh(): Promise<void> {
    await this.fetchWorkspaceInfo();
    this.notifyChanged();
  }

  /**
   * Dispatches change event to all frames so UI components can reload data.
   */
  private notifyChanged(): void {
    document.dispatchEvent(new WorkspaceChangedEvent());
    for (let i = 0; i < window.frames.length; i++) {
      try {
        window.frames[i].document.dispatchEvent(new WorkspaceChangedEvent());
      } catch {
        // Cross-origin frame, skip
      }
    }
  }

  private async fetchWorkspaceInfo(): Promise<void> {
    try {
      const response: AjaxResponse = await (new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_info)).get();
      const data: WorkspaceInfoResponse = await response.resolve();

      this.currentWorkspace = data.current;
      this.workspaces = data.workspaces;

    } catch (error) {
      console.error('Failed to fetch workspace info', error);
      // Set defaults on error
      this.currentWorkspace = this.defaultWorkspace;
      this.workspaces = [];
    }
  }

  private ensureWorkspaceIndicator(): void {
    if (!document.querySelector('typo3-backend-workspace-top-indicator')) {
      const scaffoldState = document.querySelector('.t3js-scaffold-state');
      if (!scaffoldState) {
        return;
      }
      const indicator = document.createElement('typo3-backend-workspace-top-indicator');
      scaffoldState.insertBefore(indicator, scaffoldState.firstChild);
    }
  }

  private handlePostSwitchNavigation(data: { workspaceId: number; pageId?: number; pageModule?: string }): void {
    const currentModule = ModuleMenu.App.getCurrentModule();

    if (data.pageId) {
      const url = UrlFactory.createUrl(TYPO3.Backend.ContentContainer.getUrl(), {
        id: data.pageId,
      });
      import('@typo3/backend/viewport').then(({ default: Viewport }): void => {
        Viewport.ContentContainer.setUrl(url);
      });
    } else if (currentModule === 'workspaces_publish') {
      ModuleMenu.App.showModule(currentModule, 'workspace=' + data.workspaceId);
    } else if (currentModule?.startsWith('web_')) {
      ModuleMenu.App.reloadFrames();
    } else if (data.pageModule) {
      ModuleMenu.App.showModule(data.pageModule);
    }

    // Refresh the pagetree if visible
    document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
    // Reload the module menu
    ModuleMenu.App.refreshMenu();
  }
}

/**
 * Returns singleton instance from top frame, creating it if needed.
 */
function getOrCreateInstance(): WorkspaceStateService {
  if (top?.TYPO3?.WorkspaceState) {
    return top.TYPO3.WorkspaceState;
  }
  const instance = new WorkspaceStateService();
  if (top?.TYPO3) {
    top.TYPO3.WorkspaceState = instance;
  }

  return instance;
}

export default getOrCreateInstance();
