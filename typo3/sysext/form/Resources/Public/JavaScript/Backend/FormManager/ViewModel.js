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

        newFormMode: {identifier: '[data-identifier="newFormMode"]', button: '[data-identifier="newFormModeButton"]'},
        newFormName: {identifier: '[data-identifier="newFormName"]'},
        newFormSavePath: {identifier: '[data-identifier="newFormSavePath"]'},
        newFormPrototypeName: {identifier: '[data-identifier="newFormPrototypeName"]'},
        newFormTemplate: {identifier: '[data-identifier="newFormTemplate"]'},

        duplicateFormName: {identifier: '[data-identifier="duplicateFormName"]'},
        duplicateFormSavePath: {identifier: '[data-identifier="duplicateFormSavePath"]'},

        showReferences: {identifier: '[data-identifier="showReferences"]'},
        referenceLink: {identifier: '[data-identifier="referenceLink"]'},

        tooltip: {identifier: '[data-toggle="tooltip"]'},

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
        MultiStepWizard.addSlide('new-form-step-1', TYPO3.lang['formManager.newFormWizard.step1.title'], '', Severity.info, null, function(slide) {
          Icons.getIcon('actions-document-duplicates-select', Icons.sizes.large).then(function (duplicateIconMarkup) {
            Icons.getIcon('actions-document-new', Icons.sizes.large).then(function (blankIconMarkup) {
              var advancedWizardHasOptions, folders, html, modal, cancelButton, nextButton, prototypes,
                templates;

              modal = MultiStepWizard.setup.$carousel.closest('.modal');
              nextButton = modal.find('.modal-footer').find('button[name="next"]');

              MultiStepWizard.blurCancelStep();
              MultiStepWizard.lockNextStep();
              MultiStepWizard.lockPrevStep();

              folders = _formManagerApp.getAccessibleFormStorageFolders();
              if (folders.length === 0) {
                html = '<div class="new-form-modal">'
                  + '<div class="form-horizontal">'
                  + '<div>'
                  + '<label class="control-label">' + TYPO3.lang['formManager.newFormWizard.step1.noStorages'] + '</label>'
                  + '</div>'
                  + '</div>'
                  + '</div>';

                slide.html(html);
                _formManagerApp.assert(false, 'No accessible form storage folders', 1477506500);
              }

              prototypes = _formManagerApp.getPrototypes();

              _formManagerApp.assert(prototypes.length > 0, 'No prototypes available', 1477506501);
              MultiStepWizard.set('prototypeName', prototypes[0]['value']);

              templates = _formManagerApp.getTemplatesForPrototype(prototypes[0]['value']);
              _formManagerApp.assert(templates.length > 0, 'No templates available', 1477506502);
              MultiStepWizard.set('templatePath', templates[0]['value']);

              html = '<div class="new-form-modal">'
                + '<div class="form-horizontal">'
                + '<div>';

              html += '<div class="row">'
                + '<div class="col-sm-6">'
                + '<p>'
                + '<label class="label-block">'
                + '<button class="btn btn-block btn-default btn-block btn-createform" data-identifier="newFormModeButton" type="button">'
                + blankIconMarkup
                + '<input type="radio" name="newformmode" id="mode_blank" value="blank" data-identifier="newFormMode" style="display: none">'
                + '<br>' + TYPO3.lang['formManager.blankForm.label']
                + '</button>'
                + '</label>'
                + '</p>'
                + '</div>'
                + '<div class="col-sm-6">'
                + '<p>'
                + '<label class="label-block">'
                + '<button class="btn btn-block btn-default btn-block btn-createform" data-identifier="newFormModeButton" type="button">'
                + duplicateIconMarkup
                + '<input type="radio" name="newformmode" id="mode_predefined" value="predefined" data-identifier="newFormMode" style="display: none">'
                + '<br>' + TYPO3.lang['formManager.predefinedForm.label']
                + '</button>'
                + '</label>'
                + '</p>'
                + '</div>'
                + '</div>';

              html += '</div>'
                + '</div>'
                + '</div>';

              slide.html(html);

              $(getDomElementIdentifier('newFormMode'), modal).on('change', function (e) {
                if ($(this).is(':checked')) {
                  MultiStepWizard.set('newFormMode', $(this).val());
                  MultiStepWizard.unlockNextStep().trigger('click');
                }
              });

              $(getDomElementIdentifier('newFormMode', 'button'), modal).on('click', function (e) {
                $(getDomElementIdentifier('newFormMode'), $(this)).prop('checked', true).trigger('change');
              });

              $(getDomElementIdentifier('newFormMode', 'button'), modal).first().focus();

              nextButton.on('click', function() {
                Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
                  slide.html($('<div />', {class: 'text-center'}).append(markup));
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
            savePathSelect = $('<select class="new-form-save-path form-control" id="new-form-save-path" data-identifier="newFormSavePath" />');
            for (var i = 0, len = folders.length; i < len; ++i) {
              var option = new Option(folders[i]['label'], folders[i]['value']);
              $(savePathSelect).append(option);
            }
          }

          prototypeNameSelect = $('<select class="new-form-prototype-name form-control" id="new-form-prototype-name" data-identifier="newFormPrototypeName" />');
          templateSelect = $('<select class="new-form-template form-control" id="new-form-template" data-identifier="newFormTemplate" />');

          prototypes = _formManagerApp.getPrototypes();
          templates = {};
          if (prototypes.length > 0) {
            if (!settings['prototypeName']) {
              MultiStepWizard.set('prototypeName', prototypes[0]['value']);
              MultiStepWizard.set('prototypeNameName', prototypes[0]['label']);
            }

            for (var i = 0, len = prototypes.length; i < len; ++i) {
              var option = new Option(prototypes[i]['label'], prototypes[i]['value']);
              $(prototypeNameSelect).append(option);
            }

            templates = _formManagerApp.getTemplatesForPrototype(prototypes[0]['value']);

            if (!settings['templatePath']) {
              MultiStepWizard.set('templatePath', templates[0]['value']);
              MultiStepWizard.set('templatePathName', templates[0]['label']);
            }

            for (var i = 0, len = templates.length; i < len; ++i) {
              var option = new Option(templates[i]['label'], templates[i]['value']);
              $(templateSelect).append(option);
            }
          }

          html = '<div class="new-form-modal">'
            + '<div class="form-horizontal">'
            + '<div>';

          if (settings['newFormMode'] !== 'blank') {
            if (prototypes.length > 1) {
              html += '<label class="control-label" for="new-form-prototype-name">' + TYPO3.lang['formManager.form_prototype'] + '</label>' + $(prototypeNameSelect)[0].outerHTML;
            }
          }

          if (settings['newFormMode'] === 'blank') {
            MultiStepWizard.set('templatePath', 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml');
            MultiStepWizard.set('templatePathName', TYPO3.lang['formManager.blankForm.label']);
          } else {
            if (templates.length > 1) {
              html += '<label class="control-label" for="new-form-template">' + TYPO3.lang['formManager.form_template'] + '</label>' + $(templateSelect)[0].outerHTML;
            }
          }

          html += '<label class="control-label" for="new-form-name">' + TYPO3.lang['formManager.form_name'] + '</label>';
          if (settings['formName']) {
            html += '<input class="new-form-name form-control has-error" id="new-form-name" data-identifier="newFormName" value="' + securityUtility.encodeHtml(settings['formName']) + '" />';

            setTimeout(
              function() {
                MultiStepWizard.unlockNextStep();
              }, 200);
          } else {
            html += '<input class="new-form-name form-control has-error" id="new-form-name" data-identifier="newFormName" />';
          }

          if (savePathSelect) {
            html += '<label class="control-label" for="new-form-save-path">' + TYPO3.lang['formManager.form_save_path'] + '</label>' + $(savePathSelect)[0].outerHTML;
          }

          html += '</div>'
            + '</div>'
            + '</div>';

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
            $(getDomElementIdentifier('newFormPrototypeName'), modal).change();

            if (settings['templatePathOnPrev']) {
              $(getDomElementIdentifier('newFormTemplate'), modal).find('option[value="' + settings['templatePathOnPrev'] + '"]').prop('selected', true);
              $(getDomElementIdentifier('newFormTemplate'), modal).change();
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
        MultiStepWizard.addSlide('new-form-step-3', TYPO3.lang['formManager.newFormWizard.step3.title'], '', Severity.info, null, function(slide, settings) {
          var addOnTemplateChangeEvents, folders, html, modal, nextButton, prototypes, prototypeNameSelect,
            savePathSelect, templates, templateSelect;

          modal = MultiStepWizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          html = '<div class="new-form-modal">'
            + '<div class="form-horizontal">'
            + '<div>';

          html += '<h3 class="modal-title">' + TYPO3.lang['formManager.newFormWizard.step3.check'] + '</h3>'
            + '<hr />';

          if (settings['prototypeNameName']) {
            html += '<div class="row">'
              + '<div class="col-sm-3">'
              + '<strong>' + TYPO3.lang['formManager.form_prototype'] + ':</strong>'
              + '</div>'
              + '<div class="col-sm-9">'
              + '<p>'
              + securityUtility.encodeHtml(settings['prototypeNameName'])
              + '</p>'
              + '</div>'
              + '</div>';
          }

          if (settings['templatePathName']) {
            html += '<div class="row">'
              + '<div class="col-sm-3">'
              + '<strong>' + TYPO3.lang['formManager.form_template'] + ':</strong>'
              + '</div>'
              + '<div class="col-sm-9">'
              + '<p>'
              + securityUtility.encodeHtml(settings['templatePathName'])
              + '</p>'
              + '</div>'
              + '</div>';
          }

          html += '<div class="row">'
            + '<div class="col-sm-3">'
            + '<strong>' + TYPO3.lang['formManager.form_name'] + ':</strong>'
            + '</div>'
            + '<div class="col-sm-9">'
            + '<p>'
            + securityUtility.encodeHtml(settings['formName'])
            + '</p>'
            + '</div>'
            + '</div>'
            + '<div class="row">'
            + '<div class="col-sm-3">'
            + '<strong>' + TYPO3.lang['formManager.form_save_path'] + ':</strong>'
            + '</div>'
            + '<div class="col-sm-9">'
            + '<p>'
            + securityUtility.encodeHtml(settings['savePathName'])
            + '</p>'
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
        MultiStepWizard.addSlide('duplicate-form-step-1', TYPO3.lang['formManager.duplicateFormWizard.step1.title'].replace('{0}', that.data('formName')), '', Severity.info, null, function(slide, settings) {
          var addOnTemplateChangeEvents, folders, html, modal, nextButton, prototypes, prototypeNameSelect,
            savePathSelect, templates, templateSelect;

          modal = MultiStepWizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          nextButton.trigger('click');

          html = '<div class="new-form-modal">'
            + '<div class="form-horizontal">'
            + '<div>';

          html += '</div>'
            + '</div>'
            + '</div>';

          slide.html(html);

          nextButton.on('click', function() {
            Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
              slide.html($('<div />', {class: 'text-center'}).append(markup));
            });
          });
        });

        /**
         * Wizard step 2
         */
        MultiStepWizard.addSlide('duplicate-form-step-2', TYPO3.lang['formManager.duplicateFormWizard.step1.title'].replace('{0}', that.data('formName')), '', Severity.info, top.TYPO3.lang['wizard.progressStep.configure'], function(slide, settings) {
          var addOnTemplateChangeEvents, folders, html, modal, nextButton, prototypes, prototypeNameSelect,
            savePathSelect, templates, templateSelect;

          modal = MultiStepWizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          folders = _formManagerApp.getAccessibleFormStorageFolders();
          _formManagerApp.assert(folders.length > 0, 'No accessible form storage folders', 1477649539);

          MultiStepWizard.set('formPersistenceIdentifier', that.data('formPersistenceIdentifier'));
          MultiStepWizard.set('savePath', folders[0]['value']);
          if (folders.length > 1) {
            savePathSelect = $('<select class="duplicate-form-save-path form-control" data-identifier="duplicateFormSavePath" />');
            for (var i = 0, len = folders.length; i < len; ++i) {
              var option = new Option(folders[i]['label'], folders[i]['value']);
              $(savePathSelect).append(option);
            }
          }

          html = '<div class="duplicate-form-modal">'
            + '<div class="form-horizontal">'
            + '<div>'
            + '<label class="control-label">' + TYPO3.lang['formManager.new_form_name'] + '</label>'
            + '<input class="duplicate-form-name form-control has-error" data-identifier="duplicateFormName" />';

          if (savePathSelect) {
            html += '<label class="control-label">' + TYPO3.lang['formManager.form_save_path'] + '</label>' + $(savePathSelect)[0].outerHTML;
          }

          html += '</div>'
            + '</div>'
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
         * Wizard step 3
         */
        MultiStepWizard.addSlide('duplicate-form-step-3', TYPO3.lang['formManager.newFormWizard.step3.title'], '', Severity.info, null, function(slide, settings) {
          var html, modal, nextButton;

          modal = MultiStepWizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          html = '<div class="new-form-modal">'
            + '<div class="form-horizontal">'
            + '<div>';

          html += '<h3 class="modal-title">' + TYPO3.lang['formManager.newFormWizard.step3.check'] + '</h3>'
            + '<hr />'
            + '<div class="row">'
            + '<div class="col-sm-3">'
            + '<strong>' + TYPO3.lang['formManager.form_copied'] + ':</strong>'
            + '</div>'
            + '<div class="col-sm-9">'
            + '<p>'
            + securityUtility.encodeHtml(settings['confirmationDuplicateFormName'])
            + '</p>'
            + '</div>'
            + '</div>'
            + '<div class="row">'
            + '<div class="col-sm-3">'
            + '<strong>' + TYPO3.lang['formManager.form_name'] + ':</strong>'
            + '</div>'
            + '<div class="col-sm-9">'
            + '<p>'
            + securityUtility.encodeHtml(settings['formName'])
            + '</p>'
            + '</div>'
            + '</div>'
            + '<div class="row">'
            + '<div class="col-sm-3">'
            + '<strong>' + TYPO3.lang['formManager.form_save_path'] + ':</strong>'
            + '</div>'
            + '<div class="col-sm-9">'
            + '<p>'
            + securityUtility.encodeHtml(settings['confirmationDuplicateFormSavePath'])
            + '</p>'
            + '</div>'
            + '</div>';

          html += '</div>'
            + '</div>'
            + '</div>';

          slide.html(html);

          nextButton.focus();

          nextButton.on('click', function(e) {
            Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
              slide.html($('<div />', {class: 'text-center'}).append(markup));
            });
          });
        });

        /**
         * Wizard step 4
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
              Notification.error(TYPO3.lang['formManager.duplicateFormWizard.step2.errorTitle'], TYPO3.lang['formManager.duplicateFormWizard.step2.errorMessage'] + " " + data['message']);
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
              + '<table id="forms" class="table table-striped table-condensed">'
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
