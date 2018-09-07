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

/**
 * Module: TYPO3/CMS/Backend/FormEngine/Element/SlugElement
 * Logic for a TCA type "slug"
 *
 * For new records, changes on the other fields of the record (typically the record title) are listened
 * on as well and the response is put in as "placeholder" into the input field.
 *
 * For new and existing records, the toggle switch will allow editors to modify the slug
 *  - for new records, we only need to see if that is already in use or not (uniqueInSite), if it is taken, show a message.
 *  - for existing records, we also check for conflicts, and check if we have subpges, or if we want to add a redirect (todo)
 */
define(['jquery'], function ($) {

  /**
   *
   * @type {{}}
   * @exports TYPO3/CMS/Backend/FormEngine/Element/SlugElement
   */
  var SlugElement = {
    options: {},
    manuallyChanged: false, // This flag is set to true as soon as user submitted data to the input field
    $fullElement: null,
    $readOnlyField: null,
    $inputField: null,
    $hiddenField: null
  };

  /**
   * Initializes the SlugElement
   *
   * @param {String} selector
   * @param {Object} options
   */
  SlugElement.initialize = function (selector, options) {
    var self = this;
    var toggleButtonClass = '.t3js-form-field-slug-toggle';
    var recreateButtonClass = '.t3js-form-field-slug-recreate';
    var inputFieldClass = '.t3js-form-field-slug-input';
    var readOnlyFieldClass = '.t3js-form-field-slug-readonly';
    var hiddenFieldClass = '.t3js-form-field-slug-hidden';

    this.options = options;
    this.$fullElement = $(selector);
    this.$inputField = this.$fullElement.find(inputFieldClass);
    this.$readOnlyField = this.$fullElement.find(readOnlyFieldClass);
    this.$hiddenField = this.$fullElement.find(hiddenFieldClass);

    var fieldsToListenOn = this.options.listenerFieldNames || {};

    if (options.command === 'new') {
      // Listen on 'listenerFieldNames' for new pages. This is typically the 'title' field
      // of a page to create slugs from the title when title is set / changed.

      $.each(fieldsToListenOn, function (fieldName, field) {
        $(document).on('keyup', '[data-formengine-input-name="' + field + '"]', function (e) {
          if (!self.manuallyChanged) {
            // manuallyChanged = true stops slug generation as soon as editor set a slug manually
            SlugElement.sendSlugProposal('auto');
          }
        });
      });
    }

    // Clicking the recreate button makes new slug proposal created from 'title' field
    $(document).on('click', recreateButtonClass, function (e) {
      e.preventDefault();
      if (self.$readOnlyField.hasClass('hidden')) {
        // Switch to readonly version - similar to 'new' page where field is
        // written on the fly with title change
        self.$readOnlyField.toggleClass('hidden', false);
        self.$inputField.toggleClass('hidden', true);
      }
      SlugElement.sendSlugProposal('recreate');
    });

    // Scenario for new pages: Usually, slug is created from the page title. However, if user toggles the
    // input field and feeds an own slug, and then changes title again, the slug should stay. manuallyChanged
    // is used to track this.
    $(this.$inputField).on('keyup', function (e) {
      self.manuallyChanged = true;
      SlugElement.sendSlugProposal('manual');
    });

    // Clicking the toggle button toggles the read only field and the input field.
    // Also set the value of either the read only or the input field to the hidden field.
    $(document).on('click', toggleButtonClass, function (e) {
      e.preventDefault();
      var showReadOnlyField = self.$readOnlyField.hasClass('hidden');
      self.$readOnlyField.toggleClass('hidden', !showReadOnlyField);
      self.$inputField.toggleClass('hidden', showReadOnlyField);
      if (showReadOnlyField) {
        self.manuallyChanged = false;
        self.$hiddenField.val(self.$readOnlyField.val());
        self.$fullElement.find('.t3js-form-proposal-accepted').addClass('hidden');
        self.$fullElement.find('.t3js-form-proposal-different').addClass('hidden');
      } else {
        self.$hiddenField.val(self.$inputField.val());
      }
    });
  };

  SlugElement.sendSlugProposal = function (mode) {
    var input = {};
    if (mode === 'auto' || mode === 'recreate') {
      var fieldsToListenOn = SlugElement.options.listenerFieldNames || {};
      $.each(fieldsToListenOn, function (fieldName, field) {
        input[fieldName] = $('[data-formengine-input-name="' + field + '"]').val();
      });
    } else {
      input['manual'] = SlugElement.$inputField.val();
    }
    $.post(
      TYPO3.settings.ajaxUrls['record_slug_suggest'],
      {
        values: input,
        mode: mode,
        tableName: SlugElement.options.tableName,
        pageId: SlugElement.options.pageId,
        parentPageId: SlugElement.options.parentPageId,
        recordId: SlugElement.options.recordId,
        language: SlugElement.options.language,
        fieldName: SlugElement.options.fieldName,
        command: SlugElement.options.command,
        signature: SlugElement.options.signature
      }, function (response) {
        if (response.hasConflicts) {
          SlugElement.$fullElement.find('.t3js-form-proposal-accepted').addClass('hidden');
          SlugElement.$fullElement.find('.t3js-form-proposal-different').removeClass('hidden').find('span').text(response.proposal);
        } else {
          SlugElement.$fullElement.find('.t3js-form-proposal-accepted').removeClass('hidden').find('span').text(response.proposal);
          SlugElement.$fullElement.find('.t3js-form-proposal-different').addClass('hidden');
        }
        const isChanged = SlugElement.$hiddenField.val() !== response.proposal;
        if (isChanged) {
          SlugElement.$fullElement.find('input').trigger('change');
        }
        if (mode === 'auto' || mode === 'recreate') {
          SlugElement.$readOnlyField.val(response.proposal);
          SlugElement.$hiddenField.val(response.proposal);
        } else {
          SlugElement.$hiddenField.val(response.proposal);
        }
      },
      'json'
    );
  };

  return SlugElement;
});
