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

import Tablesort, { type TablesortOptions } from 'tablesort';
import 'tablesort.dotsep';
import 'tablesort.number';
import { IconElement } from './element/icon-element';
import { Sizes } from './enum/icon-types';

class TablesortWithButtons extends Tablesort {
  public init(el: HTMLTableElement, options: TablesortOptions) {
    let firstRow: HTMLTableRowElement, defaultSort;

    this.table = el;
    this.thead = false;
    this.options = options;

    if (el.rows && el.rows.length > 0) {
      if (el.tHead && el.tHead.rows.length > 0) {
        for (let i = 0; i < el.tHead.rows.length; i++) {
          if (el.tHead.rows[i].getAttribute('data-sort-method') === 'thead') {
            firstRow = el.tHead.rows[i];
            break;
          }
        }
        if (!firstRow) {
          firstRow = el.tHead.rows[el.tHead.rows.length - 1];
        }
        this.thead = true;
      } else {
        firstRow = el.rows[0];
      }
    }

    if (!firstRow) {
      return;
    }

    const onClick = (event: PointerEvent): void => {
      event.preventDefault();
      event.stopImmediatePropagation();
      const direction = (event.currentTarget as HTMLButtonElement).dataset.sortDirection;
      const target = (event.currentTarget as HTMLButtonElement).closest('th, td') as HTMLTableCellElement;

      // Tablesort produces different results on sorting in the same direction twice
      // when the sorting is not 100% clear and multiple cells have the same value.
      // To avoid confusion we prevent resorting when the direction does not change.
      if (this.current === target && this.current.ariaSort === direction) {
        return;
      }

      // Tablesort only supports a toggle for sorting, so we set the current direction to the opposite.
      // After that tablesort uses the correct direction for sorting.
      target.ariaSort = (direction === 'ascending') ? 'descending' : 'ascending';

      if (this.current && this.current !== target) {
        this.current.removeAttribute('aria-sort');
      }

      this.current = target;
      this.sortTable(target);
    };

    // Assume first row is the header and attach a click handler to each.
    for (let i = 0; i < firstRow.cells.length; i++) {
      const cell = firstRow.cells[i] as HTMLTableCellElement;
      cell.setAttribute('role', 'columnheader');
      if (cell.getAttribute('data-sort-method') !== 'none') {

        // Create dropdown toggle
        const toggle = document.createElement('button');
        toggle.classList.add('dropdown-toggle', 'dropdown-toggle-link');
        toggle.dataset.bsToggle = 'dropdown';
        toggle.dataset.sortingToggle = 'true';
        toggle.type = 'button';
        toggle.ariaExpanded = 'false';
        toggle.textContent = cell.textContent;
        const icon = new IconElement();
        icon.identifier = 'empty-empty';
        icon.size = Sizes.small;
        const iconWrapper = document.createElement('div');
        iconWrapper.appendChild(icon);
        toggle.appendChild(iconWrapper);
        cell.replaceChildren(toggle);

        // Create dropdown menu
        const dropdown = document.createElement('div');
        dropdown.classList.add('dropdown-menu');

        const buttonSortAscTitle = top.TYPO3.lang['labels.sorting.asc'] || 'Sort ascending';
        const buttonSortAsc = document.createElement('button');
        buttonSortAsc.classList.add('dropdown-item');
        buttonSortAsc.type = 'button';
        buttonSortAsc.title = buttonSortAscTitle;
        buttonSortAsc.ariaLabel = buttonSortAscTitle;
        buttonSortAsc.dataset.sortDirection = 'ascending';
        buttonSortAsc.addEventListener('click', onClick, false);
        const buttonSortAscColumnWrap = document.createElement('span');
        buttonSortAscColumnWrap.classList.add('dropdown-item-columns');
        buttonSortAsc.appendChild(buttonSortAscColumnWrap);
        const buttonSortAscColumnIconWrap = document.createElement('span');
        buttonSortAscColumnIconWrap.classList.add('dropdown-item-column', 'dropdown-item-column-icon', 'text-primary');
        buttonSortAscColumnWrap.appendChild(buttonSortAscColumnIconWrap);
        const buttonSortAscColumnIcon = new IconElement();
        buttonSortAscColumnIcon.identifier = 'empty-empty';
        buttonSortAscColumnIcon.size = Sizes.small;
        buttonSortAscColumnIconWrap.appendChild(buttonSortAscColumnIcon);
        const buttonSortAscColumnTitleWrap = document.createElement('span');
        buttonSortAscColumnTitleWrap.classList.add('dropdown-item-column', 'dropdown-item-column-title');
        buttonSortAscColumnTitleWrap.textContent = buttonSortAscTitle;
        buttonSortAscColumnWrap.appendChild(buttonSortAscColumnTitleWrap);
        dropdown.appendChild(buttonSortAsc);

        const buttonSortDescTitle = top.TYPO3.lang['labels.sorting.desc'] || 'Sort descending';
        const buttonSortDesc = document.createElement('button');
        buttonSortDesc.classList.add('dropdown-item');
        buttonSortDesc.type = 'button';
        buttonSortDesc.title = buttonSortDescTitle;
        buttonSortDesc.ariaLabel = buttonSortDescTitle;
        buttonSortDesc.dataset.sortDirection = 'descending';
        buttonSortDesc.addEventListener('click', onClick, false);
        const buttonSortDescColumnWrap = document.createElement('span');
        buttonSortDescColumnWrap.classList.add('dropdown-item-columns');
        buttonSortDesc.appendChild(buttonSortDescColumnWrap);
        const buttonSortDescColumnIconWrap = document.createElement('span');
        buttonSortDescColumnIconWrap.classList.add('dropdown-item-column', 'dropdown-item-column-icon', 'text-primary');
        buttonSortDescColumnWrap.appendChild(buttonSortDescColumnIconWrap);
        const buttonSortDescColumnIcon = new IconElement();
        buttonSortDescColumnIcon.identifier = 'empty-empty';
        buttonSortDescColumnIcon.size = Sizes.small;
        buttonSortDescColumnIconWrap.appendChild(buttonSortDescColumnIcon);
        const buttonSortDescColumnTitleWrap = document.createElement('span');
        buttonSortDescColumnTitleWrap.classList.add('dropdown-item-column', 'dropdown-item-column-title');
        buttonSortDescColumnTitleWrap.textContent = buttonSortDescTitle;
        buttonSortDescColumnWrap.appendChild(buttonSortDescColumnTitleWrap);
        dropdown.appendChild(buttonSortDesc);

        cell.appendChild(dropdown);

        if (cell.getAttribute('data-sort-default') !== null) {
          defaultSort = cell;
        }
      }
    }

    if (defaultSort) {
      this.current = defaultSort;
      this.sortTable(defaultSort);
    }
  }
}

