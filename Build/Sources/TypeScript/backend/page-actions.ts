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

import { KeyTypesEnum } from './enum/key-types';
import $ from 'jquery';
import PersistentStorage from './storage/persistent';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/new-content-element-wizard-button';

enum IdentifierEnum {
  pageTitle = '.t3js-title-inlineedit',
  hiddenElements = '.t3js-hidden-record',
}

/**
 * Module: @typo3/backend/page-actions
 * JavaScript implementations for page actions
 */
class PageActions {
  private pageId: number = 0;
  private pageOverlayId: number = 0;
  private $pageTitle: JQuery = null;
  private $showHiddenElementsCheckbox: JQuery = null;

  constructor() {
    $((): void => {
      this.initializeElements();
      this.initializeEvents();
      this.initializePageTitleRenaming();
    });
  }

  /**
   * Set the page id (used in the RequireJS callback)
   *
   * @param {number} pageId
   */
  public setPageId(pageId: number): void {
    this.pageId = pageId;
  }

  /**
   * Set the overlay id
   *
   * @param {number} overlayId
   */
  public setLanguageOverlayId(overlayId: number): void {
    this.pageOverlayId = overlayId;
  }

  /**
   * Initialize page title renaming
   */
  public initializePageTitleRenaming(): void {
    if (!$.isReady) {
      $((): void => {
        this.initializePageTitleRenaming();
      });
      return;
    }
    if (this.pageId <= 0) {
      return;
    }

    const $editActionLink = $(
      '<button type="button" class="btn btn-link" aria-label="' + TYPO3.lang.editPageTitle + '" data-action="edit">' +
      '<typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>' +
      '</button>'
    );
    $editActionLink.on('click', (): void => {
      this.editPageTitle();
    });
    this.$pageTitle
      .on('dblclick', (): void => {
        this.editPageTitle();
      })
      .append($editActionLink);
  }

  /**
   * Initialize elements
   */
  private initializeElements(): void {
    this.$pageTitle = $(IdentifierEnum.pageTitle + ':first');
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

    PersistentStorage.set('moduleData.web_layout.showHidden', $me.prop('checked') ? '1' : '0').done((): void => {
      $spinner.remove();
      $me.show();
    });
  }

  /**
   * Changes the h1 to an edit form
   */
  private editPageTitle(): void {
    const $inputFieldWrap = $(
        '<form class="t3js-title-edit-form">' +
        '<div class="form-group">' +
        '<div class="input-group input-group-lg">' +
        '<input class="form-control t3js-title-edit-input">' +
        '<button class="btn btn-default" type="button" data-action="submit"><typo3-backend-icon identifier="actions-save" size="small"></typo3-backend-icon></button> ' +
        '<button class="btn btn-default" type="button" data-action="cancel"><typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon></button> ' +
        '</div>' +
        '</div>' +
        '</form>',
      ),
      $inputField = $inputFieldWrap.find('input');

    $inputFieldWrap.find('[data-action="cancel"]').on('click', (): void => {
      $inputFieldWrap.replaceWith(this.$pageTitle);
      this.initializePageTitleRenaming();
    });

    $inputFieldWrap.find('[data-action="submit"]').on('click', (): void => {
      const newPageTitle = $inputField.val().trim();
      if (newPageTitle !== '' && this.$pageTitle.text() !== newPageTitle) {
        this.saveChanges($inputField);
      } else {
        $inputFieldWrap.find('[data-action="cancel"]').trigger('click');
      }
    });

    // the form stuff is a wacky workaround to prevent the submission of the docheader form
    $inputField.parents('form').on('submit', (e: JQueryEventObject): boolean => {
      e.preventDefault();
      return false;
    });

    const $h1 = this.$pageTitle;
    $h1.children().last().remove();
    $h1.replaceWith($inputFieldWrap);
    $inputField.val($h1.text()).focus();

    // Use type 'keydown' instead of 'keyup' which would be triggered directly in case a keyboard is used to start editing.
    $inputField.on('keydown', (e: JQueryEventObject): void => {
      switch (e.which) {
        case KeyTypesEnum.ENTER:
          $inputFieldWrap.find('[data-action="submit"]').trigger('click');
          break;
        case KeyTypesEnum.ESCAPE:
          $inputFieldWrap.find('[data-action="cancel"]').trigger('click');
          break;
        default:
      }
    });
  }

  /**
   * Save the changes and reload the page tree
   *
   * @param {JQuery} $field
   */
  private saveChanges($field: JQuery): void {
    const $inputFieldWrap = $field.parents('form.t3js-title-edit-form');
    $inputFieldWrap.find('button').addClass('disabled');
    $field.attr('disabled', 'disabled');

    let parameters: { [k: string]: any } = {};
    let recordUid;

    if (this.pageOverlayId > 0) {
      recordUid = this.pageOverlayId;
    } else {
      recordUid = this.pageId;
    }

    parameters.data = {};
    parameters.data.pages = {};
    parameters.data.pages[recordUid] = { title: $field.val() };

    import('@typo3/backend/ajax-data-handler').then(({default: DataHandler}): void => {
      DataHandler.process(parameters).then((): void => {
        $inputFieldWrap.find('[data-action=cancel]').trigger('click');
        this.$pageTitle.text($field.val());
        this.initializePageTitleRenaming();
        top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
      }).catch((): void => {
        $inputFieldWrap.find('[data-action=cancel]').trigger('click');
      });
    });
  }
}

export default new PageActions();
