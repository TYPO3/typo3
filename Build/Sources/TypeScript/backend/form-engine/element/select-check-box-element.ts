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

enum Identifier {
  toggleGroup = '.t3js-toggle-selectcheckbox-group',
}

enum IconIdentifier {
  collapse = 'actions-view-list-collapse',
  expand = 'actions-view-list-expand',
}

class SelectCheckBoxElement {
  constructor() {
    DocumentService.ready().then((): void => {
      this.registerEventHandler();
    });
  }

  /**
   * Registers the events for the header collapse icon toggling
   */
  private registerEventHandler(): void {
    new RegularEvent('click', this.toggleGroup).delegateTo(document, Identifier.toggleGroup);
  }

  private toggleGroup(e: MouseEvent, targetEl: HTMLElement): void {
    e.preventDefault();

    const isExpanded = targetEl.ariaExpanded === 'true';
    const collapseIcon = targetEl.querySelector('.collapseIcon');
    const toggleIcon = isExpanded ? IconIdentifier.collapse : IconIdentifier.expand;

    Icons.getIcon(toggleIcon, Icons.sizes.small).then((icon: string): void => {
      collapseIcon.innerHTML = icon;
    });
  }
}

export default SelectCheckBoxElement;