export default class SortableTable {
  constructor(table: HTMLTableElement) {
    table.addEventListener('afterSort', (event: CustomEvent) => {
      const table = (event.target as HTMLTableElement);
      const buttons = table.tHead.querySelectorAll('.dropdown-toggle[data-sorting-toggle]');
      buttons.forEach((button) => {
        const iconWrap = button.querySelector(':scope > div');
        iconWrap.classList.remove('text-primary');
        const icon = button.querySelector('typo3-backend-icon');
        icon.identifier = 'empty-empty';
        icon.classList.remove('text-primary');
      });

      const menuIcons = table.tHead.querySelectorAll('.dropdown-toggle[data-sorting-toggle] + .dropdown-menu typo3-backend-icon');
      menuIcons.forEach((menuIcon: IconElement) => {
        menuIcon.identifier = 'empty-empty';
      });

      const sortingCell = table.tHead.querySelector('th[aria-sort]') as HTMLTableCellElement;
      const sortingButton = sortingCell.querySelector('.dropdown-toggle[data-sorting-toggle]');
      const sortingIconWrap = sortingButton.querySelector(':scope > div');
      sortingIconWrap.classList.add('text-primary');
      const sortingIcon = sortingButton.querySelector('typo3-backend-icon');
      const sortingDropdown = sortingCell.querySelector('.dropdown-menu');

      if (sortingCell.ariaSort === 'ascending') {
        sortingIcon.identifier = 'actions-sort-amount-up';
      } else {
        sortingIcon.identifier = 'actions-sort-amount-down';
      }

      const sortingButtons = sortingDropdown.querySelectorAll('.dropdown-item');
      sortingButtons.forEach((button: HTMLButtonElement) => {
        const sortingButtonDirection = button.dataset.sortDirection;
        const sortingButtonIcon = button.querySelector('typo3-backend-icon');
        if (sortingButtonDirection === sortingCell.ariaSort) {
          sortingButtonIcon.identifier = 'actions-dot';
        } else {
          sortingButtonIcon.identifier = 'empty-empty';
        }
      });
    });

    new TablesortWithButtons(table);
  }
}
