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
import { lll } from '@typo3/core/lit-helper';
import labels from '~labels/core.bookmarks';
import Modal from '../modal';
import Notification from '../notification';
import { SeverityEnum } from '../enum/severity';
import { BroadcastMessage, type BroadcastEvent } from '@typo3/backend/broadcast-message';
import BroadcastService from '@typo3/backend/broadcast-service';

export const BookmarkStoreChangedEvent = 'typo3:bookmark-store:changed';

/**
 * Number for system/global groups,
 * UUID string for user-created groups.
 */
export type BookmarkGroupId = number | string;

export enum BookmarkGroupType {
  SYSTEM = 'system',
  GLOBAL = 'global',
  USER = 'user',
}

export interface Bookmark {
  id: number;
  route: string;
  arguments: string;
  title: string;
  groupId: BookmarkGroupId;
  iconIdentifier: string;
  iconOverlayIdentifier: string;
  module: string;
  href: string;
  editable: boolean;
  accessible: boolean;
}

/**
 * Bookmark group with type information.
 *
 * Group types:
 * - system: Special reserved groups (e.g., Uncategorized with ID 0)
 * - global: Negative ID groups, admin-only for adding, visible to all users
 * - tsconfig: Groups defined via UserTSconfig (includes default bookmark groups)
 * - user: User-created groups with UUID identifiers
 */
export interface BookmarkGroup {
  id: BookmarkGroupId;
  label: string;
  type: BookmarkGroupType;
  priority: number;
  sorting: number;
  editable: boolean;
  selectable: boolean;
}

interface BookmarkListResponse {
  success: boolean;
  bookmarks: Bookmark[];
  groups: BookmarkGroup[];
}

interface BookmarkOperationResponse {
  success: boolean;
  error?: string;
  bookmark?: Bookmark;
}

interface BookmarkDeleteResponse {
  success: boolean;
  error?: string;
}

interface BookmarkGroupCreateResponse {
  success: boolean;
  group?: BookmarkGroup;
  error?: string;
}

interface BookmarkGroupOperationResponse {
  success: boolean;
  groups?: BookmarkGroup[];
  error?: string;
}

export interface BookmarkStoreData {
  bookmarks: Bookmark[];
  groups: BookmarkGroup[];
}

/**
 * Central state management for bookmarks with cached data, CRUD operations,
 * cross-tab synchronization, and event-driven updates.
 *
 * Single source of truth: bookmarks Map (maintains insertion order) for server-defined ordering.
 */
class BookmarkStore {
  private readonly bookmarks: Map<number, Bookmark> = new Map();
  private groups: BookmarkGroup[] = [];
  private isLoaded: boolean = false;
  private loadPromise: Promise<void> | null = null;

  constructor() {
    document.addEventListener('typo3:bookmark:broadcast', (event) => this.handleBroadcast(event as BroadcastEvent<BookmarkStoreData>));
  }

  /**
   * Generates a composite key for bookmark lookup by route and arguments.
   */
  public getBookmarkKey(route: string, args: string): string {
    return `${route}::${args}`;
  }

  public async isBookmarked(key: string): Promise<boolean> {
    return (await this.getBookmark(key)) !== null;
  }

  public async getBookmark(key: string): Promise<Bookmark | null> {
    await this.ready();
    for (const bookmark of this.bookmarks.values()) {
      if (this.getBookmarkKey(bookmark.route, bookmark.arguments) === key) {
        return bookmark;
      }
    }
    return null;
  }

  public async getBookmarks(): Promise<Bookmark[]> {
    await this.ready();
    return [...this.bookmarks.values()];
  }

  public async getGroups(): Promise<BookmarkGroup[]> {
    await this.ready();
    return [...this.groups];
  }

