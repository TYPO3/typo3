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

import { ScaffoldIdentifierEnum } from '../enum/viewport/scaffold-identifier';
import PersistentStorage from '../storage/persistent';

export enum ScaffoldStateClass {
  sidebarExpanded = 'scaffold-sidebar-expanded',
  sidebarFlyout = 'scaffold-sidebar-flyout',
  toolbarExpanded = 'scaffold-toolbar-expanded',
}

export interface ScaffoldStateEventDetail {
  expanded: boolean;
}

export class ScaffoldSidebarToggleEvent extends CustomEvent<ScaffoldStateEventDetail> {
  static readonly eventName = 'typo3:scaffold:sidebar:toggle';

  constructor(expanded: boolean) {
    super(ScaffoldSidebarToggleEvent.eventName, {
      detail: { expanded },
      bubbles: true,
      composed: true,
    });
  }
}

export class ScaffoldToolbarToggleEvent extends CustomEvent<ScaffoldStateEventDetail> {
  static readonly eventName = 'typo3:scaffold:toolbar:toggle';

  constructor(expanded: boolean) {
    super(ScaffoldToolbarToggleEvent.eventName, {
      detail: { expanded },
      bubbles: true,
      composed: true,
    });
  }
}

/**
 * Event dispatched to request toggling the toolbar.
 */
export class ToolbarToggleRequestEvent extends CustomEvent<void> {
  static readonly eventName = 'typo3:scaffold:toolbar:toggle-request';

  constructor() {
    super(ToolbarToggleRequestEvent.eventName, {
      bubbles: true,
      composed: true,
    });
  }
}

/**
 * Event dispatched to request toggling the search.
 */
export class SearchToggleRequestEvent extends CustomEvent<void> {
  static readonly eventName = 'typo3:scaffold:search:toggle-request';

  constructor() {
    super(SearchToggleRequestEvent.eventName, {
      bubbles: true,
      composed: true,
    });
  }
}

/**
 * Centralized scaffold state management for sidebar and toolbar expansion.
 *
 * On large screens: sidebar can be expanded/collapsed (persisted).
 * On small screens: sidebar appears as flyout overlay (not persisted).
 */
export class ScaffoldState {
  private static readonly STORAGE_KEY = 'typo3-sidebar-collapsed';
  private static readonly LARGE_SCREEN_QUERY = '(min-width: 992px)';

  private static mediaQuery: MediaQueryList | null = null;
  private static sidebarPreferExpanded: boolean = true;

  public static isSidebarExpanded(): boolean {
    return this.getScaffold()?.classList.contains(ScaffoldStateClass.sidebarExpanded) ?? false;
  }

  public static isSidebarFlyout(): boolean {
    return this.getScaffold()?.classList.contains(ScaffoldStateClass.sidebarFlyout) ?? false;
  }

  public static isSidebarVisible(): boolean {
    if (this.isLargeScreen()) {
      return true;
    }
    return this.isSidebarFlyout();
  }

  public static isToolbarExpanded(): boolean {
    return this.getScaffold()?.classList.contains(ScaffoldStateClass.toolbarExpanded) ?? false;
  }

  public static isLargeScreen(): boolean {
    return window.matchMedia(this.LARGE_SCREEN_QUERY).matches;
  }

  public static initialize(): void {
    this.mediaQuery = window.matchMedia(this.LARGE_SCREEN_QUERY);
    this.mediaQuery.addEventListener('change', this.handleMediaQueryChange);

    if (document.body.dataset.context === 'install') {
      const collapsed = localStorage.getItem(this.STORAGE_KEY) === 'true';
      this.sidebarPreferExpanded = !collapsed;
    } else {
      this.sidebarPreferExpanded = this.isSidebarExpanded();
    }

    this.initializeOverlay();
    this.initializeEventListeners();
    this.applyStateForViewport();
  }

  public static toggleSidebar(expand?: boolean): void {
    const scaffold = this.getScaffold();
    if (!scaffold) {
      return;
    }

    if (this.isLargeScreen()) {
      this.toggleSidebarExpanded(expand);
    } else {
      this.toggleSidebarFlyout(expand);
    }
  }

