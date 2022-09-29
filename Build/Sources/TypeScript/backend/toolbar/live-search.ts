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

import {html, TemplateResult} from 'lit';
import {lll} from '@typo3/core/lit-helper';
import Viewport from '../viewport';
import Modal from '../modal';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/input/clearable';
import '../live-search/element/result-container';
import '../live-search/element/show-all';
import '../live-search/live-search-shortcut';
import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import DebounceEvent from '@typo3/core/event/debounce-event';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import AjaxRequest from '@typo3/core/ajax/ajax-request';

enum Identifiers {
  toolbarItem = '.t3js-topbar-button-search',
}

/**
 * Module: @typo3/backend/toolbar/live-search
 * Global search to deal with everything in the backend that is search-related
 * @exports @typo3/backend/toolbar/live-search
 */
class LiveSearch {
  private lastTerm: string = '';
  private lastResultSet: string = '';
  private hints: string[] = [
    lll('liveSearch_helpDescriptionPages'),
    lll('liveSearch_helpDescriptionContent'),
    lll('liveSearch_help.shortcutOpen'),
  ];

  constructor() {
    DocumentService.ready().then((): void => {
      Viewport.Topbar.Toolbar.registerEvent((): void => {
        this.registerEvents();
      });
    });
  }

  private registerEvents(): void {
    new RegularEvent('click', (): void => {
      this.openSearchModal();
    }).delegateTo(document, Identifiers.toolbarItem);

    new RegularEvent('live-search:item-chosen', (e: CustomEvent): void => {
      Modal.dismiss();
      e.detail.callback();
    }).bindTo(document);

    new RegularEvent('typo3:live-search:trigger-open', (): void => {
      if (Modal.currentModal) {
        return;
      }

      this.openSearchModal();
    }).bindTo(document);
  }

  private openSearchModal(): void {
    const modal = Modal.advanced({
      content: this.composeSearchComponent(),
      title: lll('labels.search'),
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium
    });

    modal.addEventListener('typo3-modal-shown', () => {
      const searchField = modal.querySelector('input[type="search"]') as HTMLInputElement;
      searchField.clearable({
        onClear: (): void => {
          this.search('');
        },
      });
      searchField.focus();
      searchField.select();

      new DebounceEvent('input', (e: InputEvent): void => {
        const searchTerm = (e.target as HTMLInputElement).value;
        this.search(searchTerm);
      }).bindTo(searchField);
      new RegularEvent('keydown', this.handleKeyDown).bindTo(searchField);

      if (this.lastResultSet) {
        this.updateSearchResults(this.lastResultSet);
      }
    });
  }

  private composeSearchComponent(): TemplateResult {
    return html`<div id="backend-live-search">
      <div class="sticky-form-actions">
        <div class="row row-cols-auto justify-content-between">
          <div class="col flex-grow-1">
            <input type="search" name="searchField" class="form-control" placeholder="Search" value="${this.lastTerm}" autocomplete="off">
            <div class="form-text mt-2">
              <typo3-backend-icon identifier="actions-lightbulb-on" size="small"></typo3-backend-icon>${this.hints[Math.floor(Math.random() * this.hints.length)]}
            </div>
          </div>
          <div class="col" hidden>
            <typo3-backend-live-search-show-all></typo3-backend-live-search-show-all>
          </div>
        </div>
      </div>
      <typo3-backend-live-search-result-container class="livesearch-results"></typo3-backend-live-search-result-container>
    </div>`;
  }

  private search = async (searchTerm: string): Promise<void> => {
    this.lastTerm = searchTerm;
    let resultSet = '[]';

    if (searchTerm !== '') {
      const searchResultContainer = document.querySelector('typo3-backend-live-search-result-container') as HTMLElement;
      searchResultContainer.setAttribute('loading', 'loading');

      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.livesearch)
        .withQueryArguments({q: searchTerm})
        .get({cache: 'no-cache'});

      resultSet = await response.raw().text();
    }

    this.lastResultSet = resultSet;
    this.updateSearchResults(resultSet);
  }

  private handleKeyDown(e: KeyboardEvent): void {
    if (e.key !== 'ArrowDown') {
      return;
    }

    e.preventDefault();

    // Select first available result item
    const firstSearchResultItem = document.getElementById('backend-live-search').querySelector('typo3-backend-live-search-result-item') as HTMLElement|null;
    firstSearchResultItem?.focus();
  }

  private updateSearchResults(searchResults: string): void {
    const searchAllButton = document.querySelector('typo3-backend-live-search-show-all') as HTMLButtonElement;
    searchAllButton.parentElement.hidden = JSON.parse(searchResults).length === 0;

    const searchResultContainer = document.querySelector('typo3-backend-live-search-result-container') as HTMLElement;
    if (this.lastTerm !== '') {
      searchResultContainer.setAttribute('results', searchResults);
    } else {
      searchResultContainer.removeAttribute('results');
    }
    searchResultContainer.removeAttribute('loading');
  }
}

export default new LiveSearch();