  /**
   * Returns bookmarks grouped by groupId, sorted by group priority.
   * Order: user → tsconfig → global → system.
   * Within each priority level, groups are sorted by their position in the groups array (server-defined order).
   * Optional filter by groupId and limit the total number of bookmarks.
   */
  public async getGroupedBookmarks(options?: { groupId?: BookmarkGroupId; limit?: number; accessibleOnly?: boolean }): Promise<Map<BookmarkGroupId, Bookmark[]>> {
    let bookmarks = await this.getBookmarks();

    if (options?.accessibleOnly) {
      bookmarks = bookmarks.filter((b: Bookmark) => b.accessible);
    }
    if (options?.groupId !== undefined) {
      bookmarks = bookmarks.filter((b: Bookmark) => b.groupId === options.groupId);
    }
    if (options?.limit !== undefined && options.limit > 0 && bookmarks.length > options.limit) {
      bookmarks = bookmarks.slice(0, options.limit);
    }

    const grouped = new Map<BookmarkGroupId, Bookmark[]>();

    // Build maps for sorting by priority and sorting
    const groupPriorityMap = new Map<BookmarkGroupId, number>();
    const groupSortingMap = new Map<BookmarkGroupId, number>();
    this.groups.forEach((group) => {
      groupPriorityMap.set(group.id, group.priority);
      groupSortingMap.set(group.id, group.sorting);
    });

    const groupIds = [...new Set(bookmarks.map((b: Bookmark) => b.groupId))];
    const sortedBookmarkGroupIds = groupIds.sort((a: BookmarkGroupId, b: BookmarkGroupId) => {
      // First sort by priority (lower values first)
      const priorityA = groupPriorityMap.get(a) ?? Number.MAX_SAFE_INTEGER;
      const priorityB = groupPriorityMap.get(b) ?? Number.MAX_SAFE_INTEGER;
      const priorityDiff = priorityA - priorityB;
      if (priorityDiff !== 0) {
        return priorityDiff;
      }

      // Within same priority, sort by sorting value
      const sortingA = groupSortingMap.get(a) ?? Number.MAX_SAFE_INTEGER;
      const sortingB = groupSortingMap.get(b) ?? Number.MAX_SAFE_INTEGER;
      return sortingA - sortingB;
    });

    for (const groupId of sortedBookmarkGroupIds) {
      const groupBookmarks = bookmarks.filter((b: Bookmark) => b.groupId === groupId);
      if (groupBookmarks.length > 0) {
        grouped.set(groupId, groupBookmarks);
      }
    }

    return grouped;
  }

  /**
   * Hydrates the store with server-rendered data, avoiding an initial AJAX request.
   * Should be called once during page initialization.
   */
  public initialize(bookmarks: Bookmark[], groups: BookmarkGroup[]): void {
    if (this.isLoaded) {
      return;
    }
    this.load({ bookmarks, groups });
  }

  /**
   * Creates bookmark on server and updates local cache.
   */
  public async create(
    routeIdentifier: string,
    args: string,
    displayName?: string
  ): Promise<BookmarkOperationResponse> {
    try {
      const response: AjaxResponse = await new AjaxRequest(
        TYPO3.settings.ajaxUrls.bookmark_create
      ).post({
        routeIdentifier,
        arguments: args,
        displayName: displayName || '',
      });

      const data: BookmarkOperationResponse = await response.resolve();

      if (data.success && data.bookmark) {
        this.bookmarks.set(data.bookmark.id, data.bookmark);
        this.notifyChanged();
      }

      return data;
    } catch (error) {
      console.error('Failed to create bookmark:', error);
      return {
        success: false,
        error: labels.get('error.createFailed.message'),
      };
    }
  }

  /**
   * Updates bookmark title and group on server and synchronizes local cache.
   */
  public async update(
    id: number,
    title: string,
    groupId: BookmarkGroupId
  ): Promise<BookmarkOperationResponse> {
    try {
      const response: AjaxResponse = await new AjaxRequest(
        TYPO3.settings.ajaxUrls.bookmark_update
      ).post({
        bookmarkId: id,
        bookmarkTitle: title,
        bookmarkGroup: groupId,
      });

      const data: BookmarkOperationResponse = await response.resolve();

      if (data.success && data.bookmark) {
        this.bookmarks.set(data.bookmark.id, data.bookmark);
        this.notifyChanged();
      }

      return data;
    } catch (error) {
      console.error('Failed to update bookmark:', error);
      return {
        success: false,
        error: labels.get('error.updateFailed.message'),
      };
    }
  }

  /**
   * Shows confirmation dialog and deletes bookmark if confirmed.
   */
  public requestDelete(id: number): void {
    const bookmark = this.bookmarks.get(id);
    if (!bookmark) {
      return;
    }

    const confirmModal = Modal.confirm(
      labels.get('delete'),
      labels.get('confirmDelete.title', bookmark.title),
      SeverityEnum.warning,
      [
        {
          text: lll('button.cancel'),
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: () => confirmModal.hideModal(),
        },
        {
          text: lll('button.delete'),
          btnClass: 'btn-danger',
          name: 'delete',
          trigger: async () => {
            confirmModal.hideModal();
            const result = await this.delete(id);
            if (result.success) {
              Notification.success(
                labels.get('success.deleted.title'),
                labels.get('success.deleted.message')
              );
            } else {
              Notification.error(
                labels.get('error.deleteFailed.title'),
                result.error || labels.get('error.deleteFailed.message')
              );
            }
          },
        },
      ]
    );
  }

