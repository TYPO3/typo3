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

import DebounceEvent = require('TYPO3/CMS/Core/Event/DebounceEvent');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');
import './Input/Clearable';

export default class NewContentElementWizard {
  private readonly context: Element;
  private readonly searchField: HTMLInputElement;

  private static getTabIdentifier(tab: Element): string {
    const tabLink = tab.querySelector('a') as HTMLAnchorElement;
    const [, tabIdentifier] = tabLink.href.split('#');
    return tabIdentifier;
  }

  private static countVisibleContentElements(container: Element): number {
    return container.querySelectorAll('.media-new-content-element-wizard:not(.hidden)').length;
  }

  constructor(context: JQuery) {
    this.context = context.get(0);
    this.searchField = this.context.querySelector('.t3js-contentWizard-search');

    this.registerClearable();
    this.registerEvents();
  }

  public focusSearchField(): void {
    this.searchField.focus();
  }

  private registerClearable(): void {
    this.searchField.clearable({
      onClear: (input: HTMLInputElement): void => {
        input.value =  '';
        this.filterElements(input);
      },
    });
  }

  private registerEvents(): void {
    new RegularEvent('keydown', (e: KeyboardEvent): void => {
      const target = e.target as HTMLInputElement;
      if (e.code === 'Escape') {
        e.stopImmediatePropagation();
        target.value = '';
      }
    }).bindTo(this.searchField);

    new DebounceEvent('keyup', (e: KeyboardEvent): void => {
      this.filterElements(e.target as HTMLInputElement);
    }, 150).bindTo(this.searchField);

    new RegularEvent('submit', (e: Event): void => {
      e.preventDefault();
    }).bindTo(this.searchField.closest('form'));

    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      e.stopPropagation();
    }).delegateTo(this.context, '.t3js-tabs .disabled');
  }

  private filterElements(inputField: HTMLInputElement): void {
    const form = inputField.closest('form');
    const tabContainer = form.querySelector('.t3js-tabs');
    const nothingFoundAlert = form.querySelector('.t3js-filter-noresult');

    form.querySelectorAll('.media.media-new-content-element-wizard').forEach((element: Element): void => {
      // Clean up textContent by trimming and replacing consecutive spaces with a single space
      const textContent = element.textContent.trim().replace(/\s+/g, ' ');
      element.classList.toggle('hidden', inputField.value !== '' && !RegExp(inputField.value, 'i').test(textContent));
    });

    const visibleContentElements = NewContentElementWizard.countVisibleContentElements(form);
    tabContainer.parentElement.classList.toggle('hidden', visibleContentElements === 0);
    nothingFoundAlert.classList.toggle('hidden', visibleContentElements > 0);
    this.switchTabIfNecessary(tabContainer);
  }

  private switchTabIfNecessary(tabContainer: Element): void {
    const currentActiveTab = tabContainer.querySelector('.active');
    const siblings = Array.from(currentActiveTab.parentNode.children);

    for (let sibling of siblings) {
      const siblingTabIdentifier = NewContentElementWizard.getTabIdentifier(sibling);
      sibling.classList.toggle('disabled', !this.hasTabContent(siblingTabIdentifier));
    }

    if (!this.hasTabContent(NewContentElementWizard.getTabIdentifier(currentActiveTab))) {
      for (let sibling of siblings) {
        if (sibling === currentActiveTab) {
          // We already know the current active tab has no content, that's why we're here in the first place
          continue;
        }

        const siblingTabIdentifier = NewContentElementWizard.getTabIdentifier(sibling);
        if (this.hasTabContent(siblingTabIdentifier)) {
          this.switchTab(tabContainer.parentElement, siblingTabIdentifier);
          break;
        }
      }
    }
  }

  private hasTabContent(tabIdentifier: string): boolean {
    const tabContentContainer = this.context.querySelector(`#${tabIdentifier}`);
    return NewContentElementWizard.countVisibleContentElements(tabContentContainer) > 0;
  }

  /**
   * Switches the tab to the requested one. Unfortunately, bootstrap has a bug and searches the tab content in document,
   * whereas top.document is our correct context.
   *
   * @param {HTMLElement} tabContainerWrapper
   * @param {string} tabIdentifier
   */
  private switchTab(tabContainerWrapper: HTMLElement, tabIdentifier: string): void {
    const tabElement = tabContainerWrapper.querySelector(`a[href="#${tabIdentifier}"]`);
    const tabContentElement = this.context.querySelector(`#${tabIdentifier}`);

    tabContainerWrapper.querySelector('.t3js-tabmenu-item.active').classList.remove('active');
    tabContainerWrapper.querySelector('.tab-pane.active').classList.remove('active');

    tabElement.parentElement.classList.add('active');
    tabContentElement.classList.add('active');
  }
}
