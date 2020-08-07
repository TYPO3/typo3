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

import {KeyTypesEnum} from './Enum/KeyTypes';
import $ from 'jquery';
import PersistentStorage = require('./Storage/Persistent');
import NewContentElement = require('./Wizard/NewContentElement');

enum IdentifierEnum {
  pageTitle = '.t3js-title-inlineedit',
  hiddenElements = '.t3js-hidden-record',
  newButton = '.t3js-toggle-new-content-element-wizard',
}

/**
 * Module: TYPO3/CMS/Backend/PageActions
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
      this.initializeNewContentElementWizard();
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

    const $editActionLink = $('<a class="hidden" href="#" data-action="edit"><span class="t3-icon fa fa-pencil"></span></a>');
    $editActionLink.on('click', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.editPageTitle();
    });
    this.$pageTitle
      .on('dblclick',  (): void => {
        this.editPageTitle();
      })
      .on('mouseover', (): void => {
        $editActionLink.removeClass('hidden');
      })
      .on('mouseout', (): void => {
        $editActionLink.addClass('hidden');
      })
      .append($editActionLink);
  }

  /**
   * Initialize elements
   */
  private initializeElements(): void {
    this.$pageTitle = $(IdentifierEnum.pageTitle + ':first');
    this.$showHiddenElementsCheckbox = $('#checkTt_content_showHidden');
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
    const $spinner = $('<span />', {class: 'checkbox-spinner fa fa-circle-o-notch fa-spin'});
    $me.hide().after($spinner);

    if ($me.prop('checked')) {
      $hiddenElements.slideDown();
    } else {
      $hiddenElements.slideUp();
    }

    PersistentStorage.set('moduleData.web_layout.tt_content_showHidden', $me.prop('checked') ? '1' : '0').done((): void => {
      $spinner.remove();
      $me.show();
    });
  }

  /**
   * Changes the h1 to an edit form
   */
  private editPageTitle(): void {
    const $inputFieldWrap = $(
        '<form>' +
      '<div class="form-group">' +
      '<div class="input-group input-group-lg">' +
      '<input class="form-control t3js-title-edit-input">' +
      '<span class="input-group-btn">' +
      '<button class="btn btn-default" type="button" data-action="submit"><span class="t3-icon fa fa-floppy-o"></span></button> ' +
      '</span>' +
      '<span class="input-group-btn">' +
      '<button class="btn btn-default" type="button" data-action="cancel"><span class="t3-icon fa fa-times"></span></button> ' +
      '</span>' +
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

    $inputField.on('keyup', (e: JQueryEventObject): void => {
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
    const $inputFieldWrap = $field.parents('form');
    $inputFieldWrap.find('button').addClass('disabled');
    $field.attr('disabled', 'disabled');

    let parameters: {[k: string]: any} = {};
    let recordUid;

    if (this.pageOverlayId > 0) {
      recordUid = this.pageOverlayId;
    } else {
      recordUid = this.pageId;
    }

    parameters.data = {};
    parameters.data.pages = {};
    parameters.data.pages[recordUid] = {title: $field.val()};

    require(['TYPO3/CMS/Backend/AjaxDataHandler'], (DataHandler: any): void => {
      DataHandler.process(parameters).then((): void => {
        $inputFieldWrap.find('[data-action=cancel]').trigger('click');
        this.$pageTitle.text($field.val());
        this.initializePageTitleRenaming();
        top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
      }).catch((): void => {
        $inputFieldWrap.find('[data-action=cancel]').trigger('click');
      });
    });
  }

  /**
   * Activate New Content Element Wizard
   */
  private initializeNewContentElementWizard(): void {
    Array.from(document.querySelectorAll(IdentifierEnum.newButton)).forEach((element: HTMLElement): void => {
      element.classList.remove('disabled');
    });
    $(IdentifierEnum.newButton).on('click', (e: JQueryEventObject): void => {
      e.preventDefault();

      const $me = $(e.currentTarget);
      NewContentElement.wizard($me.attr('href'), $me.data('title'));
    });
  }
}

export = new PageActions();