  /**
   * Deletes a bookmark from the server and local cache.
   */
  public async delete(id: number): Promise<BookmarkDeleteResponse> {
    try {
      const response: AjaxResponse = await new AjaxRequest(
        TYPO3.settings.ajaxUrls.bookmark_delete
      ).post({ bookmarkId: id });

      const data: BookmarkDeleteResponse = await response.resolve();

      if (data.success) {
        this.bookmarks.delete(id);
        this.notifyChanged();
      }

      return data;
    } catch (error) {
      console.error('Failed to delete bookmark:', error);
      return {
        success: false,
        error: labels.get('error.deleteFailed.message')
      };
    }
  }

  /**
   * Persists new bookmark order and updates local cache with server response.
   */
  public async reorder(ids: number[]): Promise<boolean> {
    try {
      const response: AjaxResponse = await new AjaxRequest(
        TYPO3.settings.ajaxUrls.bookmark_reorder
      ).post({
        bookmarkIds: ids,
      });

      const data = await response.resolve();

      if (data.success && data.bookmarks) {
        this.bookmarks.clear();
        for (const bookmark of data.bookmarks) {
          this.bookmarks.set(bookmark.id, bookmark);
        }
        this.notifyChanged();
        return true;
      }

      return false;
    } catch (error) {
      console.error('Failed to reorder bookmarks:', error);
      return false;
    }
  }

  /**
   * Deletes multiple bookmarks in a single request.
   */
  public async deleteMultiple(ids: number[]): Promise<boolean> {
    try {
      const response: AjaxResponse = await new AjaxRequest(
        TYPO3.settings.ajaxUrls.bookmark_delete_multiple
      ).post({
        bookmarkIds: ids,
      });

      const data = await response.resolve();

      if (data.success) {
        for (const id of ids) {
          this.bookmarks.delete(id);
        }
        this.notifyChanged();
        return true;
      }

      return false;
    } catch (error) {
      console.error('Failed to delete multiple bookmarks:', error);
      return false;
    }
  }

