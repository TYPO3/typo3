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
import BrowserSession from '@typo3/backend/storage/browser-session';
import {ResultContainer, componentName as resultContainerComponentName} from '@typo3/backend/live-search/element/result/result-container';
import {ResultItemInterface} from '@typo3/backend/live-search/element/result/item/item';

enum Identifiers {
  toolbarItem = '.t3js-topbar-button-search',
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
  constructor() {
    DocumentService.ready().then((): void => {
      this.registerEvents();
    });
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
    const url = new URL(TYPO3.settings.ajaxUrls.livesearch_form, window.location.origin);
    url.searchParams.set('query', BrowserSession.get('livesearch-term') ?? '');

    const persistedSearchOptions = Object.entries(BrowserSession.getByPrefix('livesearch-option-'))
      .filter((item: [string, string]) => item[1] === '1')
      .map((item: [string, string]): SearchOption => {
        const trimmedKey = item[0].replace('livesearch-option-', '');
        const [key, value] = trimmedKey.split('-', 2);
        return {key, value}
      });

    const searchOptions = this.composeSearchOptions(persistedSearchOptions);
    for (const [optionKey, optionValues] of Object.entries(searchOptions)) {
      for (let optionValue of optionValues) {
        url.searchParams.append(`${optionKey}[]`, optionValue);
      }
    }

    const modal = Modal.advanced({
      type: Modal.types.ajax,
      content: url.toString(),
      title: lll('labels.search'),
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium
    });

    modal.addEventListener('typo3-modal-shown', () => {
      const liveSearchContainer = modal.querySelector('typo3-backend-live-search')
      const searchField = liveSearchContainer.querySelector('input[type="search"]') as HTMLInputElement;
      const searchForm = searchField.closest('form');

      new RegularEvent('submit', (e: SubmitEvent): void => {
        e.preventDefault();

        const formData = new FormData(searchForm);
        this.search(formData).then((): void => {
          const query = formData.get('query').toString();
          BrowserSession.set('livesearch-term', query);
        });
        const optionCounterElement = searchForm.querySelector('[data-active-options-counter]') as HTMLElement;
        let count = parseInt(optionCounterElement.dataset.activeOptionsCounter, 10);
        optionCounterElement.querySelector('output').textContent = count.toString(10);
        optionCounterElement.classList.toggle('hidden', count === 0);
      }).bindTo(searchForm);

      searchField.clearable({
        onClear: (): void => {
          searchForm.requestSubmit();
        },
      });
      searchField.focus();
      searchField.select();

      const searchResultContainer: ResultContainer = document.querySelector('typo3-backend-live-search-result-container') as ResultContainer;
      new RegularEvent('live-search:item-chosen', (): void => {
        Modal.dismiss();
      }).bindTo(searchResultContainer);

      new RegularEvent('typo3:live-search:option-invoked', (e: CustomEvent): void => {
        const optionCounterElement = searchForm.querySelector('[data-active-options-counter]') as HTMLElement;
        let count = parseInt(optionCounterElement.dataset.activeOptionsCounter, 10);
        count = e.detail.active ? count + 1 : count - 1;

        // Update data attribute only, the visible text content is updated in the submit handler
        optionCounterElement.dataset.activeOptionsCounter = count.toString(10);
      }).bindTo(liveSearchContainer);

      new RegularEvent('hide.bs.dropdown', (): void => {
        searchForm.requestSubmit();
      }).bindTo(modal.querySelector(Identifiers.searchOptionDropdownToggle));

      new DebounceEvent('input', (): void => {
        searchForm.requestSubmit();
      }).bindTo(searchField);

      new RegularEvent('keydown', this.handleKeyDown).bindTo(searchField);

      searchForm.requestSubmit();
    });
  }

  private composeSearchOptions(searchOptions: SearchOption[]): { [key: string]: string[] } {
    const composedSearchOptions: { [key: string]: string[] } = {};
    searchOptions.forEach((searchOption: SearchOption): void => {
      if (composedSearchOptions[searchOption.key] === undefined) {
        composedSearchOptions[searchOption.key] = [];
      }
      composedSearchOptions[searchOption.key].push(searchOption.value);
    });

    return composedSearchOptions;
  }

  private search = async (formData: FormData): Promise<void> => {
    const query = formData.get('query').toString();
    let resultSet: ResultItemInterface[]|null = null;

    if (query !== '') {
      const searchResultContainer = document.querySelector(resultContainerComponentName) as ResultContainer;
      searchResultContainer.loading = true;

      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.livesearch).post(formData);

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

    searchResultContainer.results = searchResults;
    searchResultContainer.loading = false;
  }
}

export default top.TYPO3.LiveSearch ?? new LiveSearch();
