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

import $ from 'jquery';
import Viewport from '../viewport';
import Icons from '../icons';
import 'jquery/autocomplete';
import '../input/clearable';
import {html, render, TemplateResult} from 'lit';
import {unsafeHTML} from 'lit/directives/unsafe-html';
import {renderHTML} from '@typo3/core/lit-helper';

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem',
  toolbarItem = '.t3js-toolbar-item-search',
  dropdownToggle = '.t3js-toolbar-search-dropdowntoggle',
  searchFieldSelector = '.t3js-topbar-navigation-search-field',
  formSelector = '.t3js-topbar-navigation-search',
  dropdownClass = 'toolbar-item-search-field-dropdown',
}

interface ResultItem {
  editLink: string;
  iconHTML: string;
  id: string;
  pageId: number;
  title: string;
  typeLabel: string;
}

interface Suggestion {
  data: ResultItem;
  value: string;
}

/**
 * Module: @typo3/backend/toolbar/live-search
 * Global search to deal with everything in the backend that is search-related
 * @exports @typo3/backend/toolbar/live-search
 */
class LiveSearch {
  private url: string = TYPO3.settings.ajaxUrls.livesearch;

  constructor() {
    Viewport.Topbar.Toolbar.registerEvent((): void => {
      this.registerAutocomplete();
      this.registerEvents();

      // Unset height, width and z-index
      $(Identifiers.toolbarItem).removeAttr('style');
      let searchField: HTMLInputElement;
      if ((searchField = document.querySelector(Identifiers.searchFieldSelector)) !== null) {
        searchField.clearable();
      }
    });
  }

  private registerAutocomplete(): void {
    const $searchField = $(Identifiers.searchFieldSelector);
    $(Identifiers.searchFieldSelector).autocomplete({
      // ajax options
      serviceUrl: this.url,
      paramName: 'q',
      dataType: 'json',
      minChars: 2,
      width: '100%',
      groupBy: 'typeLabel',
      tabDisabled: true,
      noCache: true,
      containerClass: Identifiers.toolbarItem.substr(1, Identifiers.toolbarItem.length) + ' dropdown-menu ' + Identifiers.dropdownClass,
      forceFixPosition: false,
      preserveInput: true,
      showNoSuggestionNotice: true,
      triggerSelectOnValidInput: false,
      preventBadQueries: false,
      noSuggestionNotice: '<h3 class="dropdown-headline">' + TYPO3.lang.liveSearch_listEmptyText + '</h3>'
      + '<p>' + TYPO3.lang.liveSearch_helpTitle + '</p>'
      + '<hr>'
      + '<p>' + TYPO3.lang.liveSearch_helpDescription + '<br>' + TYPO3.lang.liveSearch_helpDescriptionPages + '</p>',
      // put the AJAX results in the right format
      transformResult: (response: Array<ResultItem>): { [key: string]: Array<Suggestion> } => {
        let allSuggestions = $.map(response, (dataItem: ResultItem): Suggestion => {
          return {value: dataItem.title, data: dataItem};
        });

        // If there are search results, append a "Show all"-link to suggestions
        // to allow the user to reach "Show all" using up/down key
        if(allSuggestions.length > 0) {
          let showAll: Suggestion = {
            value: 'search_all',
            data: {
              typeLabel: '',
              title: TYPO3.lang.liveSearch_showAllResults,
              editLink: '#',
              iconHTML: '',
              id: '',
              pageId: 0
            }
          }
          allSuggestions.push(showAll);
        }

        return {
          suggestions: allSuggestions,
        };
      },
      formatGroup: (suggestion: Suggestion, category: string, i: number): string => {
        // Do not return headline div if category empty
        if(category.length < 1) {
          return '';
        }

        return renderHTML(html`
          ${i > 0 ? html`<hr>` : ''}
          <h3 class="dropdown-headline">${category}</h3>
        `);
      },
      // Rendering of each item
      formatResult: (suggestion: Suggestion): string => {
        return renderHTML(html`
          <div class="dropdown-table">
            <div class="dropdown-table-row">
              ${this.linkItem(suggestion)}
            </div>
          </div>
        `);
      },
      onSearchStart: (): void => {
        const $toolbarItem = $(Identifiers.toolbarItem);
        if (!$toolbarItem.hasClass('loading')) {
          $toolbarItem.addClass('loading');
          Icons.getIcon(
            'spinner-circle-light',
            Icons.sizes.small,
            '',
            Icons.states.default,
            Icons.markupIdentifiers.inline,
          ).then((markup: string): void => {
            $toolbarItem.find('.icon-apps-toolbar-menu-search').replaceWith(markup);
          });
        }
      },
      onSearchComplete: (): void => {
        const $toolbarItem = $(Identifiers.toolbarItem);
        if ($toolbarItem.hasClass('loading')) {
          $toolbarItem.removeClass('loading');
          Icons.getIcon(
            'apps-toolbar-menu-search',
            Icons.sizes.small,
            '',
            Icons.states.default,
            Icons.markupIdentifiers.inline,
          ).then((markup: string): void => {
            $toolbarItem.find('.icon-spinner-circle-light').replaceWith(markup);
          });
        }
      },
      onSelect: (item: Suggestion): void => {
        $searchField.focus();
        $(Identifiers.searchFieldSelector).autocomplete('hide');

        if(item.value === 'search_all') {
          TYPO3.ModuleMenu.App.showModule('web_list', 'id=0&search_levels=-1&search_field=' + encodeURIComponent($searchField.val()));
        } else {
          TYPO3.Backend.ContentContainer.setUrl(item.data.editLink);
        }

        // Hide mobile menu scaffold coz it does not make sense to keep the layer
        if (document.body.classList.contains('scaffold-search-expanded')) {
          document.body.classList.remove('scaffold-search-expanded')
        }

        // Make sure the dropdown is hidden after selection when using the keyboard
        document.getElementById('typo3-contentIframe').onload = function() {
          $(Identifiers.searchFieldSelector).autocomplete('hide');
        };
      }
    });
  }

  private registerEvents(): void {
    // Prevent submitting the search form
    $(Identifiers.formSelector).on('submit', (evt: JQueryEventObject): void => {
      evt.preventDefault();
    });
  }

  private linkItem(suggestion: Suggestion): TemplateResult {
    if(suggestion.value === 'search_all') {
      return html`
        <a class="dropdown-list-link btn btn-primary pull-right t3js-live-search-show-all" data-pageid="0">${suggestion.data.title}</a>
      `
    }

    return suggestion.data.editLink
      ? html`
        <a class="dropdown-list-link"
           data-pageid="${suggestion.data.pageId}" href="#">
          <div class="dropdown-table-column dropdown-table-icon">
            ${unsafeHTML(suggestion.data.iconHTML)}
          </div>
          <div class="dropdown-table-column">
            ${suggestion.data.title}
          </div>
        </a>`
      : html`<span class="dropdown-list-title">${suggestion.data.title}</span>`;
  }
}

export default new LiveSearch();