  /**
   * Moves multiple bookmarks to a target group in a single request.
   */
  public async move(ids: number[], groupId: BookmarkGroupId): Promise<boolean> {
    try {
      const response: AjaxResponse = await new AjaxRequest(
        TYPO3.settings.ajaxUrls.bookmark_move
      ).post({
        bookmarkIds: ids,
        groupId,
      });

      const data = await response.resolve();

      if (data.success) {
        for (const id of ids) {
          const bookmark = this.bookmarks.get(id);
          if (bookmark) {
            this.bookmarks.set(id, { ...bookmark, groupId });
          }
        }
        this.notifyChanged();
        return true;
      }

      return false;
    } catch (error) {
      console.error('Failed to move bookmarks:', error);
      return false;
    }
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
   * Creates a user-defined bookmark group.
   */
  public async createGroup(label: string): Promise<BookmarkGroupCreateResponse> {
    try {
      const response: AjaxResponse = await new AjaxRequest(TYPO3.settings.ajaxUrls.bookmark_group_create).post({
        label,
      });

      const data: BookmarkGroupCreateResponse = await response.resolve();

      if (data.success && data.group) {
        this.groups.push(data.group);
        this.notifyChanged();
      }

      return data;
    } catch (error) {
      console.error('Failed to create group:', error);
      return {
        success: false,
        error: labels.get('error.groupCreateFailed.message')
      };
    }
  }

  /**
   * Renames a user-created group. System groups cannot be modified.
   */
  public async updateGroup(uuid: string, label: string): Promise<BookmarkGroupOperationResponse> {
    try {
      const response: AjaxResponse = await new AjaxRequest(TYPO3.settings.ajaxUrls.bookmark_group_update).post({
        uuid,
        label,
      });
      const data: BookmarkGroupOperationResponse = await response.resolve();

      if (data.success && data.groups) {
        this.groups = data.groups;
        this.notifyChanged();
      }

      return data;
    } catch (error) {
      console.error('Failed to update group:', error);
      return {
        success: false,
        error: labels.get('error.groupUpdateFailed.message')
      };
    }
  }

  /**
   * Deletes a user-created group. Contained bookmarks move to the default group.
   */
  public async deleteGroup(uuid: string): Promise<BookmarkGroupOperationResponse> {
    try {
      const response: AjaxResponse = await new AjaxRequest(TYPO3.settings.ajaxUrls.bookmark_group_delete).post({
        uuid,
      });

      const data: BookmarkGroupOperationResponse = await response.resolve();

      if (data.success && data.groups) {
        this.groups = data.groups;
        await this.refresh();
        this.notifyChanged();
      }

      return data;
    } catch (error) {
      console.error('Failed to delete group:', error);
      return {
        success: false,
        error: labels.get('error.groupDeleteFailed.message')
      };
    }
  }

  /**
   * Reorders user-created groups.
   */
  public async reorderGroups(uuids: string[]): Promise<BookmarkGroupOperationResponse> {
    try {
      const response: AjaxResponse = await new AjaxRequest(TYPO3.settings.ajaxUrls.bookmark_group_reorder).post({
        uuids,
      });

      const data: BookmarkGroupOperationResponse = await response.resolve();

      if (data.success && data.groups) {
        this.groups = data.groups;
        this.notifyChanged();
      }

      return data;
    } catch (error) {
      console.error('Failed to reorder groups:', error);
      return {
        success: false,
        error: labels.get('error.groupReorderFailed.message')
      };
    }
  }

  /**
   * Navigates to a bookmark using the module router, with fallback to direct navigation.
   */
  public navigate(bookmark: Bookmark): void {
    const router = document.querySelector('typo3-backend-module-router');
    if (router === null) {
      throw new Error('Router not available.');
    }

    router.setAttribute('endpoint', bookmark.href);
    router.setAttribute('module', bookmark.module);
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
   * Fetches bookmarks from server if not already loaded.
   * Returns immediately if store was already initialized.
   * Deduplicates concurrent requests.
   */
  private async fetchAll(): Promise<Bookmark[]> {
    if (this.isLoaded) {
      return [...this.bookmarks.values()];
    }

    if (!this.loadPromise) {
      this.loadPromise = this.doFetch();
    }
    await this.loadPromise;

    return [...this.bookmarks.values()];
  }

  /**
   * Sorts groups by priority (lower first) then by sorting value.
   */
  private sortGroups(groups: BookmarkGroup[]): BookmarkGroup[] {
    return groups.sort((a, b) => {
      const priorityDiff = a.priority - b.priority;
      if (priorityDiff !== 0) {
        return priorityDiff;
      }
      return a.sorting - b.sorting;
    });
  }

  /**
   * Synchronizes local cache with data from another tab's broadcast.
   */
  private handleBroadcast(event: BroadcastEvent<BookmarkStoreData>): void {
    this.load(event.detail.payload);
    this.notifyChanged(false);
  }

  /**
   * Replaces store contents with provided data.
   */
  private load(data: BookmarkStoreData): void {
    this.bookmarks.clear();
    for (const bookmark of data.bookmarks) {
      this.bookmarks.set(bookmark.id, bookmark);
    }
    this.groups = this.sortGroups(data.groups);
    this.isLoaded = true;
  }

  /**
   * Dispatches change event to all frames and optionally broadcasts to other tabs.
   * @param broadcast - Whether to broadcast changes to other tabs (default: true)
   */
  private notifyChanged(broadcast: boolean = true): void {
    const event = new CustomEvent(BookmarkStoreChangedEvent);
    document.dispatchEvent(event);
    for (let i = 0; i < window.frames.length; i++) {
      try {
        window.frames[i].document.dispatchEvent(event);
      } catch {
        // Cross-origin frame, skip
      }
    }

    if (broadcast) {
      BroadcastService.post(new BroadcastMessage('bookmark', 'broadcast', {
        bookmarks: [...this.bookmarks.values()],
        groups: [...this.groups],
      }));
    }
  }

  /**
   * Fetches bookmark data from server and rebuilds local cache.
   */
  private async doFetch(): Promise<void> {
    try {
      const url = TYPO3.settings.ajaxUrls.bookmark_list;
      if (!url) {
        console.warn('BookmarkStore: bookmark_list URL not available yet');
        return;
      }

      const response: AjaxResponse = await new AjaxRequest(url).get({ cache: 'no-cache' });
      const data: BookmarkListResponse = await response.resolve();

      if (data.success) {
        this.load(data);
        this.notifyChanged(false);
      }
    } catch (error) {
      console.error('Failed to fetch bookmarks:', error);
      throw error;
    }
  }

}

/**
 * Returns singleton instance from top frame, creating it if needed.
 */
function getOrCreateInstance(): BookmarkStore {
  try {
    if (top?.TYPO3?.BookmarkStore) {
      return top.TYPO3.BookmarkStore;
    }
    const instance = new BookmarkStore();
    if (top?.TYPO3) {
      top.TYPO3.BookmarkStore = instance;
    }
    return instance;
  } catch {
    // Cross-origin access denied, create local instance
    return new BookmarkStore();
  }
}

export default getOrCreateInstance();
