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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import Icons from '@typo3/backend/icons';

/**
 * Module: @typo3/backend/element-information
 * @exports @typo3/backend/element-information
 */
class ElementInformation {
  constructor() {
    DocumentService.ready().then((): void => {
      document.querySelectorAll('div[data-persist-collapse-state]').forEach((element: HTMLElement): void => {
        const isCollapsed: boolean = !element.classList.contains('show');
        const collapseTrigger: HTMLElement = document.querySelector('.t3js-toggle-table[data-bs-target="#' + element.id + '"]');
        if (collapseTrigger !== null) {
          collapseTrigger.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
          collapseTrigger.classList.toggle('collapsed', isCollapsed);
          const collapseIcon: HTMLElement = collapseTrigger.querySelector('.t3js-icon');
          if (collapseIcon !== null) {
            this.replaceIcon(isCollapsed, collapseIcon)
          }
        }
      })

      new RegularEvent('show.bs.collapse', this.toggleCollapseIcon.bind(this)).bindTo(document);
      new RegularEvent('hide.bs.collapse', this.toggleCollapseIcon.bind(this)).bindTo(document);
    });
  }

  private toggleCollapseIcon(e: Event): void {
    const collapseIcon: HTMLElement = document.querySelector('.t3js-toggle-table[data-bs-target="#' + (e.target as HTMLElement).id + '"] .t3js-icon');
    if (collapseIcon !== null) {
      this.replaceIcon(e.type === 'hide.bs.collapse', collapseIcon)
    }
  }

  private replaceIcon(isCollapsed: boolean, collapseIcon: HTMLElement): void {
    Icons
      .getIcon((isCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), Icons.sizes.small)
      .then((icon: string): void => {
        collapseIcon.replaceWith(document.createRange().createContextualFragment(icon));
      });
  }
}

export default new ElementInformation();
