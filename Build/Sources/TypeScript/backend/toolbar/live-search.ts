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

import {lll} from '@typo3/core/lit-helper';
import Modal from '../modal';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/input/clearable';
import '../live-search/element/search-option-item';
import '../live-search/element/show-all';
import '../live-search/live-search-shortcut';
import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import DebounceEvent from '@typo3/core/event/debounce-event';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {SearchOptionItem} from '@typo3/backend/live-search/element/search-option-item';
import BrowserSession from '@typo3/backend/storage/browser-session';
import {ResultContainer, componentName as resultContainerComponentName} from '@typo3/backend/live-search/element/result/result-container';
import {ResultItemInterface} from '@typo3/backend/live-search/element/result/item/item';

enum Identifiers {
  toolbarItem = '.t3js-topbar-button-search',
  searchOptionDropdown = '.t3js-search-provider-dropdown',
  searchOptionDropdownToggle = '.t3js-search-provider-dropdown-toggle',
}

interface SearchOption {
  key: string;
  value: string;
}

/**
 * Module: @typo3/backend/toolbar/live-search
 * Global search to deal with everything in the backend that is search-related
 * @exports @typo3/backend/toolbar/live-search
 */
class LiveSearch {
  private renderers: { [key: string]: Function } = {};
  private invokeHandlers: { [key: string]: Function } = {};
  private searchTerm: string = '';
  private searchOptions: { [key: string]: string[] } = {};

  constructor() {
    DocumentService.ready().then((): void => {
      this.registerEvents();
    });
  }

  public addRenderer(type: string, callback: Function): void {
    this.renderers[type] = callback;
  }

  public addInvokeHandler(type: string, action: string, callback: Function): void {
    this.invokeHandlers[type + '_' + action] = callback;
  }

  private registerEvents(): void {
    new RegularEvent('click', (): void => {
      this.openSearchModal();
    }).delegateTo(document, Identifiers.toolbarItem);

    new RegularEvent('typo3:live-search:trigger-open', (): void => {
      if (Modal.currentModal) {
        return;
      }

      this.openSearchModal();
    }).bindTo(document);
  }

  private openSearchModal(): void {
    const modal = Modal.advanced({
      type: Modal.types.ajax,
      content: TYPO3.settings.ajaxUrls.livesearch_form + '&q=' + (BrowserSession.get('livesearch-term') ?? ''),
      title: lll('labels.search'),
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium
    });

    modal.addEventListener('typo3-modal-shown', () => {
      this.searchTerm = BrowserSession.get('livesearch-term') ?? '';

      const searchOptions = Object.entries(BrowserSession.getByPrefix('livesearch-option-'))
        .filter((item: [string, string]) => item[1] === '1')
        .map((item: [string, string]): SearchOption => {
          const trimmedKey = item[0].replace('livesearch-option-', '');
          const [key, value] = trimmedKey.split('-', 2);
          return {key, value}
        });
      this.composeSearchOptions(searchOptions);

      const searchField = modal.querySelector('input[type="search"]') as HTMLInputElement;

      searchField.clearable({
        onClear: (): void => {
          this.searchTerm = '';
          this.search();
        },
      });
      searchField.focus();
      searchField.select();

      const searchResultContainer: ResultContainer = document.querySelector('typo3-backend-live-search-result-container') as ResultContainer;
      new RegularEvent('live-search:item-chosen', (): void => {
        Modal.dismiss();
      }).bindTo(searchResultContainer);

      new RegularEvent('hide.bs.dropdown', (): void => {
        const activeSearchOptions = Array.from(modal.querySelectorAll(Identifiers.searchOptionDropdown + ' typo3-backend-live-search-option-item'))
          .filter((searchOptionItem: SearchOptionItem) => searchOptionItem.active)
          .map((searchOptionItem: SearchOptionItem): SearchOption => ({
            key: searchOptionItem.optionName,
            value: searchOptionItem.optionId
          }));
        this.composeSearchOptions(activeSearchOptions);

        this.search();
      }).bindTo(modal.querySelector(Identifiers.searchOptionDropdownToggle));

      new DebounceEvent('input', (e: InputEvent): void => {
        this.searchTerm = (e.target as HTMLInputElement).value;
        this.search();
      }).bindTo(searchField);

      new RegularEvent('keydown', this.handleKeyDown).bindTo(searchField);

      this.search();
    });
  }

  private composeSearchOptions(searchOptions: SearchOption[]): void {
    this.searchOptions = {};

    searchOptions.forEach((searchOption: SearchOption): void => {
      if (this.searchOptions[searchOption.key] === undefined) {
        this.searchOptions[searchOption.key] = [];
      }
      this.searchOptions[searchOption.key].push(searchOption.value);
    });
  }

  private search = async (): Promise<void> => {
    BrowserSession.set('livesearch-term', this.searchTerm);

    let resultSet: ResultItemInterface[]|null = null;
    if (this.searchTerm !== '') {
      const searchResultContainer = document.querySelector(resultContainerComponentName) as ResultContainer;
      searchResultContainer.loading = true;

      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.livesearch).post({
        q: this.searchTerm,
        options: this.searchOptions,
      });

      resultSet = await response.raw().json();
    }

    this.updateSearchResults(resultSet);
  }

  private handleKeyDown(e: KeyboardEvent): void {
    if (e.key !== 'ArrowDown') {
      return;
    }

    e.preventDefault();

    // Select first available result item
    const firstSearchResultItem = document.querySelector('typo3-backend-live-search').querySelector('typo3-backend-live-search-result-item') as HTMLElement|null;
    firstSearchResultItem?.focus();
  }

  private updateSearchResults(searchResults: ResultItemInterface[]|null): void {
    const searchAllButton = document.querySelector('typo3-backend-live-search-show-all') as HTMLButtonElement;
    searchAllButton.parentElement.hidden = searchResults === null || searchResults.length === 0;

    const searchResultContainer: ResultContainer = document.querySelector('typo3-backend-live-search-result-container') as ResultContainer;
    searchResultContainer.renderers = this.renderers;
    searchResultContainer.invokeHandlers = this.invokeHandlers;

    searchResultContainer.results = searchResults;
    searchResultContainer.loading = false;
  }
}

export default top.TYPO3.LiveSearch ?? new LiveSearch();
