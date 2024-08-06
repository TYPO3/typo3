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

import { customElement, property } from 'lit/decorators';
import { html, css, LitElement, CSSResult, TemplateResult, nothing } from 'lit';
import Modal from '@typo3/backend/modal';
import '@typo3/backend/element/icon-element';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { lll } from '@typo3/core/lit-helper';
import Notification from '@typo3/backend/notification';
import Viewport from '@typo3/backend/viewport';
import RegularEvent from '@typo3/core/event/regular-event';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';

type RequestType = 'location'|'ajax'|undefined;

class Item {
  public visible: boolean = true;

  public constructor(
    public readonly identifier: string,
    public readonly label: string,
    public readonly description: string,
    public readonly icon: string,
    public readonly url: string,
    public readonly requestType: RequestType,
    public readonly defaultValues: Array<any>,
    public readonly saveAndClose: boolean
  ) { }

  public static fromData(data: DataItemInterface) {
    return new Item(
      data.identifier,
      data.label,
      data.description,
      data.icon,
      data.url,
      data.requestType ?? 'location',
      data.defaultValues ?? [],
      data.saveAndClose ?? false,
    );
  }

  public reset(): void
  {
    this.visible = true;
  }
}

class Category {
  public disabled: boolean = false;

  public constructor(
    public readonly identifier: string,
    public readonly label: string,
    public readonly items: Item[],
  ) { }

  public static fromData(data: DataCategoryInterface) {
    return new Category(
      data.identifier,
      data.label,
      data.items.map((item: DataItemInterface) => Item.fromData(item))
    );
  }

  public reset(): void
  {
    this.disabled = false;
    this.items.forEach((item: Item): void => { item.reset(); });
  }

  public activeItems(): Item[] {
    return this.items.filter((item: Item): boolean => item.visible) ?? [];
  }
}

class Categories {
  public constructor(
    public readonly items: Category[],
  ) { }

  public static fromData(data: DataCategoriesInterface) {
    return new Categories(
      Object.values(data).map((item: DataCategoryInterface) => Category.fromData(item))
    );
  }

  public reset(): void
  {
    this.items.forEach((item: Category): void => { item.reset(); });
  }

  public categoriesWithItems(): Category[] {
    return this.items.filter((item: Category): boolean => item.activeItems().length > 0) ?? [];
  }
}

interface DataItemInterface {
  identifier: string;
  label: string;
  description: string;
  icon: string;
  url: string,
  requestType: RequestType,
  defaultValues: Array<any> | undefined,
  saveAndClose: boolean | undefined
}

interface DataCategoryInterface {
  identifier: string;
  label: string;
  items: DataItemInterface[];
}

interface DataCategoriesInterface {
  [key: string]: DataCategoryInterface;
}

interface Message {
  message: string;
  severity: string;
}

/**
 * Module: @typo3/backend/new-record-wizard
 */
