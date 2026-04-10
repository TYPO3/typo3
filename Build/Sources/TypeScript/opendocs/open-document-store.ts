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
import { BroadcastMessage, type BroadcastEvent } from '@typo3/backend/broadcast-message';
import BroadcastService from '@typo3/backend/broadcast-service';
import type { BreadcrumbNodeInterface } from '@typo3/backend/element/breadcrumb';
import { topWindow } from '@typo3/core/utility/top-window';

export const OpenDocumentStoreChangedEvent = 'typo3:open-document-store:changed';

export interface OpenDocument {
  identifier: string;
  title: string;
  uri: string;
  iconIdentifier: string;
  iconOverlayIdentifier: string;
  breadcrumb: BreadcrumbNodeInterface[];
  updatedAt: string;
}

export interface OpenDocumentStoreData {
  recentDocuments: OpenDocument[];
}

interface OpenDocumentListResponse {
  success: boolean;
  recentDocuments: OpenDocument[];
}

/**
 * Central state management for recent documents with cached data,
 * cross-tab synchronization, and event-driven updates.
 */
class OpenDocumentStore {
  private readonly recentDocuments: Map<string, OpenDocument> = new Map();
  private isLoaded: boolean = false;
  private loadPromise: Promise<void> | null = null;

  constructor() {
    document.addEventListener('typo3:open-document:broadcast', (event) => this.handleBroadcast(event as BroadcastEvent<OpenDocumentStoreData>));
    document.addEventListener('typo3:opendocs:updateRequested', () => this.refresh());
  }

  public async getRecentDocuments(): Promise<OpenDocument[]> {
    await this.ready();
    return [...this.recentDocuments.values()];
  }

  /**
   * Clears cache and re-fetches all data from server.
   */
  public async refresh(): Promise<void> {
    this.isLoaded = false;
    this.loadPromise = null;
    await this.fetchAll();
  }

  /**
   * Navigates to a document using the module router.
   */
  public navigate(openDocument: OpenDocument): void {
    const router = document.querySelector('typo3-backend-module-router');
    if (router === null) {
      throw new Error('Router not available.');
    }

    const returnUrl = window.list_frame.document.location.pathname + window.list_frame.document.location.search;
    const targetUrl = new URL(openDocument.uri, window.location.origin);
    targetUrl.searchParams.set('returnUrl', returnUrl);

    router.setAttribute('endpoint', targetUrl.toString());
  }

  /**
   * Resolves when the store has been initialized or fetched.
   */
  private async ready(): Promise<void> {
    if (this.isLoaded) {
      return;
    }
    await this.fetchAll();
  }

  /**
   * Fetches documents from server if not already loaded.
   * Deduplicates concurrent requests.
   */
  private async fetchAll(): Promise<void> {
    if (this.isLoaded) {
      return;
    }

    if (!this.loadPromise) {
      this.loadPromise = this.doFetch();
    }
    await this.loadPromise;
  }

  /**
   * Synchronizes local cache with data from another tab's broadcast.
   */
  private handleBroadcast(event: BroadcastEvent<OpenDocumentStoreData>): void {
    this.load(event.detail.payload);
    this.notifyChanged(false);
  }

  /**
   * Replaces store contents with provided data.
   */
  private load(data: OpenDocumentStoreData): void {
    this.recentDocuments.clear();
    for (const openDocument of data.recentDocuments) {
      this.recentDocuments.set(openDocument.identifier, openDocument);
    }

    this.isLoaded = true;
  }

  /**
   * Dispatches change event to all frames and optionally broadcasts to other tabs.
   */
  private notifyChanged(broadcast: boolean = true): void {
    const event = new CustomEvent(OpenDocumentStoreChangedEvent);
    document.dispatchEvent(event);
    for (let i = 0; i < window.frames.length; i++) {
      try {
        window.frames[i].document.dispatchEvent(event);
      } catch {
        // Cross-origin frame, skip
      }
    }

    if (broadcast) {
      BroadcastService.post(new BroadcastMessage('open-document', 'broadcast', {
        recentDocuments: [...this.recentDocuments.values()],
      }));
    }
  }

  /**
   * Fetches document data from server and rebuilds local cache.
   */
  private async doFetch(): Promise<void> {
    try {
      const url = TYPO3.settings.ajaxUrls.opendocs_list;
      if (!url) {
        console.warn('OpenDocumentStore: opendocs_list URL not available yet');
        return;
      }

      const response: AjaxResponse = await new AjaxRequest(url).get({ cache: 'no-cache' });
      const data: OpenDocumentListResponse = await response.resolve();

      if (data.success) {
        this.load(data);
        this.notifyChanged(false);
      }
    } catch (error) {
      console.error('Failed to fetch documents:', error);
      throw error;
    }
  }
}

/**
 * Returns singleton instance from top frame, creating it if needed.
 */
function getOrCreateInstance(): OpenDocumentStore {
  try {
    if (topWindow.TYPO3?.OpenDocumentStore) {
      return topWindow.TYPO3.OpenDocumentStore;
    }
    const instance = new OpenDocumentStore();
    if (topWindow.TYPO3) {
      topWindow.TYPO3.OpenDocumentStore = instance;
    }
    return instance;
  } catch {
    // Cross-origin access denied, create local instance
    return new OpenDocumentStore();
  }
}

export default getOrCreateInstance();
