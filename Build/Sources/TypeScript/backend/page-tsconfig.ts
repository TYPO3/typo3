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

import BrowserSession from '@typo3/backend/storage/browser-session';
import {Collapse as BootstrapCollapse} from 'bootstrap';
import '@typo3/backend/input/clearable';
import DocumentService from '@typo3/core/document-service';
import DebounceEvent from '@typo3/core/event/debounce-event';
import RegularEvent from '@typo3/core/event/regular-event';
import Mark from '@typo3/backend/contrib/mark';

/**
 * Module: @typo3/backend/page-tsconfig
 * JavaScript for Page TSconfig
 * @exports @typo3/backend/page-tsconfig
 */
class PageTSconfigBrowser {
  private readonly termSessionStorageKey = 'pagets-search-term';
  private searchField: HTMLInputElement;
  private searchForm: HTMLFormElement;
  private pageTsTreeContainer: HTMLElement|null;
  private markInstance: any;

  constructor() {
    DocumentService.ready().then(() => {
      this.pageTsTreeContainer = document.querySelector('.t3js-pagets-tree');
      if (this.pageTsTreeContainer === null) {
        return;
      }

      this.searchField = document.querySelector('input[name="searchValue"]');
      this.searchForm = this.searchField.closest('form');

      this.registerEvents();

      this.markInstance = new Mark(this.pageTsTreeContainer);

      const searchTermInSession = BrowserSession.get(this.termSessionStorageKey);
      if (searchTermInSession) {
        this.searchField.value = searchTermInSession;
        // Trigger "keyup" event to update clearable status
        this.searchField.dispatchEvent(new Event('keyup'));
        this.searchForm.requestSubmit();
      }
    });
  }

  private registerEvents(): void {
    this.searchField.clearable({
      onClear: (input: HTMLInputElement): void => {
        input.closest('form').requestSubmit();
      },
    });

    new DebounceEvent('input', (): void => {
      this.searchForm.requestSubmit();
    }).bindTo(this.searchField);

    new RegularEvent('submit', (e: SubmitEvent): void => {
      e.preventDefault();

      this.filterTree(this.searchField.value);
    }).bindTo(this.searchForm);
  }

  private filterTree(term: string): void {
    // Normalize search term
    term = term.toLowerCase();

    this.markInstance.unmark();
    BrowserSession.set(this.termSessionStorageKey, term);
    if (term.length < 3) {
      return;
    }

    const matchingCollapsibleIds = new Set();
    const matchingNodes = [...this.findNodesByIdentifier(term), ...this.findNodesByValue(term)];
    matchingNodes.forEach((match: Element|null): void => {
      if (match === null) {
        return;
      }

      const collapsibleIdentifier = (match.parentElement.querySelector('[data-bs-toggle="collapse"]') as HTMLElement|null)?.dataset.bsTarget;
      if (collapsibleIdentifier !== undefined) {
        matchingCollapsibleIds.add(collapsibleIdentifier.substring(1));
      }

      const parentElements = this.parents(match, '.collapse');
      for (let parentEl of parentElements) {
        matchingCollapsibleIds.add(parentEl.id);
      }
    });

    const allNodes = Array.from(this.pageTsTreeContainer.querySelectorAll('.collapse')) as HTMLElement[];
    for (let node of allNodes) {
      const collapsible = BootstrapCollapse.getOrCreateInstance(node, { toggle: false });
      if (matchingCollapsibleIds.has(node.id)) {
        collapsible.show();
      } else {
        collapsible.hide();
      }
    }

    this.markInstance.mark(term, {
      element: 'strong',
      className: 'text-danger'
    });
  }

  private findNodesByIdentifier(term: string): Element[] {
    const matches = [];
    // Search for nodes identifiers matching the term
    const exactMatches = this.pageTsTreeContainer.querySelectorAll('[data-pagets-identifier="' + term + '"]');
    matches.push(...exactMatches);

    if (exactMatches.length === 0) {
      const nearestMatches = Array.from(this.pageTsTreeContainer.querySelectorAll('[data-pagets-identifier*="' + term + '"]')).filter((element: HTMLElement) => {
        // Search the nearest node available (e.g. "mod.wiz" resolves to "mod.wizards", but no "mod.wizards.newContentElement")
        const substrStart = element.dataset.pagetsIdentifier.indexOf(term) + term.length;
        return !element.dataset.pagetsIdentifier.substring(substrStart).includes('.');
      });

      matches.push(...nearestMatches);
    }

    return matches;
  }

  private findNodesByValue(term: string): Element[] {
    const matchingValueNodes = Array.from(this.pageTsTreeContainer.querySelectorAll('.list-tree-value')).filter((el: Element): boolean => {
      return el.textContent.toLowerCase().includes(term);
    });

    return matchingValueNodes.map((node: Element): Element => {
      return node.previousElementSibling;
    });
  }

  private parents(el: Element, selector: string) {
    const parents = [];
    let closest;
    while ((closest = el.parentElement.closest(selector)) !== null) {
      el = closest;
      parents.push(closest);
    }

    return parents;
  }
}

export default new PageTSconfigBrowser();
