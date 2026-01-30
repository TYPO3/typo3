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

import { html, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { lll } from '@typo3/core/lit-helper';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import BookmarkStore, { BookmarkStoreChangedEvent } from '../bookmark-store';
import Notification from '../../notification';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/element/spinner-element';

/**
 * Toggle button that creates or removes a bookmark. Syncs state with BookmarkStore.
 */
@customElement('typo3-backend-bookmark-button')
export class BookmarkButtonElement extends PseudoButtonLitElement {
  @property({ type: String }) route: string = '';
  @property({ type: String }) arguments: string = '';
  @property({ type: String, attribute: 'display-name' }) displayName: string = '';
  @property({ type: Boolean, attribute: 'hide-label-text' }) hideLabelText: boolean = false;

  @state() private isBookmarked: boolean = false;
  @state() private isProcessing: boolean = false;

  private get bookmarkKey(): string {
    return BookmarkStore.getBookmarkKey(this.route, this.arguments);
  }

  public override connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener(BookmarkStoreChangedEvent, this.handleStoreUpdate);
    this.setup();
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener(BookmarkStoreChangedEvent, this.handleStoreUpdate);
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    const label = this.isBookmarked
      ? lll('core.bookmarks:action.remove')
      : lll('core.bookmarks:action.create');

    const labelHtml = this.hideLabelText
      ? html`<span class="visually-hidden">${label}</span>`
      : html` ${label}`;

    if (this.isProcessing) {
      return html`<typo3-backend-spinner size="small"></typo3-backend-spinner>${labelHtml}`;
    }

    const iconIdentifier = this.isBookmarked ? 'actions-bookmark-remove' : 'actions-bookmark-add';
    return html`<typo3-backend-icon identifier="${iconIdentifier}" size="small"></typo3-backend-icon>${labelHtml}`;
  }

  protected override async buttonActivated(): Promise<void> {
    if (this.isProcessing) {
      return;
    }

    this.isProcessing = true;
    this.updateState();

    try {
      if (this.isBookmarked) {
        const bookmark = await BookmarkStore.getBookmark(this.bookmarkKey);
        if (bookmark) {
          BookmarkStore.requestDelete(bookmark.id);
        }
      } else {
        const result = await BookmarkStore.create(this.route, this.arguments, this.displayName);
        if (result.success) {
          Notification.success(
            lll('core.bookmarks:success.created.title'),
            lll('core.bookmarks:success.created.message')
          );
        } else {
          Notification.error(
            lll('core.bookmarks:error.createFailed.title'),
            result.error || lll('core.bookmarks:error.createFailed.message')
          );
        }
      }
    } finally {
      this.isProcessing = false;
      await this.checkIfBookmarked();
      this.updateState();
    }
  }

  private async setup(): Promise<void> {
    await this.checkIfBookmarked();
    this.updateState();
  }

  private readonly handleStoreUpdate = async (): Promise<void> => {
    await this.checkIfBookmarked();
    this.updateState();
  };

  private async checkIfBookmarked(): Promise<void> {
    this.isBookmarked = await BookmarkStore.isBookmarked(this.bookmarkKey);
  }

  /**
   * Synchronizes aria attributes and title with current bookmark/processing state.
   */
  private updateState(): void {
    if (this.isProcessing) {
      this.setAttribute('aria-disabled', 'true');
      this.tabIndex = -1;
    } else {
      this.removeAttribute('aria-disabled');
      this.tabIndex = 0;
    }

    this.setAttribute('aria-pressed', this.isBookmarked ? 'true' : 'false');
    this.title = this.isBookmarked
      ? lll('core.bookmarks:action.remove')
      : lll('core.bookmarks:action.create');
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-bookmark-button': BookmarkButtonElement;
  }
}
