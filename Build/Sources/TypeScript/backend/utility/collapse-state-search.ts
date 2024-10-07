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

import Client from '@typo3/backend/storage/client';
import '@typo3/backend/input/clearable';
import DocumentService from '@typo3/core/document-service';
import DebounceEvent from '@typo3/core/event/debounce-event';
import RegularEvent from '@typo3/core/event/regular-event';
import Mark from '@typo3/backend/contrib/mark';
import DomHelper from '@typo3/backend/utility/dom-helper';

/**
 * Utility class to perform client side search on ul/li trees. This is used in various
 * backend modules that show trees, especially the "Active PageTsConfig" and "Active TypoScript".
 *
 * This is an add-on to collapse-state-persister.ts: When a search is performed, this search
 * result collapse/expand state is not persisted in local storage, so the state without search
 * "comes back" when a search word is reset.
 *
 * @internal
 */
class CollapseStateSearch {
  private readonly searchValueSelector: string = '.t3js-collapse-search-term';
  private searchField: HTMLInputElement;
  private searchForm: HTMLFormElement;
  private searchSessionKey: string;
  private searchValue: string = '';
  private treeContainers: NodeListOf<HTMLElement>;
  private numberOfSearchMatchesContainer: NodeListOf<HTMLElement>;
  private markInstances: Array<any> = [];

  constructor() {
    DocumentService.ready().then(() => {
      this.treeContainers = document.querySelectorAll('.t3js-collapse-states-search-tree');
      if (this.treeContainers.length === 0) {
        return;
      }
      this.numberOfSearchMatchesContainer = document.querySelectorAll('.t3js-collapse-states-search-numberOfSearchMatches');
      this.searchField = document.querySelector(this.searchValueSelector);
      this.searchForm = this.searchField.closest('form');
      this.searchSessionKey = this.searchField.dataset.persistCollapseSearchKey;
      this.searchValue = Client.get(this.searchSessionKey) ?? '';

      this.registerEvents();

      for (let i = 0; i < this.treeContainers.length; i++) {
        this.markInstances[i] = new Mark(this.treeContainers[i]);
      }

      if (this.searchValue !== '') {
        this.searchField.value = this.searchValue;
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
      for (let i = 0; i < this.treeContainers.length; i++) {
        this.filterTree(this.searchField.value, this.treeContainers[i], this.numberOfSearchMatchesContainer[i], this.markInstances[i]);
      }
    }).bindTo(this.searchForm);
  }

  private filterTree(term: string, treeContainer: HTMLElement, numberOfSearchMatchesContainer: HTMLElement, markInstance: any): void {
    // Normalize search term
    term = term.toLowerCase();

    markInstance.unmark();
    Client.set(this.searchSessionKey, term);
    if (term.length < 3) {
      numberOfSearchMatchesContainer.classList.add('hidden');
      return;
    }

    const matchingCollapsibleIds = new Set();
    const matchingNodes = [
      ...this.findNodesByIdentifier(term, treeContainer),
      ...this.findNodesByValue(term, treeContainer),
      ...this.findNodesByComment(term, treeContainer),
      ...this.findNodesByConstantSubstitution(term, treeContainer)
    ];

    numberOfSearchMatchesContainer.innerText = String(TYPO3.lang['collapse-state-search.numberOfSearchMatches']).replace('%s', String(matchingNodes.length));
    numberOfSearchMatchesContainer.classList.remove('hidden');

    matchingNodes.forEach((match: Element|null): void => {
      if (match === null) {
        return;
      }

      const collapsibleIdentifier = (match.parentElement.querySelector('[data-bs-toggle="collapse"]') as HTMLElement|null)?.dataset.bsTarget;
      if (collapsibleIdentifier !== undefined) {
        matchingCollapsibleIds.add(collapsibleIdentifier.substring(1));
      }

      const parentElements = DomHelper.parents(match, '.collapse');
      for (const parentEl of parentElements) {
        matchingCollapsibleIds.add(parentEl.id);
      }
    });

    const allNodes = Array.from(treeContainer.querySelectorAll('.collapse')) as HTMLElement[];
    for (const node of allNodes) {
      const isExpanded: boolean = node.classList.contains('show');
      const id: string = node.id;
      if (matchingCollapsibleIds.has(id)) {
        // We're not using BootstrapCollapse.getOrCreateInstance() since this is too slow when
        // dealing with many elements like with System > Configuration with TCA tree.
        if (!isExpanded) {
          const toggle: HTMLElement = document.querySelector('[data-bs-target="#' + id + '"]');
          toggle.classList.remove('collapsed');
          toggle.setAttribute('aria-expanded', 'true');
          node.classList.add('show');
        }
      } else {
        if (isExpanded) {
          const toggle: HTMLElement = document.querySelector('[data-bs-target="#' + id + '"]');
          toggle.classList.add('collapsed');
          toggle.setAttribute('aria-expanded', 'false');
          node.classList.remove('show');
        }
      }
    }

    markInstance.mark(term, {
      element: 'span',
      className: 'text-highlight'
    });
  }

  private findNodesByIdentifier(term: string, treeContainer: HTMLElement): Element[] {
    return Array.from(treeContainer.querySelectorAll('.treelist-label')).filter((el: Element): boolean => {
      return el.textContent.toLowerCase().includes(term);
    });
  }

  private findNodesByValue(term: string, treeContainer: HTMLElement): Element[] {
    const matchingValueNodes = Array.from(treeContainer.querySelectorAll('.treelist-value')).filter((el: Element): boolean => {
      return el.textContent.toLowerCase().includes(term);
    });
    return matchingValueNodes.map((node: Element): Element => {
      return node.previousElementSibling;
    });
  }

  private findNodesByComment(term: string, treeContainer: HTMLElement): Element[] {
    return Array.from(treeContainer.querySelectorAll('.treelist-comment')).filter((el: Element): boolean => {
      return el.textContent.toLowerCase().includes(term);
    });
  }

  private findNodesByConstantSubstitution(term: string, treeContainer: HTMLElement): Element[] {
    return Array.from(treeContainer.querySelectorAll('.treelist-constant-substitution')).filter((el: Element): boolean => {
      return el.textContent.toLowerCase().includes(term);
    });
  }
}

export default new CollapseStateSearch();
