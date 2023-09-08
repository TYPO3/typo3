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

import Tablesort, { TablesortOptions } from 'tablesort';
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
      const target = (event.currentTarget as HTMLButtonElement).parentNode as HTMLTableCellElement;
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

        const button = document.createElement('button');
        button.classList.add('table-sorting-button');

        const labelWrap = document.createElement('span');
        labelWrap.classList.add('table-sorting-label');
        labelWrap.textContent = cell.textContent;
        button.appendChild(labelWrap);

        const iconWrap = document.createElement('span');
        iconWrap.classList.add('table-sorting-icon');
        const icon = new IconElement();
        icon.identifier = 'actions-sort-amount';
        icon.size = Sizes.small;
        iconWrap.appendChild(icon);
        button.appendChild(iconWrap);

        button.addEventListener('click', onClick, false);
        cell.replaceChildren(button);

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
    new TablesortWithButtons(table);

    table.addEventListener('afterSort', (event: CustomEvent) => {
      const table = (event.target as HTMLTableElement);
      const buttons = table.tHead.querySelectorAll('.table-sorting-button');
      buttons.forEach((button) => {
        button.classList.remove('table-sorting-button-active');
        const icon = button.querySelector('typo3-backend-icon');
        icon.identifier = 'actions-sort-amount';
      });

      const sortingCell = table.tHead.querySelector('th[aria-sort]') as HTMLTableCellElement;
      const sortingButton = sortingCell.querySelector('.table-sorting-button');
      sortingButton.classList.add('table-sorting-button-active');

      const sortingIcon = sortingButton.querySelector('typo3-backend-icon');
      if (sortingCell.ariaSort === 'ascending') {
        sortingIcon.identifier = 'actions-sort-amount-down';
      } else {
        sortingIcon.identifier = 'actions-sort-amount-up';
      }
    });
  }
}
