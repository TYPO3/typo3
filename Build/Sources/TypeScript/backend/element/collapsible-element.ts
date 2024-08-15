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

import RegularEvent from '@typo3/core/event/regular-event';
import Icons from '@typo3/backend/icons';

enum IconIdentifier {
  collapse = 'actions-view-list-collapse',
  expand = 'actions-view-list-expand',
}

/**
 * Module: @typo3/backend/element/collapsible-element
 *
 * Functionality for collapsible elements like accordions and panels
 *
 * @example
 * <typo3-backend-collapsible>
 *   ...
 * </typo3-backend-collapsible>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class CollapsibleElement extends HTMLElement {

  public connectedCallback(): void {
    this.registerEventHandler();
  }

  private registerEventHandler(): void {
    new RegularEvent('click', this.toggleGroup).delegateTo(this, 'button[data-bs-toggle="collapse"]');
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

window.customElements.define('typo3-backend-collapsible', CollapsibleElement);