  public static toggleToolbar(expand?: boolean): void {
    const scaffold = this.getScaffold();
    if (!scaffold) {
      return;
    }

    if (this.isLargeScreen()) {
      return;
    }

    if (typeof expand === 'undefined') {
      expand = !this.isToolbarExpanded();
    }

    scaffold.classList.toggle(ScaffoldStateClass.toolbarExpanded, expand);
    if (expand) {
      scaffold.classList.remove(ScaffoldStateClass.sidebarExpanded);
      this.toggleSidebarFlyout(false);
    }

    document.dispatchEvent(new ScaffoldToolbarToggleEvent(expand));
  }

  public static collapseAll(): void {
    const scaffold = this.getScaffold();
    if (!scaffold) {
      return;
    }

    scaffold.classList.remove(
      ScaffoldStateClass.sidebarExpanded,
      ScaffoldStateClass.sidebarFlyout,
      ScaffoldStateClass.toolbarExpanded
    );
  }

  public static toggleSidebarExpanded(expand?: boolean): void {
    const scaffold = this.getScaffold();
    if (!scaffold) {
      return;
    }

    expand = expand ?? !this.isSidebarExpanded();
    this.sidebarPreferExpanded = expand;

    scaffold.classList.toggle(ScaffoldStateClass.sidebarExpanded, expand);
    scaffold.classList.remove(ScaffoldStateClass.sidebarFlyout, ScaffoldStateClass.toolbarExpanded);
    this.persistSidebarState(expand);

    document.dispatchEvent(new ScaffoldSidebarToggleEvent(expand));
  }

  public static toggleSidebarFlyout(expand?: boolean): void {
    const scaffold = this.getScaffold();
    if (!scaffold) {
      return;
    }

    if (this.isLargeScreen()) {
      return;
    }

    expand = expand ?? !this.isSidebarFlyout();

    scaffold.classList.toggle(ScaffoldStateClass.sidebarFlyout, expand);
    if (expand) {
      scaffold.classList.remove(ScaffoldStateClass.sidebarExpanded, ScaffoldStateClass.toolbarExpanded);
    }

    document.dispatchEvent(new ScaffoldSidebarToggleEvent(expand));
  }

  private static initializeEventListeners(): void {
    document.addEventListener('typo3-module-load', () => {
      this.toggleSidebarFlyout(false);
      this.toggleToolbar(false);
    });
    document.addEventListener(ToolbarToggleRequestEvent.eventName, () => {
      this.toggleToolbar();
    });
    document.addEventListener(SearchToggleRequestEvent.eventName, () => {
      this.collapseAll();
    });
  }

  private static applyStateForViewport(): void {
    const scaffold = this.getScaffold();
    if (!scaffold) {
      return;
    }

    if (this.isLargeScreen()) {
      scaffold.classList.remove(ScaffoldStateClass.sidebarFlyout);
      scaffold.classList.toggle(ScaffoldStateClass.sidebarExpanded, this.sidebarPreferExpanded);
    } else {
      scaffold.classList.remove(ScaffoldStateClass.sidebarExpanded, ScaffoldStateClass.sidebarFlyout);
    }

    document.dispatchEvent(new ScaffoldSidebarToggleEvent(this.isSidebarVisible()));
  }

  private static readonly handleMediaQueryChange = (): void => {
    ScaffoldState.applyStateForViewport();
  };

  private static persistSidebarState(expanded: boolean): void {
    if (document.body.dataset.context === 'install') {
      localStorage.setItem(this.STORAGE_KEY, expanded ? 'false' : 'true');
    } else {
      PersistentStorage.set('BackendComponents.States.typo3-sidebar', { collapsed: !expanded });
    }
  }

  private static getScaffold(): Element | null {
    return document.querySelector(ScaffoldIdentifierEnum.scaffold);
  }

  private static initializeOverlay(): void {
    const overlay = document.querySelector('.scaffold-overlay');
    overlay?.addEventListener('click', (event: Event) => {
      event.preventDefault();
      ScaffoldState.toggleSidebar(false);
    });
  }
}
