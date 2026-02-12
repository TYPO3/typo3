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
import { customElement, state } from 'lit/decorators.js';
import { repeat } from 'lit/directives/repeat.js';
import BookmarkStore, { BookmarkStoreChangedEvent } from '../bookmark-store';
import type { Bookmark, BookmarkGroup, BookmarkGroupId } from '../bookmark-store';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/bookmark/bookmark-manager';
import labels from '~labels/core.bookmarks';

/**
 * Toolbar dropdown menu listing bookmarks grouped by category.
 */
@customElement('typo3-backend-bookmark-menu')
export class BookmarkMenuElement extends LitElement {
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
    return html`
      <p class="dropdown-headline">${labels.get('title')}</p>
      ${this.renderContent()}
      <hr class="dropdown-divider" aria-hidden="true">
      <typo3-backend-bookmark-manager-button class="dropdown-item">
        <span class="dropdown-item-columns">
          <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
            <typo3-backend-icon identifier="actions-cog" size="small"></typo3-backend-icon>
          </span>
          <span class="dropdown-item-column dropdown-item-column-title">
            ${labels.get('manage')}
          </span>
        </span>
      </typo3-backend-bookmark-manager-button>
    `;
  }

  private readonly handleStoreUpdate = (): void => {
    this.syncFromStore();
  };

  private async syncFromStore(): Promise<void> {
    this.groupedBookmarks = await BookmarkStore.getGroupedBookmarks({ accessibleOnly: true });
    this.groups = await BookmarkStore.getGroups();
  }

  private renderContent(): TemplateResult | typeof nothing {
    if (this.groupedBookmarks.size === 0) {
      return html`
        <p class="dropdown-item-text">
          ${labels.get('description')}
        </p>
      `;
    }

    let isFirst = true;

    /* eslint-disable @stylistic/indent */
    return html`
      ${repeat(Array.from(this.groupedBookmarks.entries()), ([groupId]) => groupId, ([groupId, bookmarks]) => {
        const result = this.renderGroup(groupId, bookmarks, isFirst);
        isFirst = false;
        return result;
      })}
    `;
  }

  private renderGroup(groupId: BookmarkGroupId, bookmarks: Bookmark[], isFirst: boolean): TemplateResult {
    const group = this.groups.find(g => g.id === groupId);
    const groupLabel = group?.label;
    const showHeader = groupLabel || this.groups.length > 1;

    return html`
      ${!isFirst ? html`<hr class="dropdown-divider" aria-hidden="true">` : nothing}
      ${showHeader ? html`<p class="dropdown-header" id="bookmark-group-${groupId}">${groupLabel || labels.get('notGrouped')}</p>` : nothing}
      <ul class="dropdown-list" data-bookmarkgroup="${groupId}">
        ${repeat(bookmarks, (bookmark) => bookmark.id, (bookmark) => this.renderBookmarkItem(bookmark, groupId))}
      </ul>
    `;
  }

  private renderBookmarkItem(bookmark: Bookmark, groupId: BookmarkGroupId): TemplateResult {
    return html`
      <li class="t3js-topbar-bookmark" data-bookmarkid="${bookmark.id}" data-bookmarkgroup="${groupId}">
        <button
          type="button"
          class="dropdown-item"
          title="${bookmark.title}"
          @click=${() => this.handleNavigate(bookmark)}
        >
          <span class="dropdown-item-columns">
            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
              <typo3-backend-icon identifier=${bookmark.iconIdentifier} overlay=${bookmark.iconOverlayIdentifier} size="small"></typo3-backend-icon>
            </span>
            <span class="dropdown-item-column dropdown-item-column-title">
              ${bookmark.title}
            </span>
          </span>
        </button>
      </li>
    `;
  }

  private handleNavigate(bookmark: Bookmark): void {
    BookmarkStore.navigate(bookmark);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-bookmark-menu': BookmarkMenuElement;
  }
}
