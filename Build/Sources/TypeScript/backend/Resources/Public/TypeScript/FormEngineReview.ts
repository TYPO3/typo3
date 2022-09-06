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

import 'bootstrap';
import $ from 'jquery';
import FormEngine = require('TYPO3/CMS/Backend/FormEngine');
import Popover = require('TYPO3/CMS/Backend/Popover');
import {Popover as BootstrapPopover} from 'bootstrap';

/**
 * Module: TYPO3/CMS/Backend/FormEngineReview
 * Enables interaction with record fields that need review
 * @exports TYPO3/CMS/Backend/FormEngineReview
 */
class FormEngineReview {

  /**
   * Class for the toggle button
   */
  private readonly toggleButtonClass: string = 't3js-toggle-review-panel';

  /**
   * Class of FormEngine labels
   */
  private readonly labelSelector: string = '.t3js-formengine-label';

  /**
   * Fetches all fields that have a failed validation
   *
   * @return {$}
   */
  public static findInvalidField(): any {
    return $(document).find('.tab-content .' + FormEngine.Validation.errorClass);
  }

  /**
   * Renders an invisible button to toggle the review panel into the least possible toolbar
   *
   * @param {Object} context
   */
  public static attachButtonToModuleHeader(context: any): void {
    const $leastButtonBar: any = $('.t3js-module-docheader-bar-buttons').children().last().find('[role="toolbar"]');
    const $button: any = $('<a />', {
      class: 'btn btn-danger btn-sm hidden ' + context.toggleButtonClass,
      href: '#',
      title: TYPO3.lang['buttons.reviewFailedValidationFields'],
    }).append(
      $('<span />', {class: 'fa fa-fw fa-info'}),
    );

    Popover.popover($button);
    $leastButtonBar.prepend($button);
  }

  /**
   * The constructor, set the class properties default values
   */
  constructor() {
    this.initialize();
  }

  /**
   * Initialize the events
   */
  public initialize(): void {
    const me: any = this;
    const $document: any = $(document);

    $((): void => {
      FormEngineReview.attachButtonToModuleHeader(me);
    });
    $document.on('t3-formengine-postfieldvalidation', this.checkForReviewableField);
  }

  /**
   * Checks if fields have failed validation. In such case, the markup is rendered and the toggle button is unlocked.
   */
  public checkForReviewableField = (): void => {
    const me: any = this;
    const $invalidFields: any = FormEngineReview.findInvalidField();
    const $toggleButton: any = $('.' + this.toggleButtonClass);

    if ($invalidFields.length > 0) {
      const $list: any = $('<div />', {class: 'list-group'});

      $invalidFields.each(function(this: Element): void {
        const $field: any = $(this);
        const $input: JQuery = $field.find('[data-formengine-validation-rules]');

        const link = document.createElement('a');
        link.classList.add('list-group-item');
        link.href = '#';
        link.textContent = $field.find(me.labelSelector).text();
        link.addEventListener('click', (e: Event) => me.switchToField(e, $input));

        $list.append(link);
      });

      $toggleButton.removeClass('hidden');
      Popover.setOptions($toggleButton, <BootstrapPopover.Options>{
        html: true,
        content: $list[0]
      });
    } else {
      $toggleButton.addClass('hidden');
      Popover.hide($toggleButton);
    }
  }

  /**
   * Finds the field in the form and focuses it
   *
   * @param {Event} e
   * @param {JQuery} $referenceField
   */
  public switchToField = (e: Event, $referenceField: JQuery): void => {
    e.preventDefault();

    // iterate possibly nested tab panels
    $referenceField.parents('[id][role="tabpanel"]').each(function(this: Element): void {
      $('[aria-controls="' + $(this).attr('id') + '"]').tab('show');
    });

    $referenceField.focus();
  }
}

// create an instance and return it
export = new FormEngineReview();
