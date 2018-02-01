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
 * Module: TYPO3/CMS/Backend/FormEngineFlexForm
 * Contains all JS functions related to TYPO3 Flexforms
 * available under the latest jQuery version
 * can be used by $('myflexform').t3FormEngineFlexFormElement({options});, all .t3-flex-form containers will be called on load
 *
 * currently TYPO3.FormEngine.FlexFormElement represents one Flexform element
 * which can contain one ore more sections
 */
define(['jquery',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/FormEngine'
], function($, Modal) {

  /**
   *
   * @param {HTMLElement} el
   * @param {Object} options
   * @constructor
   * @exports TYPO3/CMS/Backend/FormEngineFlexForm
   */
  TYPO3.FormEngine.FlexFormElement = function(el, options) {
    var me = this;	// avoid scope issues
    var opts;	// shorthand options notation

    // initialization function; private
    me.initialize = function() {
      // store DOM element and jQuery object for later use
      me.el = el;
      me.$el = $(el);

      // remove any existing backups
      var old_me = me.$el.data('TYPO3.FormEngine.FlexFormElement');
      if (typeof old_me !== 'undefined') {
        me.$el.removeData('TYPO3.FormEngine.FlexFormElement');
      }

      // add a reverse reference to the DOM element
      me.$el.data('TYPO3.FormEngine.FlexFormElement', me);

      if (!options) {
        options = {};
      }

      // set some values from existing properties
      options.allowRestructure = me.$el.data('t3-flex-allow-restructure');
      options.flexformId = me.$el.attr('id');

      // store options and merge with default options
      opts = me.options = $.extend({}, TYPO3.FormEngine.FlexFormElement.defaults, options);

      // initialize events
      me.initializeEvents();

      // generate the preview text if a section is hidden on load
      me.$el.find(opts.sectionSelector).each(function() {
        me.generateSectionPreview($(this));
      });

      return me;
    };

    /**
     * init all events related to the flexform. As this method is called multiple times,
     * some handlers need to be off'ed first to prevent event stacking.
     */
    me.initializeEvents = function() {
      // Toggling all sections on/off by clicking all toggle buttons of each section
      me.$el.prev(opts.flexFormToggleAllSectionsSelector).off('click').on('click', function() {
        me.$el.find(opts.sectionToggleButtonSelector).trigger('click');
      });

      if (opts.allowRestructure) {
        // create a sortable when dragging on the header of a section
        me.createSortable();

        // allow delete of a single section
        me.$el.off('click').on('click', opts.deleteIconSelector, function(evt) {
          evt.preventDefault();

          var confirmTitle = TYPO3.lang['flexform.section.delete.title'] || 'Are you sure?';
          var confirmMessage = TYPO3.lang['flexform.section.delete.message'] || 'Are you sure you want to delete this section?';
          var $confirm = Modal.confirm(confirmTitle, confirmMessage);
          $confirm.on('confirm.button.cancel', function() {
            Modal.currentModal.trigger('modal-dismiss');
          });
          $confirm.on('confirm.button.ok', function(event) {
            $(evt.target).closest(opts.sectionSelector).hide().addClass(opts.sectionDeletedClass);
            me.setActionStatus();
            TYPO3.FormEngine.Validation.validate();
            Modal.currentModal.trigger('modal-dismiss');
          });
        });

        // allow the toggle open/close of the main selection
        me.$el.on('click', opts.sectionToggleButtonSelector, function(evt) {
          evt.preventDefault();
          var $sectionEl = $(this).closest(opts.sectionSelector);
          me.toggleSection($sectionEl);
        }).on('click', opts.sectionToggleButtonSelector + ' .form-irre-header-control', function(evt) {
          evt.stopPropagation();
        });
      }

      return me;
    };

    // initialize ourself
    me.initialize();
  };

  // setting some default values
  TYPO3.FormEngine.FlexFormElement.defaults = {
    'deleteIconSelector': '.t3js-delete',
    'sectionSelector': '.t3js-flex-section',
    'sectionContentSelector': '.t3js-flex-section-content',
    'sectionHeaderSelector': '.t3js-flex-section-header',
    'sectionHeaderPreviewSelector': '.t3js-flex-section-header-preview',
    'sectionActionInputFieldSelector': '.t3js-flex-control-action',
    'sectionToggleInputFieldSelector': '.t3js-flex-control-toggle',
    'sectionToggleIconOpenSelector': '.t3js-flex-control-toggle-icon-open',
    'sectionToggleIconCloseSelector': '.t3js-flex-control-toggle-icon-close',
    'sectionToggleButtonSelector': '[data-toggle="formengine-flex"]',
    'flexFormToggleAllSectionsSelector': '.t3js-form-field-toggle-flexsection',
    'sectionDeletedClass': 't3js-flex-section-deleted',
    'allowRestructure': 0,	// whether the form can be modified
    'flexformId': false
  };


  /**
   * Allow flexform sections to be sorted
   */
  TYPO3.FormEngine.FlexFormElement.prototype.createSortable = function() {
    var me = this;

    require(['jquery-ui/sortable'], function() {
      me.$el.sortable({
        containment: 'parent',
        handle: '.t3js-sortable-handle',
        axis: 'y',
        tolerance: 'pointer',
        stop: function() {
          me.setActionStatus();
        }
      });
    });
  };

  // Updates the "action"-status for a section. This is used to move and delete elements.
  TYPO3.FormEngine.FlexFormElement.prototype.setActionStatus = function() {
    var me = this;

    // Traverse and find how many sections are open or closed, and save the value accordingly
    me.$el.find(me.options.sectionActionInputFieldSelector).each(function(index) {
      var actionValue = ($(this).parents(me.options.sectionSelector).hasClass(me.options.sectionDeletedClass) ? 'DELETE' : index);
      $(this).val(actionValue);
    });
  };

  // Toggling flexform elements on/off
  // hides the flexform section and shows a preview text
  // or shows the form parts
  TYPO3.FormEngine.FlexFormElement.prototype.toggleSection = function($sectionEl) {

    var $contentEl = $sectionEl.find(this.options.sectionContentSelector);

    // display/hide the content of this flexform section
    $contentEl.toggle();

    if ($contentEl.is(':visible')) {
      // show the open icon, and set the hidden field for toggling to "hidden"
      $sectionEl.find(this.options.sectionToggleIconOpenSelector).show();
      $sectionEl.find(this.options.sectionToggleIconCloseSelector).hide();
      $sectionEl.find(this.options.sectionToggleInputFieldSelector).val(0);
    } else {
      // show the close icon, and set the hidden field for toggling to "1"
      $sectionEl.find(this.options.sectionToggleIconOpenSelector).hide();
      $sectionEl.find(this.options.sectionToggleIconCloseSelector).show();
      $sectionEl.find(this.options.sectionToggleInputFieldSelector).val(1);
    }

    // see if the preview content needs to be generated
    this.generateSectionPreview($sectionEl);
  };

  // function to generate the section preview in the header
  // if the section content is hidden
  // called on load and when toggling an icon
  TYPO3.FormEngine.FlexFormElement.prototype.generateSectionPreview = function($sectionEl) {
    var $contentEl = $sectionEl.find(this.options.sectionContentSelector);
    var previewContent = '';

    if (!$contentEl.is(':visible')) {
      $contentEl.find('input[type=text], textarea').each(function() {
        var content = $($.parseHTML($(this).val())).text();
        if (content.length > 50) {
          content = content.substring(0, 50) + '...';
        }
        previewContent += (previewContent ? ' / ' : '') + content;
      });
    }

    // create a preview container span element
    if ($sectionEl.find(this.options.sectionHeaderPreviewSelector).length === 0) {
      $sectionEl.find(this.options.sectionHeaderSelector).find('.t3js-record-title').parent()
        .append('<span class="' + this.options.sectionHeaderPreviewSelector.replace(/\./, '') + '"></span>');
    }

    $sectionEl.find(this.options.sectionHeaderPreviewSelector).text(previewContent);
  };

  // register the flex functions as jQuery Plugin
  $.fn.t3FormEngineFlexFormElement = function(options) {
    // apply all util functions to ourself (for use in templates, etc.)
    return this.each(function() {
      (new TYPO3.FormEngine.FlexFormElement(this, options));
    });
  };

  // Initialization Code
  $(function() {
    // run the flexform functions on all containers (which contains one or more sections)
    $('.t3-flex-container').t3FormEngineFlexFormElement();

    // Add handler to fetch container data on click on "add container" buttons
    $('.t3js-flex-container-add').on('click', function(e) {
      var me = $(this);
      e.preventDefault();
      $.ajax({
        url: TYPO3.settings.ajaxUrls['record_flex_container_add'],
        type: 'POST',
        cache: false,
        data: {
          vanillaUid: me.data('vanillauid'),
          databaseRowUid: me.data('databaserowuid'),
          command: me.data('command'),
          tableName: me.data('tablename'),
          fieldName: me.data('fieldname'),
          recordTypeValue: me.data('recordtypevalue'),
          dataStructureIdentifier: me.data('datastructureidentifier'),
          flexFormSheetName: me.data('flexformsheetname'),
          flexFormFieldName: me.data('flexformfieldname'),
          flexFormContainerName: me.data('flexformcontainername')
        },
        success: function(response) {
          me.closest('.t3-form-field-container').find('.t3-flex-container').append(response.html);
          $('.t3-flex-container').t3FormEngineFlexFormElement();
          if (response.scriptCall && response.scriptCall.length > 0) {
            $.each(response.scriptCall, function(index, value) {
              eval(value);
            });
          }
          if (response.stylesheetFiles && response.stylesheetFiles.length > 0) {
            $.each(response.stylesheetFiles, function(index, stylesheetFile) {
              var element = document.createElement('link');
              element['rel'] = 'stylesheet';
              element['type'] = 'text/css';
              element['href'] = stylesheetFile;
              document.head.appendChild(element);
            });
          }
          TYPO3.FormEngine.reinitialize();
          TYPO3.FormEngine.Validation.initializeInputFields();
          TYPO3.FormEngine.Validation.validate();
        }
      });
    });

  });
});
