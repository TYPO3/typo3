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
import { classMap } from 'lit/directives/class-map.js';
import { repeat } from 'lit/directives/repeat.js';
import { lll } from '@typo3/core/lit-helper';
import BookmarkStore, { BookmarkStoreChangedEvent, BookmarkGroupType } from './bookmark-store';
import type { Bookmark, BookmarkGroup, BookmarkGroupId } from './bookmark-store';
import Modal from '../modal';
import Notification from '../notification';
import { SeverityEnum } from '../enum/severity';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';

interface GroupSection {
  label: string;
  groups: BookmarkGroup[];
}

interface DragPayload {
  type: 'bookmark' | 'group';
  id: number | string;
}

interface ViewStateInterface {
  readonly view: string;
}

class BookmarkListViewState implements ViewStateInterface {
  readonly view = 'list' as const;
}

class BookmarkEditViewState implements ViewStateInterface {
  readonly view = 'editBookmark' as const;
  constructor(public readonly bookmark: Bookmark) {}
}

class GroupCreateViewState implements ViewStateInterface {
  readonly view = 'createGroup' as const;
  readonly draft = { label: '' };
}

class GroupEditViewState implements ViewStateInterface {
  readonly view = 'editGroup' as const;
  constructor(public readonly group: BookmarkGroup) {}
}

class GroupListViewState implements ViewStateInterface {
  readonly view = 'manageGroups' as const;
}


type ViewContent = {
  toolbar: TemplateResult;
  content: TemplateResult;
};

/**
 * Bookmark management UI rendered inside a modal. Access via openBookmarkManager().
 */
@customElement('typo3-backend-bookmark-manager-content')
export class BookmarkManagerContentElement extends LitElement {
  @property({ type: Number }) editId: number | null = null;

  @state() private bookmarks: Bookmark[] = [];
  @state() private groups: BookmarkGroup[] = [];
  @state() private selectedIds: Set<number> = new Set();
  @state() private draggedItem: Bookmark | BookmarkGroup | null = null;
  @state() private dropTarget: { item: Bookmark | BookmarkGroup; position: 'before' | 'after' } | null = null;
  @state() private groupedBookmarks: Map<BookmarkGroupId, Bookmark[]> = new Map();
  @state() private viewState: ViewStateInterface = new BookmarkListViewState();

  public override connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener(BookmarkStoreChangedEvent, this.handleStoreUpdate);
    this.initFromStore();
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener(BookmarkStoreChangedEvent, this.handleStoreUpdate);
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    let viewContent: ViewContent;
    if (this.viewState instanceof BookmarkEditViewState) {
      viewContent = this.renderBookmarkEditView(this.viewState);
    } else if (this.viewState instanceof GroupListViewState) {
      viewContent = this.renderGroupListView();
    } else if (this.viewState instanceof GroupCreateViewState) {
      viewContent = this.renderGroupCreateView(this.viewState);
    } else if (this.viewState instanceof GroupEditViewState) {
      viewContent = this.renderGroupEditView(this.viewState);
    } else {
      viewContent = this.renderBookmarkListView();
    }