@customElement('typo3-backend-new-record-wizard')
export class NewRecordWizard extends LitElement {
  static styles: CSSResult[] = [
    css`
      :host {
        display: block;
        container-type: inline-size;
      }

      .element {
        display: flex;
        flex-direction: column;
        gap: var(--typo3-spacing);
        font-size: var(--typo3-component-font-size);
        line-height: var(--typo3-component-line-height);
      }

      .main {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: calc(var(--typo3-spacing) * 2);
      }

      @container (min-width: 500px) {
        .main {
            flex-direction: row;
        }
      }

      .main > * {
        flex-grow: 1;
      }

      .navigation {
        position: relative;
        flex-shrink: 0;
      }

      @container (min-width: 500px) {
        .navigation {
            flex-grow: 0;
            width: 200px;
        }
      }

      @container (min-width: 500px) {
        .navigation-toggle {
            display: none !important;
        }
      }

      .navigation-list {
        display: none;
        flex-direction: column;
        gap: 2px;
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .navigation-list.show {
        display: flex;
      }

      @container (max-width: 499px) {
        .navigation-list {
          z-index: 1;
          position: absolute;
          padding: var(--typo3-component-border-width);
          background: var(--typo3-component-bg);
          border: var(--typo3-component-border-width) solid var(--typo3-component-border-color);
          border-radius: var(--typo3-component-border-radius);
          box-shadow: var(--typo3-component-box-shadow);
        }
      }

      @container (min-width: 500px) {
        .navigation-list {
            display: flex;
        }
      }

      .navigation-item {
        cursor: pointer;
        align-items: center;
        display: flex;
        width: 100%;
        gap: calc(var(--typo3-spacing) / 2);
        text-align: start;
        color: inherit;
        background: transparent;
        border: var(--typo3-component-border-width) solid var(--typo3-component-border-color);
        border-radius: var(--typo3-component-border-radius);
        padding: var(--typo3-list-item-padding-y) var(--typo3-list-item-padding-x);
      }

      @container (max-width: 499px) {
        .navigation-item {
          border-radius: calc(var(--typo3-component-border-radius) - var(--typo3-component-border-width));
        }
      }

      .navigation-item:hover {
        color: var(--typo3-component-hover-color);
        background: var(--typo3-component-hover-bg);
        border-color: var(--typo3-component-hover-border-color);
      }

      .navigation-item:focus {
        outline: none;
        color: var(--typo3-component-focus-color);
        background: var(--typo3-component-focus-bg);
        border-color: var(--typo3-component-focus-border-color);
      }

      .navigation-item.active {
        color: var(--typo3-component-active-color);
        background: var(--typo3-component-active-bg);
        border-color: var(--typo3-component-active-border-color);
      }

      .navigation-item:disabled {
        cursor: not-allowed;
        color: var(--typo3-component-disabled-color);
        background: var(--typo3-component-disabled-bg);
        border-color: var(--typo3-component-disabled-border-color);
      }

      .navigation-item-label {
        flex-grow: 1;
      }

      .navigation-item-count {
        opacity: .75;
        flex-shrink: 0;
      }

      .content {
        container-type: inline-size;
      }

      .item-list {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: var(--typo3-spacing);
      }

      @container (min-width: 500px) {
        .item-list {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      .item {
        cursor: pointer;
        display: flex;
        gap: calc(var(--typo3-spacing) / 2);
        text-align: start;
        border: var(--typo3-component-border-width) solid transparent;
        border-radius: var(--typo3-component-border-radius);
        padding: var(--typo3-list-item-padding-y) var(--typo3-list-item-padding-x);
        background: transparent;
        color: inherit;
      }

      .item:hover {
        color: var(--typo3-component-hover-color);
        background: var(--typo3-component-hover-bg);
        border-color: var(--typo3-component-hover-border-color);
      }

      .item:focus {
        outline: none;
        color: var(--typo3-component-focus-color);
        background: var(--typo3-component-focus-bg);
        border-color: var(--typo3-component-focus-border-color);
      }

      .item-body-label {
        text-wrap: balance;
        font-weight: bold;
        margin-bottom: .25rem;
      }

      .item-body-description {
        opacity: .75;
        text-wrap: pretty;
      }
    `
  ];

  @property({
    type: Object, converter: {
      fromAttribute: (value) => {
        const data: DataCategoriesInterface = JSON.parse(value);
        return Categories.fromData(data);
      },
    }
  }) categories: Categories = new Categories([]);
  @property({ type: String }) searchPlaceholder: string = 'newRecordWizard.filter.placeholder';
  @property({ type: String }) searchNothingFoundLabel: string = 'newRecordWizard.filter.noResults';
  @property({ type: String, attribute: false }) selectedCategory: Category | null = null;
  @property({ type: String, attribute: false }) searchTerm: string = '';
  @property({ type: Array, attribute: false }) messages: Message[] = [];
  @property({ type: Boolean, attribute: false }) toggleMenu: boolean = false;

  public constructor() {
    super();
  }

  protected firstUpdated(): void {
    // Load shared css file
    const link = document.createElement('link');
    link.setAttribute('rel', 'stylesheet');
    link.setAttribute('href', TYPO3.settings.cssUrls.backend);
    this.shadowRoot.appendChild(link);

    const filterField: HTMLInputElement = this.renderRoot.querySelector('input[name="search"]');
    filterField.focus();
    this.selectAvailableCategory();
  }

  protected getLanguageLabel(label: string): string {
    const languageLabel = lll(label);
    if (languageLabel !== '') {
      return languageLabel;
    }

    return label;
  }

  protected selectAvailableCategory(): void {

    const needsCategoryChange: boolean = this.categories.categoriesWithItems()
      .filter((item: Category): boolean => item === this.selectedCategory).length === 0;
    if (needsCategoryChange) {
      this.selectedCategory = this.categories.categoriesWithItems()[0] ?? null;
    }

    this.messages = [];
    if (this.selectedCategory === null) {
      this.messages = [{
        message: this.getLanguageLabel(this.searchNothingFoundLabel),
        severity: 'info'
      }];
    }
  }

  protected filter(searchTerm: string): void {
    this.searchTerm = searchTerm;
    this.categories.reset();
    this.categories.items.forEach((category: Category) => {
      const categoryText = category.label.trim().replace(/\s+/g, ' ');
      const categoryMatch: boolean = !(this.searchTerm !== '' && !RegExp(this.searchTerm, 'i').test(categoryText));
      if (!categoryMatch) {
        category.items.forEach((item: Item) => {
          const text = item.label.trim().replace(/\s+/g, ' ') + item.description.trim().replace(/\s+/g, ' ');
          item.visible = !(this.searchTerm !== '' && !RegExp(this.searchTerm, 'i').test(text));
        });
      }
      category.disabled = category.items.filter((item: Item): boolean => item.visible).length === 0;
    });
    this.selectAvailableCategory();
  }

