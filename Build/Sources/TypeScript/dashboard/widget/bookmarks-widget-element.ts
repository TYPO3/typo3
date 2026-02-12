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

import { html, LitElement, type TemplateResult, nothing } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { repeat } from 'lit/directives/repeat.js';
import labels from '~labels/core.bookmarks';
import BookmarkStore, { BookmarkStoreChangedEvent } from '@typo3/backend/bookmark/bookmark-store';
import type { Bookmark, BookmarkGroup, BookmarkGroupId } from '@typo3/backend/bookmark/bookmark-store';
import '@typo3/backend/bookmark/bookmark-manager';

/**
 * Dashboard widget displaying bookmarks with optional group filtering and limit.
 */
@customElement('typo3-dashboard-bookmarks-widget')
export class BookmarksWidgetElement extends LitElement {
  @property({ type: Number }) limit: number = 0;
  @property({
    attribute: 'group',
    converter: {
      fromAttribute: (value: string | null): BookmarkGroupId | null => {
        if (value === null || value === '') {
          return null;
        }
        // Return as number if numeric, otherwise as string (UUID)
        const num = Number(value);
        return Number.isNaN(num) ? value : num;
      },
      toAttribute: (value: BookmarkGroupId | null) => {
        return value === null ? null : String(value);
      }
    }
  }) groupId: BookmarkGroupId | null = null;

  @state() private groupedBookmarks: Map<BookmarkGroupId, Bookmark[]> = new Map();
  @state() private groups: BookmarkGroup[] = [];

  public override connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener(BookmarkStoreChangedEvent, this.handleStoreUpdate);
    this.syncFromStore();
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener(BookmarkStoreChangedEvent, this.handleStoreUpdate);
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    if (this.groupedBookmarks.size === 0) {
      return html`
        <p class="dropdown-item-text">
          ${labels.get('description')}
        </p>
      `;
    }

    return html`
      <div class="widget-table-wrapper">
        ${repeat(Array.from(this.groupedBookmarks.entries()), ([groupId]) => groupId, ([groupId, bookmarks]) => this.renderGroup(groupId, bookmarks, this.groupedBookmarks.size > 1))}
      </div>
    `;
  }

  private readonly handleStoreUpdate = (): void => {
    this.syncFromStore();
  };

  private async syncFromStore(): Promise<void> {
    this.groupedBookmarks = await BookmarkStore.getGroupedBookmarks({
      groupId: this.groupId ?? undefined,
      limit: this.limit > 0 ? this.limit : undefined,
      accessibleOnly: true
    });
    this.groups = await BookmarkStore.getGroups();
  }

  private renderGroup(groupId: BookmarkGroupId, bookmarks: Bookmark[], showHeader: boolean): TemplateResult {
    const group = this.groups.find(g => g.id === groupId);
    const groupLabel = group?.label || labels.get('notGrouped');

    return html`
      <table class="widget-table table table-striped table-hover">
        ${showHeader && this.groupId === null ? html`
          <thead>
            <tr>
              <th>${groupLabel}</th>
            </tr>
          </thead>
        ` : nothing}
        <tbody>
          ${repeat(bookmarks, (bookmark) => bookmark.id, (bookmark) => this.renderBookmarkRow(bookmark))}
        </tbody>
      </table>
    `;
  }

  private renderBookmarkRow(bookmark: Bookmark): TemplateResult {
    return html`
      <tr>
        <td>
          <span class="bookmark-item-column bookmark-item-column-icon" aria-hidden="true">
            <typo3-backend-icon identifier=${bookmark.iconIdentifier} overlay=${bookmark.iconOverlayIdentifier} size="small"></typo3-backend-icon>
          </span>
          <a href="${bookmark.href}" title="${bookmark.title}" @click=${(e: Event) => this.handleNavigate(e, bookmark)}>
            <span class="bookmark-item-column bookmark-item-column-title">
              ${bookmark.title}
            </span>
          </a>
        </td>
      </tr>
    `;
  }

  private handleNavigate(e: Event, bookmark: Bookmark): void {
    e.preventDefault();
    BookmarkStore.navigate(bookmark);
  }

}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-dashboard-bookmarks-widget': BookmarksWidgetElement;
  }
}
