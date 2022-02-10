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
import {ModuleStateStorage} from '@typo3/backend/storage/module-state-storage';

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem',
  toolbarItem = '.t3js-toolbar-item-search',
  dropdownToggle = '.t3js-toolbar-search-dropdowntoggle',
  searchFieldSelector = '.t3js-topbar-navigation-search-field',
  formSelector = '.t3js-topbar-navigation-search',
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
        searchField.clearable({
          onClear: (): void => {
            if ($(Identifiers.dropdownToggle).hasClass('show')) {
              $(Identifiers.dropdownToggle).dropdown('toggle');
            }
          },
        });
      }
    });
  }

  private registerAutocomplete(): void {
    $(Identifiers.searchFieldSelector).autocomplete({
      // ajax options
      serviceUrl: this.url,
      paramName: 'q',
      dataType: 'json',
      minChars: 2,
      width: '100%',
      groupBy: 'typeLabel',
      noCache: true,
      containerClass: Identifiers.toolbarItem.substr(1, Identifiers.toolbarItem.length),
      appendTo: Identifiers.containerSelector + ' .dropdown-menu',
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
        return {
          suggestions: $.map(response, (dataItem: ResultItem): Suggestion => {
            return {value: dataItem.title, data: dataItem};
          }),
        };
      },
      formatGroup: (suggestion: Suggestion, category: string, i: number): string => {
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
              <div class="dropdown-table-column dropdown-table-icon">
                ${unsafeHTML(suggestion.data.iconHTML)}
              </div>
              <div class="dropdown-table-column dropdown-table-title">
                ${this.linkItem(suggestion)}
              </div>
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
        const $searchField = $(Identifiers.searchFieldSelector);
        if (!$(Identifiers.dropdownToggle).hasClass('show') && $searchField.val().length > 1) {
          $(Identifiers.dropdownToggle).dropdown('toggle');
          $searchField.focus();
        }
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
      beforeRender: (container: JQuery): void => {
        container.append('<hr><div>' +
          '<a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">' +
          TYPO3.lang.liveSearch_showAllResults +
          '</a>' +
          '</div>');
        if (!$(Identifiers.dropdownToggle).hasClass('show')) {
          $(Identifiers.dropdownToggle).dropdown('show');
          $(Identifiers.searchFieldSelector).focus();
        }
      },
      onHide: (): void => {
        if ($(Identifiers.dropdownToggle).hasClass('show')) {
          $(Identifiers.dropdownToggle).dropdown('hide');
        }
      },
    });
  }

  private registerEvents(): void {
    const $searchField = $(Identifiers.searchFieldSelector);

    $(Identifiers.containerSelector).on('click', '.t3js-live-search-show-all', (evt: JQueryEventObject): void => {
      evt.preventDefault();

      TYPO3.ModuleMenu.App.showModule('web_list', 'id=0&search_levels=-1&search_field=' + encodeURIComponent($searchField.val()));
      $searchField.val('').trigger('change');
    });
    if ($searchField.length) {
      const $autocompleteContainer = $('.' + Identifiers.toolbarItem.substr(1, Identifiers.toolbarItem.length));
      $autocompleteContainer.on('click.autocomplete', '.dropdown-list-link', (evt: JQueryEventObject): void => {
        evt.preventDefault();
        const $me = $(evt.currentTarget);
        // Load the list module of the page
        ModuleStateStorage.updateWithCurrentMount('web', $me.data('pageid'), true);
        const router = document.querySelector('typo3-backend-module-router');
        router.setAttribute('endpoint', $me.attr('href'))
        router.setAttribute('module', 'web_list')
        $searchField.val('').trigger('change');
      });
    }

    // Prevent submitting the search form
    $(Identifiers.formSelector).on('submit', (evt: JQueryEventObject): void => {
      evt.preventDefault();
    });
  }

  private linkItem(suggestion: Suggestion): TemplateResult {
    return suggestion.data.editLink
      ? html`
        <a class="dropdown-table-title-ellipsis dropdown-list-link"
           data-pageid="${suggestion.data.pageId}" href="${suggestion.data.editLink}">
          ${suggestion.data.title}
        </a>`
      : html`<span class="dropdown-table-title-ellipsis">${suggestion.data.title}</span>`;
  }
}

export default new LiveSearch();