  protected render(): TemplateResult {
    return html`
      <div class="element">
        ${this.renderFilter()}
        ${this.renderMessages()}
        ${this.selectedCategory === null ? nothing : html`
        <div class="main">
          <div class="navigation">
            ${this.renderNavigationToggle()}
            ${this.renderNavigationList()}
          </div>
          <div class="content">
            ${this.renderCategories()}
          </div>
        </div>
      `}
      </div>
    `;
  }

  protected renderFilter(): TemplateResult {
    return html`
      <form class="filter" @submit="${(event: SubmitEvent) => event.preventDefault()}">
        <input
          name="search"
          type="search"
          autocomplete="off"
          class="form-control"
          .value="${this.searchTerm}"
          @input="${(event: InputEvent): void => { this.filter((<HTMLInputElement>event.target).value); }}"
          @keydown="${(event: KeyboardEvent): void => { if (event.key === KeyTypesEnum.ESCAPE) { event.stopImmediatePropagation(); this.filter(''); } }}"
          placeholder="${this.getLanguageLabel(this.searchPlaceholder)}"
        />
      </form>
    `;
  }

  protected renderMessages(): TemplateResult {
    return html`${this.messages.length > 0 ?
      html`<div class="messages">${this.messages.map((message) => html`<div class="alert alert-${message.severity}" role="alert">${message.message}</div>`)}</div>` :
      nothing
    }`;
  }

  protected renderNavigationToggle(): TemplateResult {
    return html`
        <button
          class="navigation-toggle btn btn-light"
          @click="${() => { this.toggleMenu = !this.toggleMenu; }}"
        >
          ${this.selectedCategory.label}
          <typo3-backend-icon identifier="actions-chevron-${(this.toggleMenu === true) ? 'up' : 'down'}" size="small"></typo3-backend-icon>
        </button>
      `;
  }

  protected renderNavigationList(): TemplateResult {
    return html`
      <div class="navigation-list${(this.toggleMenu === true) ? ' show' : ''}" role="tablist">
    ${this.categories.items.map((category: Category) => {
    return html`
        <button
          data-identifier="${category.identifier}"
          class="navigation-item${(this.selectedCategory === category) ? ' active' : ''}"
          ?disabled="${category.disabled}"
          @click="${() => { this.selectedCategory = category; this.toggleMenu = false; }}"
        >
          <span class="navigation-item-label">${category.label}</span>
          <span class="navigation-item-count">${category.activeItems().length}</span>
        </button>
      `;
  })}
      </div>`;
  }

  protected renderCategories(): TemplateResult {
    return html`
      <div class="elementwizard-categories">
  ${this.categories.items.map((category: Category) => {
    return this.renderCategory(category);
  })}
      </div>
    `;
  }

  protected renderCategory(category: Category): TemplateResult {
    return html`${this.selectedCategory === category ?
      html`
        <div class="item-list">
          ${category.items.map((item: Item) => this.renderCategoryButton(item))}
        </div>` :
      nothing
    }`;
  }

  protected renderCategoryButton(item: Item): TemplateResult {
    return html`${item.visible ?
      html`
      <button
        type="button"
        class="item"
        data-identifier="${item.identifier}"
        @click="${(event: PointerEvent): void => { event.preventDefault(); this.handleItemClick(item); }}"
      >
        <div class="item-icon">
          <typo3-backend-icon identifier="${item.icon || 'empty-empty'}" size="medium"></typo3-backend-icon>
        </div>
        <div class="item-body">
          <div class="item-body-label">${item.label}</div>
          <div class="item-body-description">${item.description}</div>
        </div>
      </button>
      ` :
      nothing
    }`;
  }

  protected handleItemClick(item: Item): void {
    if (item.url.trim() === '') {
      return;
    }

    if (item.requestType === 'location') {
      Viewport.ContentContainer.setUrl(item.url);
      Modal.dismiss();
      return;
    }

    if (item.requestType === 'ajax') {
      (new AjaxRequest(item.url)).post({
        defVals: item.defaultValues,
        saveAndClose: item.saveAndClose ? '1' : '0'
      }).then(async (response: AjaxResponse): Promise<void> => {
        const result = document.createRange().createContextualFragment(await response.resolve());

        // Handle buttons with data-target
        Modal.currentModal.addEventListener('modal-updated', () => {
          new RegularEvent('click', (e: PointerEvent, eventTarget: HTMLButtonElement): void => {
            e.preventDefault();
            const target: string = eventTarget.dataset.target;
            if (!target) {
              return;
            }
            Viewport.ContentContainer.setUrl(target);
            Modal.dismiss();
          }).delegateTo(Modal.currentModal, 'button[data-target]');
        });

        Modal.currentModal.setContent(result);
      }).catch((): void => {
        Notification.error('Could not load module data');
      });
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-new-record-wizard': NewRecordWizard;
  }
}
