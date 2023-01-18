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
import PersistentStorage from './storage/persistent';
import '@typo3/backend/element/icon-element';

enum IdentifierEnum {
  pageTitle = '.t3js-title-inlineedit',
  hiddenElements = '.t3js-hidden-record',
}

/**
 * Module: @typo3/backend/page-actions
 * JavaScript implementations for page actions
 */
class PageActions {
  private $showHiddenElementsCheckbox: JQuery = null;

  constructor() {
    $((): void => {
      this.initializeElements();
      this.initializeEvents();
    });
  }

  /**
   * Initialize elements
   */
  private initializeElements(): void {
    this.$showHiddenElementsCheckbox = $('#checkShowHidden');
  }

  /**
   * Initialize events
   */
  private initializeEvents(): void {
    this.$showHiddenElementsCheckbox.on('change', this.toggleContentElementVisibility);
  }

  /**
   * Toggles the "Show hidden content elements" checkbox
   */
  private toggleContentElementVisibility(e: JQueryEventObject): void {
    const $me = $(e.currentTarget);
    const $hiddenElements = $(IdentifierEnum.hiddenElements);

    // show a spinner to show activity
    const $spinner = $('<span class="form-check-spinner"><typo3-backend-icon identifier="spinner-circle" size="small"></typo3-backend-icon></span>');
    $me.hide().after($spinner);

    if ($me.prop('checked')) {
      $hiddenElements.slideDown();
    } else {
      $hiddenElements.slideUp();
    }

    PersistentStorage.set('moduleData.web_layout.showHidden', $me.prop('checked') ? '1' : '0').then((): void => {
      $spinner.remove();
      $me.show();
    });
  }
}

export default new PageActions();
