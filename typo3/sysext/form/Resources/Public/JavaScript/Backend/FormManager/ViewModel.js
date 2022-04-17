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
 * Module: TYPO3/CMS/Form/Backend/FormManager/ViewModel
 */
define(['jquery',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Backend/MultiStepWizard',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Core/SecurityUtility'
], function($, Modal, Severity, MultiStepWizard, Icons, Notification, SecurityUtility) {
  'use strict';

  return (function($, Modal, Severity, MultiStepWizard, Icons, Notification, SecurityUtility) {

    var securityUtility = new SecurityUtility();

    /**
     * @private
     *
     * @var object
     */
    var _formManagerApp = null;

    /**
     * @private
     *
     * @var object
     */
    var _domElementIdentifierCache = {};

    /**
     * @private
     *
     * @return void
     */
    function _domElementIdentifierCacheSetup() {
      _domElementIdentifierCache = {
        newFormModalTrigger: {identifier: '[data-identifier="newForm"]'},
        duplicateFormModalTrigger: {identifier: '[data-identifier="duplicateForm"]'},
        removeFormModalTrigger: {identifier: '[data-identifier="removeForm"]'},

        newFormModeButton: {identifier: '[data-identifier="newFormModeButton"]'},
        newFormName: {identifier: '[data-identifier="newFormName"]'},
        newFormSavePath: {identifier: '[data-identifier="newFormSavePath"]'},
        newFormPrototypeName: {identifier: '[data-identifier="newFormPrototypeName"]'},
        newFormTemplate: {identifier: '[data-identifier="newFormTemplate"]'},

        duplicateFormName: {identifier: '[data-identifier="duplicateFormName"]'},
        duplicateFormSavePath: {identifier: '[data-identifier="duplicateFormSavePath"]'},

        showReferences: {identifier: '[data-identifier="showReferences"]'},
        referenceLink: {identifier: '[data-identifier="referenceLink"]'},

        tooltip: {identifier: '[data-bs-toggle="tooltip"]'},

        moduleBody: {class: '.module-body.t3js-module-body'},
        t3Logo: {class: '.t3-message-page-logo'},
        t3Footer: {id: '#t3-footer'}
      }
    };

    /**
     * @private
     *
     * @return void
     * @throws 1477506500
     * @throws 1477506501
     * @throws 1477506502
     */
    function _newFormSetup() {
      $(getDomElementIdentifier('newFormModalTrigger')).on('click', function(e) {
        e.preventDefault();

        /**
         * Wizard step 1
         */
        MultiStepWizard.addSlide('new-form-step-1', TYPO3.lang['formManager.newFormWizard.step1.title'], '', Severity.info, TYPO3.lang['formManager.newFormWizard.step1.progressLabel'], function(slide) {
          Icons.getIcon('actions-add', Icons.sizes.small).then(function (addIconMarkup) {
            Icons.getIcon('form-page', Icons.sizes.large).then(function (duplicateIconMarkup) {
              Icons.getIcon('apps-pagetree-page-default', Icons.sizes.large).then(function (blankIconMarkup) {
                var folders, html, modal, nextButton;

                modal = MultiStepWizard.setup.$carousel.closest('.modal');
                nextButton = modal.find('.modal-footer').find('button[name="next"]');

                MultiStepWizard.blurCancelStep();
                MultiStepWizard.lockNextStep();
                MultiStepWizard.lockPrevStep();

                folders = _formManagerApp.getAccessibleFormStorageFolders();
                if (folders.length === 0) {
                  html = '<div class="new-form-modal">'
                    + '<div class="row">'
                    + '<label class="col col-form-label">' + TYPO3.lang['formManager.newFormWizard.step1.noStorages'] + '</label>'
                    + '</div>'
                    + '</div>';

                  slide.html(html);
                  _formManagerApp.assert(false, 'No accessible form storage folders', 1477506500);
                }

                html = '<div class="new-form-modal">'

                html += '<div class="card-container">'
                  + '<div class="card card-size-medium">'
                  + '<div class="card-header">'
                  + '<div class="card-icon">' + blankIconMarkup + '</div>'
                  + '<div class="card-header-body">'
                  + '<h1 class="card-title">' + TYPO3.lang['formManager.blankForm.label'] + '</h1>'
                  + '<span class="card-subtitle">' + TYPO3.lang['formManager.blankForm.subtitle'] + '</span>'
                  + '</div>'
                  + '</div>'
                  + '<div class="card-body">'
                  + '<p class="card-text">' + TYPO3.lang['formManager.blankForm.description'] + '</p>'
                  + '</div>'
                  + '<div class="card-footer">'
                  + '<button type="button" class="btn btn-success" data-inline="1" value="blank" data-identifier="newFormModeButton">' + addIconMarkup + ' ' + TYPO3.lang['formManager.blankForm.label'] + '</button>'
                  + '</div>'
                  + '</div>'
                  + '<div class="card card-size-medium">'
                  + '<div class="card-header">'
                  + '<div class="card-icon">' + duplicateIconMarkup + '</div>'
                  + '<div class="card-header-body">'
                  + '<h1 class="card-title">' + TYPO3.lang['formManager.predefinedForm.label'] + '</h1>'
                  + '<span class="card-subtitle">' + TYPO3.lang['formManager.predefinedForm.subtitle'] + '</span>'
                  + '</div>'
                  + '</div>'
                  + '<div class="card-body">'
                  + '<p class="card-text">' + TYPO3.lang['formManager.predefinedForm.description'] + '</p>'
                  + '</div>'
                  + '<div class="card-footer">'
                  + '<button type="button" class="btn btn-success" data-inline="1" value="predefined" data-identifier="newFormModeButton">' + addIconMarkup + ' ' + TYPO3.lang['formManager.predefinedForm.label'] + '</button>'
                  + '</div>'
                  + '</div>';

                html += '</div>';

                slide.html(html);

                $(getDomElementIdentifier('newFormModeButton'), modal).on('click', function (e) {
                  MultiStepWizard.set('newFormMode', $(this).val());
                  MultiStepWizard.unlockNextStep().trigger('click');
                });

                nextButton.on('click', function() {
                  Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
                    slide.html($('<div />', {class: 'text-center'}).append(markup));
                  });
                });
              });
            });
          });
        });

        /**
         * Wizard step 2
         */
        MultiStepWizard.addSlide('new-form-step-2', TYPO3.lang['formManager.newFormWizard.step2.title'], '', Severity.info, top.TYPO3.lang['wizard.progressStep.configure'], function(slide, settings) {
          var addOnTemplateChangeEvents, folders, html, modal, nextButton, prototypes, prototypeNameSelect,
            savePathSelect, templates, templateSelect;

          MultiStepWizard.lockNextStep();
          MultiStepWizard.unlockPrevStep();

          modal = MultiStepWizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          folders = _formManagerApp.getAccessibleFormStorageFolders();

          if (!settings['savePath']) {
            MultiStepWizard.set('savePath', folders[0]['value']);
            MultiStepWizard.set('savePathName', folders[0]['label']);
          }

          if (folders.length > 1) {
            savePathSelect = $('<select class="new-form-save-path form-select" id="new-form-save-path" data-identifier="newFormSavePath" />');
            for (var i = 0, len = folders.length; i < len; ++i) {
              var option = new Option(folders[i]['label'], folders[i]['value']);
              $(savePathSelect).append(option);
            }
          }

          prototypes = _formManagerApp.getPrototypes();
          _formManagerApp.assert(prototypes.length > 0, 'No prototypes available', 1477506501);

          if (!settings['prototypeName']) {
            MultiStepWizard.set('prototypeName', prototypes[0]['value']);
            MultiStepWizard.set('prototypeNameName', prototypes[0]['label']);
          }

          prototypeNameSelect = $('<select class="new-form-prototype-name form-select" id="new-form-prototype-name" data-identifier="newFormPrototypeName" />');
          for (var i = 0, len = prototypes.length; i < len; ++i) {
            var option = new Option(prototypes[i]['label'], prototypes[i]['value']);
            $(prototypeNameSelect).append(option);
          }

          templates = _formManagerApp.getTemplatesForPrototype(prototypes[0]['value']);
          _formManagerApp.assert(templates.length > 0, 'No templates available', 1477506502);

          if (!settings['templatePath']) {
            MultiStepWizard.set('templatePath', templates[0]['value']);
            MultiStepWizard.set('templatePathName', templates[0]['label']);
          }

          templateSelect = $('<select class="new-form-template form-select" id="new-form-template" data-identifier="newFormTemplate" />');
          for (var i = 0, len = templates.length; i < len; ++i) {
            var option = new Option(templates[i]['label'], templates[i]['value']);
            $(templateSelect).append(option);
          }

          html = '<div class="new-form-modal">';

          if (settings['newFormMode'] === 'blank') {
            html += '<h5 class="form-section-headline">' + TYPO3.lang['formManager.blankForm.label'] + '</h5>';
            MultiStepWizard.set('templatePath', 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml');
            MultiStepWizard.set('templatePathName', TYPO3.lang['formManager.blankForm.label']);
          } else {
            html += '<h5 class="form-section-headline">' + TYPO3.lang['formManager.predefinedForm.label'] + '</h5>'
            if (prototypes.length > 1) {
              html += '<div class="form-group">'
                + '<label for="new-form-prototype-name">' + '<strong>' + TYPO3.lang['formManager.form_prototype'] + '</strong>' + '</label>'
                + '<div class="formengine-field-item t3js-formengine-field-item">'
                + '<div class="form-control-wrap">' + $(prototypeNameSelect)[0].outerHTML + '</div>'
                + '</div>'
                + '</div>';
            }

            if (templates.length > 1) {
              html += '<div class="form-group">'
                + '<label for="new-form-template">' + '<strong>' + TYPO3.lang['formManager.form_template'] + '</strong>' + '</label>'
                + '<div class="formengine-field-item t3js-formengine-field-item">'
                + '<span class="formengine-field-item-description text-muted">' + TYPO3.lang['formManager.form_template_description'] + '</span>'
                + '<div class="form-control-wrap">' + $(templateSelect)[0].outerHTML + '</div>'
                + '</div>'
                + '</div>';
            }
          }

          html += '<div class="form-group">'
            + '<label for="new-form-name">' + '<strong>' + TYPO3.lang['formManager.form_name'] + '</strong>' + '</label>'
            + '<div class="formengine-field-item t3js-formengine-field-item">'
            + '<span class="formengine-field-item-description text-muted">' + TYPO3.lang['formManager.form_name_description'] + '</span>'
            + '<div class="form-control-wrap">';

          if (settings['formName']) {
            html += '<input class="form-control" id="new-form-name" data-identifier="newFormName" value="' + securityUtility.encodeHtml(settings['formName']) + '" />';

            setTimeout(
              function() {
                MultiStepWizard.unlockNextStep();
              }, 200);
          } else {
            html += '<input class="form-control has-error" id="new-form-name" data-identifier="newFormName" />';
          }

          html += '</div>'
            + '</div>'
            + '</div>';

          if (savePathSelect) {
            html += '<div class="form-group">'
              + '<label for="new-form-save-path">' + '<strong>' + TYPO3.lang['formManager.form_save_path'] + '</strong>' + '</label>'
              + '<div class="formengine-field-item t3js-formengine-field-item">'
              + '<span class="formengine-field-item-description text-muted">' + TYPO3.lang['formManager.form_save_path_description'] + '</span>'
              + '<div class="form-control-wrap">' + $(savePathSelect)[0].outerHTML + '</div>'
              + '</div>'
              + '</div>';
          }

          html += '</div>';

          slide.html(html);

          if (settings['savePath']) {
            $(getDomElementIdentifier('newFormSavePath'), modal).val(settings['savePath']);
          }

          if (settings['templatePath']) {
            $(getDomElementIdentifier('newFormTemplate'), modal).val(settings['templatePath']);
          }

          if (prototypes.length > 1) {
            $(getDomElementIdentifier('newFormPrototypeName'), modal).focus();
          } else if (templates.length > 1) {
            $(getDomElementIdentifier('newFormTemplate'), modal).focus();
          }

          addOnTemplateChangeEvents = function() {
            $(getDomElementIdentifier('newFormTemplate'), modal).on('change', function(e) {
              MultiStepWizard.set('templatePath', $(getDomElementIdentifier('newFormTemplate') + ' option:selected', modal).val());
              MultiStepWizard.set('templatePathName', $(getDomElementIdentifier('newFormTemplate') + ' option:selected', modal).text());
              MultiStepWizard.set('templatePathOnPrev', $(getDomElementIdentifier('newFormTemplate') + ' option:selected', modal).val());
            });
          };

          $(getDomElementIdentifier('newFormPrototypeName'), modal).on('change', function(e) {
            MultiStepWizard.set('prototypeName', $(getDomElementIdentifier('newFormPrototypeName') + ' option:selected', modal).val());
            MultiStepWizard.set('prototypeNameName', $(getDomElementIdentifier('newFormPrototypeName') + ' option:selected', modal).text());

            templates = _formManagerApp.getTemplatesForPrototype($(this).val());
            $(getDomElementIdentifier('newFormTemplate'), modal).off().empty();
            for (var i = 0, len = templates.length; i < len; ++i) {
              var option = new Option(templates[i]['label'], templates[i]['value']);
              $(getDomElementIdentifier('newFormTemplate'), modal).append(option);
              MultiStepWizard.set('templatePath', templates[0]['value']);
              MultiStepWizard.set('templatePathName', templates[0]['label']);
            }
            addOnTemplateChangeEvents();
          });

          addOnTemplateChangeEvents();

          if (settings['prototypeName']) {
            $(getDomElementIdentifier('newFormPrototypeName'), modal).val(settings['prototypeName']);
            $(getDomElementIdentifier('newFormPrototypeName'), modal).trigger('change');

            if (settings['templatePathOnPrev']) {
              $(getDomElementIdentifier('newFormTemplate'), modal).find('option[value="' + settings['templatePathOnPrev'] + '"]').prop('selected', true);
              $(getDomElementIdentifier('newFormTemplate'), modal).trigger('change');
            }
          }

          $(getDomElementIdentifier('newFormName'), modal).focus();

          $(getDomElementIdentifier('newFormName'), modal).on('keyup paste', function(e) {
            if ($(this).val().length > 0) {
              $(this).removeClass('has-error');
              MultiStepWizard.unlockNextStep();
              MultiStepWizard.set('formName', $(this).val());
              if (e.code === 'Enter') {
                MultiStepWizard.triggerStepButton('next');
              }
            } else {
              $(this).addClass('has-error');
              MultiStepWizard.lockNextStep();
            }
          });

          $(getDomElementIdentifier('newFormSavePath'), modal).on('change', function(e) {
            MultiStepWizard.set('savePath', $(getDomElementIdentifier('newFormSavePath') + ' option:selected', modal).val());
            MultiStepWizard.set('savePathName', $(getDomElementIdentifier('newFormSavePath') + ' option:selected', modal).text());
          });

          if (settings['newFormMode'] !== 'blank' && !settings['templatePathName']) {
            MultiStepWizard.set('templatePathName', $(getDomElementIdentifier('newFormTemplate') + ' option:selected', modal).text());
          }

          nextButton.on('click', function() {
            MultiStepWizard.setup.forceSelection = false;
            Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
              slide.html($('<div />', {class: 'text-center'}).append(markup));
            });
          });
        });

        /**
         * Wizard step 3
         */
        MultiStepWizard.addSlide('new-form-step-3', TYPO3.lang['formManager.newFormWizard.step3.title'], '', Severity.info, TYPO3.lang['formManager.newFormWizard.step3.progressLabel'], function(slide, settings) {
          Icons.getIcon('actions-cog', Icons.sizes.small).then(function (formPrototypeIconMarkup) {
            Icons.getIcon('actions-file-t3d', Icons.sizes.small).then(function (formTemplateIconMarkup) {
              Icons.getIcon('actions-tag', Icons.sizes.small).then(function (formNameIconMarkup) {
                Icons.getIcon('actions-database', Icons.sizes.small).then(function (formStorageMarkup) {
                  var html, modal, nextButton;

                  modal = MultiStepWizard.setup.$carousel.closest('.modal');
                  nextButton = modal.find('.modal-footer').find('button[name="next"]');

                  html = '<div class="new-form-modal">';

                  html += '<div class="mb-3">'
                    + '<h5 class="form-section-headline">' + TYPO3.lang['formManager.newFormWizard.step3.check'] + '</h5>'
                    + '<p>' + TYPO3.lang['formManager.newFormWizard.step3.message'] + '</p>'
                    + '</div>'
                    + '<div class="alert alert-notice">'
                    + '<div class="alert-body mt-1">'

                  if (settings['prototypeNameName']) {
                    html += '<div class="dropdown-table-row">'
                      + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">'
                      + formPrototypeIconMarkup
                      + '</div>'
                      + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-title">'
                      + TYPO3.lang['formManager.form_prototype']
                      + '</div>'
                      + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-value ">'
                      + securityUtility.encodeHtml(settings['prototypeNameName'])
                      + '</div>'
                      + '</div>';
                  }

                  if (settings['templatePathName']) {
                    html += '<div class="dropdown-table-row">'
                      + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">'
                      + formTemplateIconMarkup
                      + '</div>'
                      + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-title">'
                      + TYPO3.lang['formManager.form_template']
                      + '</div>'
                      + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-value ">'
                      + securityUtility.encodeHtml(settings['templatePathName'])
                      + '</div>'
                      + '</div>';
                  }

                  html += '<div class="dropdown-table-row">'
                    + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">'
                    + formNameIconMarkup
                    + '</div>'
                    + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-title">'
                    + TYPO3.lang['formManager.form_name']
                    + '</div>'
                    + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-value ">'
                    + securityUtility.encodeHtml(settings['formName'])
                    + '</div>'
                    + '</div>'
                    + '<div class="dropdown-table-row">'
                    + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">'
                    + formStorageMarkup
                    + '</div>'
                    + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-title">'
                    + TYPO3.lang['formManager.form_save_path']
                    + '</div>'
                    + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-value ">'
                    + securityUtility.encodeHtml(settings['savePathName'])
                    + '</div>'
                    + '</div>';

                  html += '</div>'
                    + '</div>'
                    + '</div>';

                  slide.html(html);

                  nextButton.focus();

                  nextButton.on('click', function(e) {
                    MultiStepWizard.setup.forceSelection = false;
                    Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
                      slide.html($('<div />', {class: 'text-center'}).append(markup));
                    });
                  });
                });
              });
            });
          });
        });

        /**
         * Wizard step 4
         */
        MultiStepWizard.addFinalProcessingSlide(function() {
          $.post(_formManagerApp.getAjaxEndpoint('create'), {
            tx_form_web_formformbuilder: {
              formName: MultiStepWizard.setup.settings['formName'],
              templatePath: MultiStepWizard.setup.settings['templatePath'],
              prototypeName: MultiStepWizard.setup.settings['prototypeName'],
              savePath: MultiStepWizard.setup.settings['savePath']
            }
          }, function(data, textStatus, jqXHR) {
            if (data['status'] === 'success') {
              document.location = data.url;
            } else {
              Notification.error(TYPO3.lang['formManager.newFormWizard.step4.errorTitle'], TYPO3.lang['formManager.newFormWizard.step4.errorMessage'] + " " + data['message']);
            }
            MultiStepWizard.dismiss();
          }).fail(function(jqXHR, textStatus, errorThrown) {
            var parser = new DOMParser(),
              responseDocument = parser.parseFromString(jqXHR.responseText, "text/html"),
              responseBody = $(responseDocument.body);

            Notification.error(textStatus, errorThrown, 2);
            MultiStepWizard.dismiss();

            $(getDomElementIdentifier('t3Logo', 'class'), responseBody).remove();
            $(getDomElementIdentifier('t3Footer', 'id'), responseBody).remove();
            $(getDomElementIdentifier('moduleBody', 'class')).html(responseBody.html());
          });
        }).done(function() {
          MultiStepWizard.show();
        });
      });
    };

    /**
     * @private
     *
     * @return void
     */
    function _removeFormSetup() {
      $(getDomElementIdentifier('removeFormModalTrigger')).on('click', function(e) {
        var modalButtons = [], that;

        e.preventDefault();
        that = $(this);

        modalButtons.push({
          text: TYPO3.lang['formManager.cancel'],
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: function() {
            Modal.currentModal.trigger('modal-dismiss');
          }
        });

        modalButtons.push({
          text: TYPO3.lang['formManager.remove_form'],
          active: true,
          btnClass: 'btn-warning',
          name: 'createform',
          trigger: function() {
            document.location = _formManagerApp.getAjaxEndpoint('delete') + '&tx_form_web_formformbuilder[formPersistenceIdentifier]=' + that.data('formPersistenceIdentifier');
            Modal.currentModal.trigger('modal-dismiss');
          }
        });

        Modal.show(
          TYPO3.lang['formManager.remove_form_title'],
          TYPO3.lang['formManager.remove_form_message'],
          Severity.warning,
          modalButtons
        );
      });
    };

    /**
     * @private
     *
     * @return void
     * @throws 1477649539
     */
    function _duplicateFormSetup() {
      $(getDomElementIdentifier('duplicateFormModalTrigger')).on('click', function(e) {
        var that;

        e.preventDefault();
        that = $(this);

        /**
         * Wizard step 1
         */
        MultiStepWizard.addSlide('duplicate-form-step-1', TYPO3.lang['formManager.duplicateFormWizard.step1.title'].replace('{0}', that.data('formName')), '', Severity.info, top.TYPO3.lang['wizard.progressStep.configure'], function(slide, settings) {
          var addOnTemplateChangeEvents, folders, html, modal, nextButton, prototypes, prototypeNameSelect,
            savePathSelect, templates, templateSelect;

          MultiStepWizard.lockPrevStep();
          MultiStepWizard.lockNextStep();

          modal = MultiStepWizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          folders = _formManagerApp.getAccessibleFormStorageFolders();
          _formManagerApp.assert(folders.length > 0, 'No accessible form storage folders', 1477649539);

          MultiStepWizard.set('formPersistenceIdentifier', that.data('formPersistenceIdentifier'));
          MultiStepWizard.set('savePath', folders[0]['value']);
          if (folders.length > 1) {
            savePathSelect = $('<select class="duplicate-form-save-path form-select" data-identifier="duplicateFormSavePath" />');
            for (var i = 0, len = folders.length; i < len; ++i) {
              var option = new Option(folders[i]['label'], folders[i]['value']);
              $(savePathSelect).append(option);
            }
          }

          html = '<div class="duplicate-form-modal">'
            + '<div class="row mb-3">'
            + '<label class="col-4 col-form-label">' + TYPO3.lang['formManager.new_form_name'] + '</label>'
            + '<div class="col-8"><input class="duplicate-form-name form-control has-error" data-identifier="duplicateFormName" /></div>';

          if (savePathSelect) {
            html += '<label class="col col-form-label">' + TYPO3.lang['formManager.form_save_path'] + '</label>' + $(savePathSelect)[0].outerHTML;
          }

          html += '</div>'
            + '</div>';

          slide.html(html);
          $(getDomElementIdentifier('duplicateFormName'), modal).focus();

          $(getDomElementIdentifier('duplicateFormName'), modal).on('keyup paste', function(e) {
            if ($(this).val().length > 0) {
              $(this).removeClass('has-error');
              MultiStepWizard.unlockNextStep();
              MultiStepWizard.set('formName', $(this).val());
              if (e.code === 'Enter') {
                MultiStepWizard.triggerStepButton('next');
              }
            } else {
              $(this).addClass('has-error');
              MultiStepWizard.lockNextStep();
            }
          });

          nextButton.on('click', function(e) {
            MultiStepWizard.setup.forceSelection = false;
            Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
              MultiStepWizard.set('confirmationDuplicateFormName', that.data('formName'));

              if (folders.length > 1) {
                MultiStepWizard.set('savePath', $(getDomElementIdentifier('duplicateFormSavePath') + ' option:selected', modal).val());
                MultiStepWizard.set('confirmationDuplicateFormSavePath', $(getDomElementIdentifier('duplicateFormSavePath') + ' option:selected', modal).text());
              } else {
                MultiStepWizard.set('savePath', folders[0]['value']);
                MultiStepWizard.set('confirmationDuplicateFormSavePath', folders[0]['label']);
              }

              slide.html($('<div />', {class: 'text-center'}).append(markup));
            });
          });
        });

        /**
         * Wizard step 2
         */
        MultiStepWizard.addSlide('duplicate-form-step-2', TYPO3.lang['formManager.duplicateFormWizard.step2.title'], '', Severity.info, TYPO3.lang['formManager.duplicateFormWizard.step2.progressLabel'], function(slide, settings) {
          Icons.getIcon('actions-file-t3d', Icons.sizes.small).then(function (formTemplateIconMarkup) {
            Icons.getIcon('actions-tag', Icons.sizes.small).then(function (formNameIconMarkup) {
              Icons.getIcon('actions-database', Icons.sizes.small).then(function (formStorageMarkup) {
                var html, modal, nextButton;

                MultiStepWizard.unlockPrevStep();
                MultiStepWizard.unlockNextStep();

                modal = MultiStepWizard.setup.$carousel.closest('.modal');
                nextButton = modal.find('.modal-footer').find('button[name="next"]');

                html = '<div class="new-form-modal">'
                  + '<div class="row">'
                  + '<div class="col">';

                html += '<div class="mb-3">'
                  + '<h5 class="form-section-headline">' + TYPO3.lang['formManager.duplicateFormWizard.step2.check'] + '</h5>'
                  + '<p>' + TYPO3.lang['formManager.newFormWizard.step3.message'] + '</p>'
                  + '</div>'
                  + '<div class="alert alert-notice">'
                  + '<div class="alert-body mt-1">'
                  + '<div class="dropdown-table-row">'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">'
                  + formTemplateIconMarkup
                  + '</div>'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-title">'
                  + TYPO3.lang['formManager.form_copied']
                  + '</div>'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-value">'
                  + securityUtility.encodeHtml(settings['confirmationDuplicateFormName'])
                  + '</div>'
                  + '</div>'
                  + '<div class="dropdown-table-row">'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">'
                  + formNameIconMarkup
                  + '</div>'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-title">'
                  + TYPO3.lang['formManager.form_name']
                  + '</div>'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-value">'
                  + securityUtility.encodeHtml(settings['formName'])
                  + '</div>'
                  + '</div>'
                  + '<div class="dropdown-table-row">'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">'
                  + formStorageMarkup
                  + '</div>'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-title">'
                  + TYPO3.lang['formManager.form_save_path']
                  + '</div>'
                  + '<div class="dropdown-table-column dropdown-table-column-top dropdown-table-value">'
                  + securityUtility.encodeHtml(settings['confirmationDuplicateFormSavePath'])
                  + '</div>'
                  + '</div>'
                  + '</div>'
                  + '</div>';

                html += '</div>'
                  + '</div>'
                  + '</div>';

                slide.html(html);

                nextButton.focus();

                nextButton.on('click', function(e) {
                  MultiStepWizard.setup.forceSelection = false;
                  Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
                    slide.html($('<div />', {class: 'text-center'}).append(markup));
                  });
                });
              });
            });
          });
        });

        /**
         * Wizard step 3
         */
        MultiStepWizard.addFinalProcessingSlide(function() {
          $.post(_formManagerApp.getAjaxEndpoint('duplicate'), {
            tx_form_web_formformbuilder: {
              formName: MultiStepWizard.setup.settings['formName'],
              formPersistenceIdentifier: MultiStepWizard.setup.settings['formPersistenceIdentifier'],
              savePath: MultiStepWizard.setup.settings['savePath']
            }
          }, function(data, textStatus, jqXHR) {
            if (data['status'] === 'success') {
              document.location = data.url;
            } else {
              Notification.error(TYPO3.lang['formManager.duplicateFormWizard.step3.errorTitle'], TYPO3.lang['formManager.duplicateFormWizard.step3.errorMessage'] + " " + data['message']);
            }
            MultiStepWizard.dismiss();
          }).fail(function(jqXHR, textStatus, errorThrown) {
            var parser = new DOMParser(),
              responseDocument = parser.parseFromString(jqXHR.responseText, "text/html"),
              responseBody = $(responseDocument.body);

            Notification.error(textStatus, errorThrown, 2);
            MultiStepWizard.dismiss();

            $(getDomElementIdentifier('t3Logo', 'class'), responseBody).remove();
            $(getDomElementIdentifier('t3Footer', 'id'), responseBody).remove();
            $(getDomElementIdentifier('moduleBody', 'class')).html(responseBody.html());
          });
        }).done(function() {
          MultiStepWizard.show();
        });
      });
    };

    /**
     * @private
     *
     * @return void
     */
    function _showReferencesSetup() {
      $(getDomElementIdentifier('showReferences')).on('click', function(e) {
        var that, url;

        e.preventDefault();
        that = this;
        url = _formManagerApp.getAjaxEndpoint('references') + '&tx_form_web_formformbuilder[formPersistenceIdentifier]=' + $(this).data('formPersistenceIdentifier');

        $.get(url, function(data, textStatus, jqXHR) {
          var html, modalButtons = [], referencesLength;

          modalButtons.push({
            text: TYPO3.lang['formManager.cancel'],
            active: true,
            btnClass: 'btn-default',
            name: 'cancel',
            trigger: function() {
              Modal.currentModal.trigger('modal-dismiss');
            }
          });

          referencesLength = data['references'].length;
          if (referencesLength > 0) {
            html = '<div>'
              + '<h3>' + TYPO3.lang['formManager.references.headline'].replace('{0}', $(that).data('formName')) + '</h3>'
              + '</div>'
              + '<div class="table-fit">'
              + '<table id="forms" class="table table-striped table-sm">'
              + '<thead>'
              + '<tr>'
              + '<th>' + TYPO3.lang['formManager.page'] + '</th>'
              + '<th>' + TYPO3.lang['formManager.record'] + '</th>'
              + '</tr>'
              + '</thead>'
              + '<tbody>';

            for (var i = 0, len = data['references'].length; i < len; ++i) {
              html += '<tr>'
                + '<td>' + data['references'][i]['recordPageTitle'] + '</td>'
                + '<td>'
                + data['references'][i]['recordIcon']
                + '<a href="' + data['references'][i]['recordEditUrl'] + '" data-identifier="referenceLink">'
                + data['references'][i]['recordTitle'] + ' (uid: ' + data['references'][i]['recordUid'] + ')'
                + '</a>'
                + '</td>'
                + '</tr>';
            }

            html += '</tbody>'
              + '</table>'
              + '</div>';
          } else {
            html = '<div>'
              + '<h1>' + TYPO3.lang['formManager.references.title'].replace('{0}', data['formPersistenceIdentifier']) + '</h1>'
              + '</div>'
              + '<div>' + TYPO3.lang['formManager.no_references'] + '</div>';
          }

          html = $(html);
          $(getDomElementIdentifier('referenceLink'), html).on('click', function(e) {
            e.preventDefault();
            Modal.currentModal.trigger('modal-dismiss');
            document.location = $(this).prop('href');
          });

          Modal.show(
            TYPO3.lang['formManager.references.title'],
            html,
            Severity.info,
            modalButtons
          );
        }).fail(function(jqXHR, textStatus, errorThrown) {
          if (jqXHR.status !== 0) {
            Notification.error(textStatus, errorThrown, 2);
          }
        });
      });
    };

    /**
     * @public
     *
     * @param string elementIdentifier
     * @param string type
     * @return mixed|undefined
     * @throws 1477506413
     * @throws 1477506414
     */
    function getDomElementIdentifier(elementIdentifier, type) {
      _formManagerApp.assert(elementIdentifier.length > 0, 'Invalid parameter "elementIdentifier"', 1477506413);
      _formManagerApp.assert(typeof _domElementIdentifierCache[elementIdentifier] !== "undefined", 'elementIdentifier "' + elementIdentifier + '" does not exist', 1477506414);
      if (typeof type === "undefined") {
        type = 'identifier';
      }

      return _domElementIdentifierCache[elementIdentifier][type] || undefined;
    };

    /**
     * @public
     *
     * @param object formManagerApp
     * @return void
     */
    function bootstrap(formManagerApp) {
      _formManagerApp = formManagerApp;
      _domElementIdentifierCacheSetup();
      _removeFormSetup();
      _newFormSetup();
      _duplicateFormSetup();
      _showReferencesSetup();
      $(getDomElementIdentifier('tooltip')).tooltip();
    };

    /**
     * Publish the public methods.
     * Implements the "Revealing Module Pattern".
     */
    return {
      bootstrap: bootstrap
    };
  })($, Modal, Severity, MultiStepWizard, Icons, Notification, SecurityUtility);
});