    return html`
      <div class="bookmark-manager">
        <div class="bookmark-manager-toolbar">${viewContent.toolbar}</div>
        <div class="bookmark-manager-content">${viewContent.content}</div>
      </div>
    `;
  }

  private async initFromStore(): Promise<void> {
    await this.syncFromStore();

    if (this.editId !== null) {
      const bookmark = this.bookmarks.find(bookmark => bookmark.id === this.editId);
      if (bookmark) {
        this.navigateToBookmarkEditView(bookmark);
      }
    }
  }

  // Navigation
  private navigateToBookmarkListView(): void {
    this.viewState = new BookmarkListViewState();
  }

  private navigateToBookmarkEditView(bookmark: Bookmark): void {
    this.viewState = new BookmarkEditViewState(bookmark);
  }

  private navigateToGroupListView(): void {
    this.viewState = new GroupListViewState();
  }

  private navigateToGroupCreateView(): void {
    this.viewState = new GroupCreateViewState();
  }

  private navigateToGroupEditView(group: BookmarkGroup): void {
    this.viewState = new GroupEditViewState(group);
  }

  // View Rendering
  private renderBookmarkListView(): ViewContent {
    const groupedBookmarks = this.groupedBookmarks;
    const hasSelection = this.selectedIds.size > 0;
    const hasBookmarks = this.bookmarks.length > 0;
    const allSelected = this.selectedIds.size === this.bookmarks.length && this.bookmarks.length > 0;
    const groupSections = this.helperGetGroupSections(this.getSelectableGroups());

    const toolbar = html`
      <div class="bookmark-manager-toolbar-start">
        <div class="form-slim">
          <div class="form-group">
            <div class="form-check form-check-type-toggle mb-0">
              <input
                type="checkbox"
                class="form-check-input"
                id="selectAll"
                .checked=${allSelected}
                @change=${(e: Event) => { this.selectedIds = (e.target as HTMLInputElement).checked ? new Set(this.bookmarks.map(bookmark => bookmark.id)) : new Set(); }}
              />
              <label class="form-check-label" for="selectAll">
                ${allSelected ? lll('core.bookmarks:manager.deselectAll') : lll('core.bookmarks:manager.selectAll')}
              </label>
            </div>
          </div>
          ${hasSelection ? html`
            <div class="form-group dropdown">
              <button
                class="btn btn-sm btn-default dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <typo3-backend-icon identifier="actions-bookmarks" size="small"></typo3-backend-icon>
                ${lll('core.bookmarks:manager.moveToGroup')}
              </button>
              <ul class="dropdown-menu">
                ${groupSections.map((section, index) => html`
                  ${index > 0 ? html`<li><hr class="dropdown-divider"></li>` : nothing}
                  <li><div class="dropdown-header">${section.label}</div></li>
                  ${section.groups.map(group => html`
                    <li>
                      <button class="dropdown-item" type="button" @click=${() => this.handleBookmarkBulkMove(group.id)}>
                        ${group.label}
                      </button>
                    </li>
                  `)}
                `)}
              </ul>
            </div>
            <div class="form-group">
              <button
                class="btn btn-sm btn-default"
                type="button"
                @click=${this.handleBookmarkBulkDelete}
              >
                <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
                ${lll('core.bookmarks:manager.deleteSelected')}
              </button>
            </div>
          ` : nothing}
        </div>
      </div>
      <div class="bookmark-manager-toolbar-end">
        <button
          class="btn btn-sm btn-default"
          type="button"
          @click=${this.navigateToGroupListView}
        >
          <typo3-backend-icon identifier="actions-cog" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:manager.manageGroups')}
        </button>
      </div>
    `;

    /* eslint-disable @stylistic/indent */
    const content = html`
      ${!hasBookmarks ? html`
        <div class="alert alert-info">
          <typo3-backend-icon identifier="actions-info-circle" size="small"></typo3-backend-icon>
          <span class="ms-2">${lll('core.bookmarks:empty')}</span>
        </div>
      ` : nothing}
      ${repeat(Array.from(groupedBookmarks.entries()), ([groupId]) => groupId, ([groupId, bookmarks]) => {
        const group = this.groups.find(group => group.id === groupId);
        const groupLabel = group?.label || lll('core.bookmarks:notGrouped');
        const collapseId = `bookmark-group-${typeof groupId === 'number' ? groupId : groupId.replace(/-/g, '')}`;
        return html`
          <div
            class="panel panel-default"
            data-group-id=${groupId}
            @dragover=${this.handleDragOver}
            @drop=${(e: DragEvent) => this.handleDrop(e, groupId)}
          >
            <div class="panel-heading" role="tab">
              <div class="panel-heading-row">
                <button
                  class="panel-button"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#${collapseId}"
                  aria-expanded="true"
                  aria-controls=${collapseId}
                >
                  <div class="panel-title">${groupLabel}</div>
                  <span class="caret"></span>
                </button>
              </div>
            </div>
            <div class="panel-collapse collapse show" id=${collapseId} role="tabpanel">
              <div class="table-fit">
                <table class="table table-striped table-hover mb-0">
                  <tbody>
                    ${repeat(bookmarks, (bookmark) => bookmark.id, (bookmark) => this.renderBookmarkRow(bookmark))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        `;
      })}
    `;
    return { toolbar, content };
  }

  private renderBookmarkRow(bookmark: Bookmark): TemplateResult {
    const isSelected = this.selectedIds.has(bookmark.id);
    const isDragged = this.draggedItem?.id === bookmark.id;
    const isDropTarget = this.dropTarget?.item.id === bookmark.id;
    const dropPosition = this.dropTarget?.position;
    const rowClasses = classMap({
      'table-active': isSelected,
      'opacity-50': isDragged,
      'row-drop-before': isDropTarget && dropPosition === 'before',
      'row-drop-after': isDropTarget && dropPosition === 'after',
    });
    const toggleSelection = () => {
      const set = new Set(this.selectedIds);
      if (set.has(bookmark.id)) {
        set.delete(bookmark.id);
      } else {
        set.add(bookmark.id);
      }
      this.selectedIds = set;
    };

    const groupBookmarks = this.groupedBookmarks.get(bookmark.groupId) ?? [];
    const bookmarkIndex = groupBookmarks.findIndex(item => item.id === bookmark.id);
    const isFirst = bookmarkIndex === 0;
    const isLast = bookmarkIndex === groupBookmarks.length - 1;

    return html`
      <tr
        class=${rowClasses}
        data-bookmark-id=${bookmark.id}
        draggable=${bookmark.editable ? 'true' : 'false'}
        @dragstart=${(e: DragEvent) => this.handleDragStart(e, 'bookmark', bookmark)}
        @dragover=${(e: DragEvent) => this.handleItemDragOver(e, bookmark)}
        @dragleave=${this.handleItemDragLeave}
        @drop=${(e: DragEvent) => this.handleDrop(e, bookmark)}
        @dragend=${this.handleDragEnd}
      >
        <td class="col-checkbox">
          ${bookmark.editable ? html`
            <span class="form-check form-check-type-toggle">
              <input
                type="checkbox"
                class="form-check-input"
                .checked=${isSelected}
                @change=${toggleSelection}
                @click=${(e: Event) => e.stopPropagation()}
              />
            </span>
          ` : html`
            <typo3-backend-icon identifier="actions-lock" size="small" title=${lll('core.bookmarks:manager.locked')}></typo3-backend-icon>
          `}
        </td>
        <td class="col-icon">
          <typo3-backend-icon identifier=${bookmark.iconIdentifier} overlay=${bookmark.iconOverlayIdentifier} size="small"></typo3-backend-icon>
        </td>
        <td class="col-title">
          ${bookmark.accessible ? html`
            <button
              type="button"
              class="btn btn-link"
              title=${bookmark.title}
              @click=${() => this.handleBookmarkNavigate(bookmark)}
            >
              ${bookmark.title}
            </button>
          ` : html`
            <span class="text-muted" title=${bookmark.title}>${bookmark.title}</span>
            <typo3-backend-icon class="text-warning" identifier="actions-exclamation-triangle" size="small" title=${lll('core.bookmarks:manager.notAccessible')}></typo3-backend-icon>
          `}
        </td>
        <td class="col-control nowrap">
          ${bookmark.editable ? html`
            <div class="btn-group">
              <button
                type="button"
                class="btn btn-default btn-sm"
                title=${lll('core.bookmarks:manager.editBookmark')}
                @click=${() => this.navigateToBookmarkEditView(bookmark)}
              >
                <typo3-backend-icon identifier="actions-cog" size="small"></typo3-backend-icon>
                <span class="visually-hidden">${lll('core.bookmarks:manager.editBookmark')}</span>
              </button>
              ${isFirst ? html`
                <span class="btn btn-default btn-sm disabled">
                  <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
                </span>
              ` : html`
                <button
                  type="button"
                  class="btn btn-default btn-sm"
                  data-action="move-up"
                  title=${lll('core.bookmarks:manager.moveUp')}
                  @click=${() => this.handleBookmarkMoveUp(bookmark)}
                >
                  <typo3-backend-icon identifier="actions-chevron-up" size="small"></typo3-backend-icon>
                  <span class="visually-hidden">${lll('core.bookmarks:manager.moveUp')}</span>
                </button>
              `}
              ${isLast ? html`
                <span class="btn btn-default btn-sm disabled">
                  <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
                </span>
              ` : html`
                <button
                  type="button"
                  class="btn btn-default btn-sm"
                  data-action="move-down"
                  title=${lll('core.bookmarks:manager.moveDown')}
                  @click=${() => this.handleBookmarkMoveDown(bookmark)}
                >
                  <typo3-backend-icon identifier="actions-chevron-down" size="small"></typo3-backend-icon>
                  <span class="visually-hidden">${lll('core.bookmarks:manager.moveDown')}</span>
                </button>
              `}
              <span
                class="btn btn-default btn-sm"
                style="cursor: grab;"
                title=${lll('core.bookmarks:manager.dragToReorder')}
              >
                <typo3-backend-icon identifier="actions-drag" size="small"></typo3-backend-icon>
                <span class="visually-hidden">${lll('core.bookmarks:manager.dragToReorder')}</span>
              </span>
            </div>
          ` : nothing}
        </td>
      </tr>
    `;
  }

  private renderBookmarkEditView(state: BookmarkEditViewState): ViewContent {
    const { bookmark } = state;
    const toolbar = html`
      <div class="bookmark-manager-toolbar-start">
        <button
          type="button"
          class="btn btn-sm btn-default"
          @click=${this.navigateToBookmarkListView}
        >
          <typo3-backend-icon identifier="actions-arrow-left" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:manager.back')}
        </button>
      </div>
      <div class="bookmark-manager-toolbar-end">
        <button
          type="button"
          class="btn btn-sm btn-danger"
          @click=${() => this.handleBookmarkDelete(bookmark.id)}
        >
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:action.delete')}
        </button>
        <button
          type="submit"
          form="bookmark-edit-form"
          class="btn btn-sm btn-primary"
        >
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:action.save')}
        </button>
      </div>
    `;
    const parametersRow = this.helperParseParameters(bookmark.arguments);
    const groupSections = this.helperGetGroupSections(this.getSelectableGroups());
    const content = html`
      <div class="bookmark-edit-view">
        <form id="bookmark-edit-form" @submit=${(e: Event) => this.handleBookmarkUpdate(e, bookmark)}>
          <div class="form-group">
            <label class="form-label" for="bookmark-title">${lll('core.bookmarks:fieldTitle')}</label>
            <input
              type="text"
              id="bookmark-title"
              name="title"
              class="form-control"
              .value=${bookmark.title}
              @input=${(e: Event) => { bookmark.title = (e.target as HTMLInputElement).value; this.requestUpdate(); }}
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label" for="bookmark-group">${lll('core.bookmarks:fieldGroup')}</label>
            <select
              id="bookmark-group"
              name="group"
              class="form-select"
              .value=${String(bookmark.groupId)}
              @change=${(e: Event) => { const value = (e.target as HTMLSelectElement).value; const group = this.groups.find(group => String(group.id) === value); if (group) { bookmark.groupId = group.id; this.requestUpdate(); } }}
            >
              ${groupSections.map(section => html`
                <optgroup label=${section.label}>
                  ${section.groups.map(group => html`
                    <option value=${group.id} ?selected=${group.id === bookmark.groupId}>
                      ${group.label}
                    </option>
                  `)}
                </optgroup>
              `)}
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">${lll('core.bookmarks:details')}</label>
            <div class="table-fit">
              <table class="table table-striped table-sm mb-0">
                <tbody>
                  ${bookmark.href ? html`
                  <tr>
                    <th class="nowrap">${lll('core.bookmarks:details.url')}</th>
                    <td class="text-break">${this.helperStripToken(bookmark.href)}</td>
                  </tr>
                  ` : nothing}
                  <tr>
                    <th class="nowrap">${lll('core.bookmarks:details.route')}</th>
                    <td>${bookmark.route}</td>
                  </tr>
                  ${parametersRow !== null ? html`
                  <tr>
                    <th class="nowrap align-top">${lll('core.bookmarks:details.parameters')}</th>
                    <td>${parametersRow}</td>
                  </tr>
                  ` : nothing}
                </tbody>
              </table>
            </div>
          </div>
        </form>
      </div>
    `;
    return { toolbar, content };
  }

  private renderGroupListView(): ViewContent {
    const editableGroups = this.groups.filter(group => group.editable);

    const toolbar = html`
      <div class="bookmark-manager-toolbar-start">
        <button
          type="button"
          class="btn btn-sm btn-default"
          @click=${this.navigateToBookmarkListView}
        >
          <typo3-backend-icon identifier="actions-arrow-left" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:manager.back')}
        </button>
      </div>
      <div class="bookmark-manager-toolbar-end">
        <button
          class="btn btn-sm btn-primary"
          type="button"
          @click=${this.navigateToGroupCreateView}
        >
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:manager.newGroup')}
        </button>
      </div>
    `;
    const content = html`
      <div class="bookmark-manage-groups-view">
        ${editableGroups.length > 0 ? html`
          <div class="table-fit">
            <table class="table table-striped table-hover mb-0">
              <tbody>
                ${editableGroups.map(group => this.renderGroupRow(group))}
              </tbody>
            </table>
          </div>
        ` : html`
          <div class="alert alert-info">
            <typo3-backend-icon identifier="actions-info-circle" size="small"></typo3-backend-icon>
            <span class="ms-2">${lll('core.bookmarks:manager.noGroups')}</span>
          </div>
        `}
      </div>
    `;
    return { toolbar, content };
  }

  private renderGroupRow(group: BookmarkGroup): TemplateResult {
    const bookmarkCount = this.bookmarks.filter(bookmark => bookmark.groupId === group.id).length;
    const isDragged = this.draggedItem?.id === group.id;
    const isDropTarget = this.dropTarget?.item.id === group.id;
    const dropPosition = this.dropTarget?.position;
    const rowClasses = classMap({
      'opacity-50': isDragged,
      'row-drop-before': isDropTarget && dropPosition === 'before',
      'row-drop-after': isDropTarget && dropPosition === 'after',
    });

    const editableGroups = this.groups.filter(item => item.editable);
    const groupIndex = editableGroups.findIndex(item => item.id === group.id);
    const isFirst = groupIndex === 0;
    const isLast = groupIndex === editableGroups.length - 1;

    return html`
      <tr
        class=${rowClasses}
        data-group-id=${group.id}
        draggable="true"
        @dragstart=${(e: DragEvent) => this.handleDragStart(e, 'group', group)}
        @dragover=${(e: DragEvent) => this.handleItemDragOver(e, group)}
        @dragleave=${this.handleItemDragLeave}
        @drop=${(e: DragEvent) => this.handleDrop(e, group)}
        @dragend=${this.handleDragEnd}
      >
        <td class="col-title">
          <button
            type="button"
            class="btn btn-link"
            @click=${() => this.navigateToGroupEditView(group)}
          >
            <typo3-backend-icon identifier="apps-pagetree-folder-default" size="small"></typo3-backend-icon>
            ${group.label}
            ${bookmarkCount > 0 ? html`
              <span class="badge badge-default">${bookmarkCount}</span>
            ` : nothing}
          </button>
        </td>
        <td class="col-control nowrap">
          <div class="btn-group">
            <button
              type="button"
              class="btn btn-default btn-sm"
              title=${lll('core.bookmarks:manager.editGroup')}
              @click=${() => this.navigateToGroupEditView(group)}
            >
              <typo3-backend-icon identifier="actions-cog" size="small"></typo3-backend-icon>
              <span class="visually-hidden">${lll('core.bookmarks:manager.editGroup')}</span>
            </button>
            ${isFirst ? html`
              <span class="btn btn-default btn-sm disabled">
                <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
              </span>
            ` : html`
              <button
                type="button"
                class="btn btn-default btn-sm"
                data-action="move-up"
                title=${lll('core.bookmarks:manager.moveUp')}
                @click=${() => this.handleGroupMoveUp(group)}
              >
                <typo3-backend-icon identifier="actions-chevron-up" size="small"></typo3-backend-icon>
                <span class="visually-hidden">${lll('core.bookmarks:manager.moveUp')}</span>
              </button>
            `}
            ${isLast ? html`
              <span class="btn btn-default btn-sm disabled">
                <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
              </span>
            ` : html`
              <button
                type="button"
                class="btn btn-default btn-sm"
                data-action="move-down"
                title=${lll('core.bookmarks:manager.moveDown')}
                @click=${() => this.handleGroupMoveDown(group)}
              >
                <typo3-backend-icon identifier="actions-chevron-down" size="small"></typo3-backend-icon>
                <span class="visually-hidden">${lll('core.bookmarks:manager.moveDown')}</span>
              </button>
            `}
            <span
              class="btn btn-default btn-sm"
              style="cursor: grab;"
              title=${lll('core.bookmarks:manager.dragToReorder')}
            >
              <typo3-backend-icon identifier="actions-drag" size="small"></typo3-backend-icon>
              <span class="visually-hidden">${lll('core.bookmarks:manager.dragToReorder')}</span>
            </span>
          </div>
        </td>
      </tr>
    `;
  }

  private renderGroupCreateView(state: GroupCreateViewState): ViewContent {
    const { draft } = state;
    const toolbar = html`
      <div class="bookmark-manager-toolbar-start">
        <button
          type="button"
          class="btn btn-sm btn-default"
          @click=${this.navigateToGroupListView}
        >
          <typo3-backend-icon identifier="actions-arrow-left" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:manager.back')}
        </button>
      </div>
      <div class="bookmark-manager-toolbar-end">
        <button
          type="submit"
          form="bookmark-group-create-form"
          class="btn btn-sm btn-primary"
        >
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:manager.createGroup')}
        </button>
      </div>
    `;
    const content = html`
      <div class="bookmark-group-create-view">
        <form id="bookmark-group-create-form" @submit=${(e: Event) => this.handleGroupCreate(e, draft)}>
          <div class="mb-3">
            <label class="form-label" for="group-label">${lll('core.bookmarks:manager.label')}</label>
            <input
              type="text"
              id="group-label"
              name="label"
              class="form-control"
              .value=${draft.label}
              @input=${(e: Event) => { draft.label = (e.target as HTMLInputElement).value; this.requestUpdate(); }}
              required
              autofocus
            />
          </div>
        </form>
      </div>
    `;
    return { toolbar, content };
  }

  private renderGroupEditView(state: GroupEditViewState): ViewContent {
    const { group } = state;
    const toolbar = html`
      <div class="bookmark-manager-toolbar-start">
        <button
          type="button"
          class="btn btn-sm btn-default"
          @click=${this.navigateToGroupListView}
        >
          <typo3-backend-icon identifier="actions-arrow-left" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:manager.back')}
        </button>
      </div>
      <div class="bookmark-manager-toolbar-end">
        <button
          type="button"
          class="btn btn-sm btn-danger"
          @click=${() => this.handleGroupDelete(group)}
        >
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:action.delete')}
        </button>
        <button
          type="submit"
          form="bookmark-group-edit-form"
          class="btn btn-sm btn-primary"
        >
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
          ${lll('core.bookmarks:action.save')}
        </button>
      </div>
    `;
    const content = html`
      <div class="bookmark-group-edit-view">
        <form id="bookmark-group-edit-form" @submit=${(e: Event) => this.handleGroupUpdate(e, group)}>
          <div class="mb-3">
            <label class="form-label" for="group-label">${lll('core.bookmarks:manager.label')}</label>
            <input
              type="text"
              id="group-label"
              name="label"
              class="form-control"
              .value=${group.label}
              @input=${(e: Event) => { group.label = (e.target as HTMLInputElement).value; this.requestUpdate(); }}
              required
            />
          </div>
        </form>
      </div>
    `;
    return { toolbar, content };
  }

  // Data Management
  private async syncFromStore(): Promise<void> {
    this.bookmarks = await BookmarkStore.getBookmarks();
    this.groups = await BookmarkStore.getGroups();
    this.groupedBookmarks = await BookmarkStore.getGroupedBookmarks();
  }

  private getSelectableGroups(): BookmarkGroup[] {
    return this.groups.filter(group => group.selectable);
  }

  // Handlers
  private readonly handleStoreUpdate = (): void => {
    this.syncFromStore();
  };

  private handleBookmarkNavigate(bookmark: Bookmark): void {
    this.closest('typo3-backend-modal')?.hideModal();
    BookmarkStore.navigate(bookmark);
  }

  private async handleBookmarkUpdate(e: Event, bookmark: Bookmark): Promise<void> {
    e.preventDefault();
    const result = await BookmarkStore.update(bookmark.id, bookmark.title, bookmark.groupId);
    if (result.success) {
      Notification.success(
        lll('core.bookmarks:success.updated.title'),
        lll('core.bookmarks:success.updated.message')
      );
      this.navigateToBookmarkListView();
    } else {
      Notification.error(
        lll('core.bookmarks:error.updateFailed.title'),
        result.error || lll('core.bookmarks:error.unknown.message')
      );
    }
  }

  private handleBookmarkDelete(id: number): void {
    const confirmModal = Modal.confirm(
      lll('core.bookmarks:confirmDelete.title'),
      lll('core.bookmarks:confirmDelete.message'),
      SeverityEnum.notice,
      [
        {
          text: lll('core.bookmarks:action.cancel'),
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: () => confirmModal.hideModal(),
        },
        {
          text: lll('core.bookmarks:action.delete'),
          btnClass: 'btn-primary',
          name: 'delete',
          trigger: async () => {
            const success = await BookmarkStore.deleteMultiple([id]);
            if (success) {
              Notification.success(
                lll('core.bookmarks:success.deleted.title'),
                lll('core.bookmarks:success.deleted.message')
              );
              this.navigateToBookmarkListView();
            } else {
              Notification.error(
                lll('core.bookmarks:error.deleteFailed.title'),
                lll('core.bookmarks:error.deleteFailed.message')
              );
            }
            confirmModal.hideModal();
          },
        },
      ]
    );
  }

  private readonly handleBookmarkBulkDelete = (): void => {
    const ids = Array.from(this.selectedIds);
    const confirmModal = Modal.confirm(
      lll('core.bookmarks:confirmDeleteMultiple.title'),
      lll('core.bookmarks:confirmDeleteMultiple.message', ids.length),
      SeverityEnum.notice,
      [
        {
          text: lll('core.bookmarks:action.cancel'),
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: () => confirmModal.hideModal(),
        },
        {
          text: lll('core.bookmarks:action.delete'),
          btnClass: 'btn-primary',
          name: 'delete',
          trigger: async () => {
            const success = await BookmarkStore.deleteMultiple(ids);
            if (success) {
              Notification.success(
                lll('core.bookmarks:success.deletedMultiple.title'),
                lll('core.bookmarks:success.deletedMultiple.message', ids.length)
              );
              this.selectedIds = new Set();
            } else {
              Notification.error(
                lll('core.bookmarks:error.deleteFailed.title'),
                lll('core.bookmarks:error.deleteFailed.message')
              );
            }
            confirmModal.hideModal();
          },
        },
      ]
    );
  };

  private async handleBookmarkBulkMove(groupId: BookmarkGroupId): Promise<void> {
    const ids = Array.from(this.selectedIds);
    const success = await BookmarkStore.move(ids, groupId);
    if (success) {
      Notification.success(
        lll('core.bookmarks:success.moved.title'),
        lll('core.bookmarks:success.moved.message', ids.length)
      );
      this.selectedIds = new Set();
    } else {
      Notification.error(
        lll('core.bookmarks:error.moveFailed.title'),
        lll('core.bookmarks:error.moveFailed.message')
      );
    }
  }

  private async handleBookmarkMoveUp(bookmark: Bookmark): Promise<void> {
    await this.reorderBookmarkRelative(bookmark, -1);
    await this.restoreFocus('bookmark', bookmark.id, 'up');
  }

  private async handleBookmarkMoveDown(bookmark: Bookmark): Promise<void> {
    await this.reorderBookmarkRelative(bookmark, 1);
    await this.restoreFocus('bookmark', bookmark.id, 'down');
  }

  private async handleGroupMoveUp(group: BookmarkGroup): Promise<void> {
    await this.reorderGroupRelative(group, -1);
    await this.restoreFocus('group', group.id, 'up');
  }

  private async handleGroupMoveDown(group: BookmarkGroup): Promise<void> {
    await this.reorderGroupRelative(group, 1);
    await this.restoreFocus('group', group.id, 'down');
  }

  private async handleGroupCreate(e: Event, draft: { label: string }): Promise<void> {
    e.preventDefault();
    if (!draft.label.trim()) {
      return;
    }

    const result = await BookmarkStore.createGroup(draft.label.trim());
    if (result.success) {
      Notification.success(
        lll('core.bookmarks:success.groupCreated.title'),
        lll('core.bookmarks:success.groupCreated.message')
      );
      this.navigateToGroupListView();
    } else {
      Notification.error(
        lll('core.bookmarks:error.groupCreateFailed.title'),
        result.error || lll('core.bookmarks:error.groupCreateFailed.message')
      );
    }
  }

  private async handleGroupUpdate(e: Event, group: BookmarkGroup): Promise<void> {
    e.preventDefault();
    if (!group.label.trim() || !group.editable) {
      return;
    }

    const result = await BookmarkStore.updateGroup(
      group.id as string,
      group.label.trim()
    );

    if (result.success) {
      Notification.success(
        lll('core.bookmarks:success.groupUpdated.title'),
        lll('core.bookmarks:success.groupUpdated.message')
      );
      this.navigateToGroupListView();
    } else {
      Notification.error(
        lll('core.bookmarks:error.groupUpdateFailed.title'),
        result.error || lll('core.bookmarks:error.groupUpdateFailed.message')
      );
    }
  }

  private handleGroupDelete(group: BookmarkGroup): void {
    if (!group.editable) {
      return;
    }

    const confirmModal = Modal.confirm(
      lll('core.bookmarks:confirmDeleteGroup.title'),
      lll('core.bookmarks:confirmDeleteGroup.message', group.label),
      SeverityEnum.notice,
      [
        {
          text: lll('core.bookmarks:action.cancel'),
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: () => confirmModal.hideModal(),
        },
        {
          text: lll('core.bookmarks:action.delete'),
          btnClass: 'btn-primary',
          name: 'delete',
          trigger: async () => {
            const result = await BookmarkStore.deleteGroup(group.id as string);
            if (result.success) {
              Notification.success(
                lll('core.bookmarks:success.groupDeleted.title'),
                lll('core.bookmarks:success.groupDeleted.message')
              );
              this.navigateToGroupListView();
            } else {
              Notification.error(
                lll('core.bookmarks:error.groupDeleteFailed.title'),
                result.error || lll('core.bookmarks:error.groupDeleteFailed.message')
              );
            }
            confirmModal.hideModal();
          },
        },
      ]
    );
  }

  // Reordering
  private async restoreFocus(type: 'bookmark' | 'group', id: number | string, direction: 'up' | 'down'): Promise<void> {
    await this.updateComplete;
    const rowSelector = type === 'bookmark' ? `tr[data-bookmark-id="${id}"]` : `tr[data-group-id="${id}"]`;
    const row = this.querySelector(rowSelector);
    if (!row) {
      return;
    }
    const preferredAction = direction === 'up' ? 'move-up' : 'move-down';
    const fallbackAction = direction === 'up' ? 'move-down' : 'move-up';
    const moveButton = row.querySelector<HTMLButtonElement>(`button[data-action="${preferredAction}"]`)
      ?? row.querySelector<HTMLButtonElement>(`button[data-action="${fallbackAction}"]`);
    moveButton?.focus();
  }

  private async reorderBookmarkRelative(bookmark: Bookmark, offset: number): Promise<void> {
    const groupBookmarks = [...(this.groupedBookmarks.get(bookmark.groupId) ?? [])];
    const currentIndex = groupBookmarks.findIndex(item => item.id === bookmark.id);
    const targetIndex = currentIndex + offset;
    if (currentIndex < 0 || targetIndex < 0 || targetIndex >= groupBookmarks.length) {
      return;
    }
    [groupBookmarks[currentIndex], groupBookmarks[targetIndex]] =
      [groupBookmarks[targetIndex], groupBookmarks[currentIndex]];
    await BookmarkStore.reorder(groupBookmarks.map(item => item.id));
    await this.syncFromStore();
  }

  private async reorderBookmarkToPosition(bookmark: Bookmark, targetGroupId: BookmarkGroupId, targetIndex: number): Promise<void> {
    if (bookmark.groupId !== targetGroupId) {
      await BookmarkStore.update(bookmark.id, bookmark.title, targetGroupId);
    }
    const groupBookmarks = [...(this.groupedBookmarks.get(targetGroupId) ?? [])].filter(item => item.id !== bookmark.id);
    groupBookmarks.splice(targetIndex, 0, bookmark);
    await BookmarkStore.reorder(groupBookmarks.map(item => item.id));
    await this.syncFromStore();
  }

  private async reorderGroupRelative(group: BookmarkGroup, offset: number): Promise<void> {
    const editableGroups = this.groups.filter(item => item.editable);
    const currentIndex = editableGroups.findIndex(item => item.id === group.id);
    const targetIndex = currentIndex + offset;
    if (currentIndex < 0 || targetIndex < 0 || targetIndex >= editableGroups.length) {
      return;
    }
    [editableGroups[currentIndex], editableGroups[targetIndex]] =
      [editableGroups[targetIndex], editableGroups[currentIndex]];
    await BookmarkStore.reorderGroups(editableGroups.map(item => item.id as string));
    await this.syncFromStore();
  }

  private async reorderGroupToPosition(group: BookmarkGroup, targetIndex: number): Promise<void> {
    const editableGroups = this.groups.filter(item => item.editable && item.id !== group.id);
    editableGroups.splice(targetIndex, 0, group);
    await BookmarkStore.reorderGroups(editableGroups.map(item => item.id as string));
    await this.syncFromStore();
  }

  // Drag and Drop
  private handleDragStart(e: DragEvent, type: 'bookmark' | 'group', item: Bookmark | BookmarkGroup): void {
    this.draggedItem = item;
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      const payload: DragPayload = { type, id: item.id };
      e.dataTransfer.setData('application/json', JSON.stringify(payload));
    }
  }

  private readonly handleDragOver = (e: DragEvent): void => {
    e.preventDefault();
    if (e.dataTransfer) {
      e.dataTransfer.dropEffect = 'move';
    }
  };

  private handleItemDragOver(e: DragEvent, target: Bookmark | BookmarkGroup): void {
    e.preventDefault();
    e.stopPropagation();

    if (this.draggedItem === null || this.draggedItem.id === target.id) {
      return;
    }

    if (e.dataTransfer) {
      e.dataTransfer.dropEffect = 'move';
    }

    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const midpoint = rect.top + rect.height / 2;
    const position: 'before' | 'after' = e.clientY < midpoint ? 'before' : 'after';

    this.dropTarget = { item: target, position };
  }

  private readonly handleItemDragLeave = (e: DragEvent): void => {
    const relatedTarget = e.relatedTarget as Node | null;
    if (relatedTarget && (e.currentTarget as HTMLElement).contains(relatedTarget)) {
      return;
    }
    this.dropTarget = null;
  };

  private readonly handleDragEnd = (): void => {
    this.draggedItem = null;
    this.dropTarget = null;
  };

  private async handleDrop(e: DragEvent, target: Bookmark | BookmarkGroup | BookmarkGroupId): Promise<void> {
    e.preventDefault();
    e.stopPropagation();

    const dragged = this.draggedItem;
    const dropPosition = this.dropTarget?.position;
    this.dropTarget = null;

    if (dragged === null) {
      return;
    }

    const payload: DragPayload = JSON.parse(e.dataTransfer?.getData('application/json') ?? '{}');

    if (payload.type === 'bookmark') {
      await this.handleDropBookmark(dragged as Bookmark, target, dropPosition);
    } else if (payload.type === 'group') {
      await this.handleDropGroup(dragged as BookmarkGroup, target as BookmarkGroup, dropPosition);
    }

    this.draggedItem = null;
  }

  private async handleDropBookmark(draggedBookmark: Bookmark, target: Bookmark | BookmarkGroup | BookmarkGroupId, dropPosition?: 'before' | 'after'): Promise<void> {
    if (typeof target === 'object' && 'route' in target) {
      const targetBookmark = target as Bookmark;
      if (draggedBookmark.id === targetBookmark.id) {
        return;
      }
      const groupBookmarks = [...(this.groupedBookmarks.get(targetBookmark.groupId) ?? [])].filter(item => item.id !== draggedBookmark.id);
      const targetIndex = groupBookmarks.findIndex(item => item.id === targetBookmark.id);
      const insertIndex = dropPosition === 'before' ? targetIndex : targetIndex + 1;
      await this.reorderBookmarkToPosition(draggedBookmark, targetBookmark.groupId, insertIndex);
    } else {
      const targetGroupId = target as BookmarkGroupId;
      if (draggedBookmark.groupId !== targetGroupId) {
        const groupBookmarks = [...(this.groupedBookmarks.get(targetGroupId) ?? [])];
        await this.reorderBookmarkToPosition(draggedBookmark, targetGroupId, groupBookmarks.length);
      }
    }
  }

  private async handleDropGroup(draggedGroup: BookmarkGroup, targetGroup: BookmarkGroup, dropPosition?: 'before' | 'after'): Promise<void> {
    if (draggedGroup.id === targetGroup.id) {
      return;
    }
    const editableGroups = this.groups.filter(item => item.editable && item.id !== draggedGroup.id);
    const targetIndex = editableGroups.findIndex(item => item.id === targetGroup.id);
    const insertIndex = dropPosition === 'before' ? targetIndex : targetIndex + 1;
    await this.reorderGroupToPosition(draggedGroup, insertIndex);
  }

  // Helpers
  private helperGetGroupSections(groups: BookmarkGroup[]): GroupSection[] {
    const groupsByType = Map.groupBy(groups, (group) => group.type);
    const groupTypeLabels: Record<BookmarkGroupType, string> = {
      [BookmarkGroupType.USER]: lll('core.bookmarks:groupType.user'),
      [BookmarkGroupType.SYSTEM]: lll('core.bookmarks:groupType.system'),
      [BookmarkGroupType.GLOBAL]: lll('core.bookmarks:groupType.global'),
    };

    return Array.from(groupsByType.entries()).map(([type, typeGroups]) => ({
      label: groupTypeLabels[type] ?? type,
      groups: typeGroups,
    }));
  }

  private helperParseParameters(argumentsJson: string): TemplateResult | null {
    if (!argumentsJson) {
      return null;
    }
    try {
      const entries = Object.entries(JSON.parse(argumentsJson));
      if (entries.length === 0) {
        return null;
      }
      const format = (value: unknown): string => {
        const isObject = typeof value === 'object' && value !== null;
        return isObject ? JSON.stringify(value) : String(value);
      };
      return html`
        <ul class="list-unstyled mb-0">
          ${entries.map(([key, value]) => html`<li><strong>${key}:</strong> ${format(value)}</li>`)}
        </ul>
      `;
    } catch {
      return html`${argumentsJson}`;
    }
  }

  private helperStripToken(url: string): string {
    try {
      const urlObj = new URL(url, window.location.origin);
      urlObj.searchParams.delete('token');
      return decodeURIComponent(urlObj.pathname + urlObj.search);
    } catch {
      return url;
    }
  }
}

/**
 * Button that opens the bookmark manager modal.
 */
@customElement('typo3-backend-bookmark-manager-button')
export class BookmarkManagerButtonElement extends PseudoButtonLitElement {
  @property({ type: Number, attribute: 'edit-id' }) editId?: number;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override buttonActivated(): void {
    // Create element in top frame to avoid adoptedStyleSheets cross-document issues
    const targetDocument = top?.document ?? document;
    const managerElement = targetDocument.createElement('typo3-backend-bookmark-manager-content') as BookmarkManagerContentElement;
    if (this.editId !== undefined) {
      managerElement.editId = this.editId;
    }

    Modal.advanced({
      type: Modal.types.default,
      title: lll('core.bookmarks:manage'),
      size: Modal.sizes.medium,
      severity: SeverityEnum.notice,
      content: managerElement,
      buttons: [],
      staticBackdrop: true,
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-bookmark-manager-content': BookmarkManagerContentElement;
    'typo3-backend-bookmark-manager-button': BookmarkManagerButtonElement;
  }
}
