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
  'TYPO3/CMS/Backend/Wizard',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Notification'
], function($, Modal, Severity, Wizard, Icons, Notification) {
  'use strict';

  return (function($, Modal, Severity, Wizard, Icons, Notification) {

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

        newFormName: {identifier: '[data-identifier="newFormName"]'},
        newFormSavePath: {identifier: '[data-identifier="newFormSavePath"]'},
        advancedWizard: {identifier: '[data-identifier="advancedWizard"]'},
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
        Wizard.addSlide('new-form-step-1', TYPO3.lang['formManager.newFormWizard.step1.title'], '', Severity.info, function(slide) {
          var advandecWizardHasOptions, folders, html, modal, nextButton, prototypes,
            savePathSelect, templates;

          modal = Wizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

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

          Wizard.set('savePath', folders[0]['value']);
          if (folders.length > 1) {
            savePathSelect = $('<select class="new-form-save-path form-control" id="new-form-save-path" data-identifier="newFormSavePath" />');
            for (var i = 0, len = folders.length; i < len; ++i) {
              var option = new Option(folders[i]['label'], folders[i]['value']);
              $(savePathSelect).append(option);
            }
          }

          prototypes = _formManagerApp.getPrototypes();

          _formManagerApp.assert(prototypes.length > 0, 'No prototypes available', 1477506501);
          Wizard.set('prototypeName', prototypes[0]['value']);

          templates = _formManagerApp.getTemplatesForPrototype(prototypes[0]['value']);
          _formManagerApp.assert(templates.length > 0, 'No templates available', 1477506502);
          Wizard.set('templatePath', templates[0]['value']);

          html = '<div class="new-form-modal">'
            + '<div class="form-horizontal">'
            + '<div>'
            + '<label class="control-label" for="new-form-name">' + TYPO3.lang['formManager.form_name'] + '</label>'
            + '<input class="new-form-name form-control has-error" id="new-form-name" data-identifier="newFormName" />';

          if (savePathSelect) {
            html += '<label class="control-label" for="new-form-save-path">' + TYPO3.lang['formManager.form_save_path'] + '</label>' + $(savePathSelect)[0].outerHTML;
          }

          if (prototypes.length > 1 || templates.length > 1) {
            html += '<label class="control-label" for="new-form-advance-wizard">' + TYPO3.lang['formManager.newFormWizard.step1.advanced'] + '</label>'
              + '<div class="t3-form-controls"><input type="checkbox" class="new-form-advance-wizard" id="new-form-advance-wizard" data-identifier="advancedWizard" /></div>';
          }

          html += '</div>'
            + '</div>'
            + '</div>';

          slide.html(html);
          $(getDomElementIdentifier('newFormName'), modal).focus();

          $(getDomElementIdentifier('newFormName'), modal).on('keyup paste', function(e) {
            if ($(this).val().length > 0) {
              $(this).removeClass('has-error');
              Wizard.unlockNextStep();
              Wizard.set('formName', $(this).val());
            } else {
              $(this).addClass('has-error');
              Wizard.lockNextStep();
            }
          });

          $(getDomElementIdentifier('newFormSavePath'), modal).on('change', function(e) {
            Wizard.set('savePath', $(getDomElementIdentifier('newFormSavePath') + ' option:selected', modal).val());
          });

          $(getDomElementIdentifier('advancedWizard'), modal).on('change', function(e) {
            if ($(this).is(':checked')) {
              Wizard.set('advancedWizard', true);
            } else {
              Wizard.set('advancedWizard', false);
            }
          });

          nextButton.on('click', function() {
            Wizard.setup.forceSelection = false;
            Icons.getIcon('spinner-circle-dark', Icons.sizes.large, null, null).done(function(markup) {
              slide.html($('<div />', {class: 'text-center'}).append(markup));
            });
          });
        });

        /**
         * Wizard step 2
         */
        Wizard.addSlide('new-form-step-2', TYPO3.lang['formManager.newFormWizard.step2.title'], '', Severity.info, function(slide, settings) {
          var addOnTemplateChangeEvents, html, modal, nextButton, prototypes, prototypeNameSelect,
            templates, templateSelect;

          if (settings['advancedWizard'] !== true) {
            Wizard.unlockNextStep().trigger('click');
            return;
          }

          modal = Wizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          prototypeNameSelect = $('<select class="new-form-prototype-name form-control" id="new-form-prototype-name" data-identifier="newFormPrototypeName" />');
          templateSelect = $('<select class="new-form-template form-control" id="new-form-template" data-identifier="newFormTemplate" />');

          prototypes = _formManagerApp.getPrototypes();
          templates = {};
          if (prototypes.length > 0) {
            for (var i = 0, len = prototypes.length; i < len; ++i) {
              var option = new Option(prototypes[i]['label'], prototypes[i]['value']);
              $(prototypeNameSelect).append(option);
            }

            templates = _formManagerApp.getTemplatesForPrototype(prototypes[0]['value']);
            for (var i = 0, len = templates.length; i < len; ++i) {
              var option = new Option(templates[i]['label'], templates[i]['value']);
              $(templateSelect).append(option);
            }
          }

          html = '<div class="new-form-modal">'
            + '<div class="form-horizontal">'
            + '<div>';

          if (prototypes.length > 1) {
            html += '<label class="control-label" for="new-form-prototype-name">' + TYPO3.lang['formManager.form_prototype'] + '</label>' + $(prototypeNameSelect)[0].outerHTML;
          }
          if (templates.length > 1) {
            html += '<label class="control-label" for="new-form-template">' + TYPO3.lang['formManager.form_template'] + '</label>' + $(templateSelect)[0].outerHTML;
          }

          html += '</div>'
            + '</div>'
            + '</div>';

          slide.html(html);
          if (prototypes.length > 1) {
            $(getDomElementIdentifier('newFormPrototypeName'), modal).focus();
          } else if (templates.length > 1) {
            $(getDomElementIdentifier('newFormTemplate'), modal).focus();
          }

          addOnTemplateChangeEvents = function() {
            $(getDomElementIdentifier('newFormTemplate'), modal).on('change', function(e) {
              Wizard.set('templatePath', $(getDomElementIdentifier('newFormTemplate') + ' option:selected', modal).val());
            });
          };

          $(getDomElementIdentifier('newFormPrototypeName'), modal).on('change', function(e) {
            Wizard.set('prototypeName', $(this).val());
            templates = _formManagerApp.getTemplatesForPrototype($(this).val());
            $(getDomElementIdentifier('newFormTemplate'), modal).off().empty();
            for (var i = 0, len = templates.length; i < len; ++i) {
              var option = new Option(templates[i]['label'], templates[i]['value']);
              $(getDomElementIdentifier('newFormTemplate'), modal).append(option);
              Wizard.set('templatePath', templates[0]['value']);
            }
            addOnTemplateChangeEvents();
          });

          addOnTemplateChangeEvents();

          nextButton.on('click', function() {
            Icons.getIcon('spinner-circle-dark', Icons.sizes.large, null, null).done(function(markup) {
              slide.html($('<div />', {class: 'text-center'}).append(markup));
            });
          });
        });

        /**
         * Wizard step 3
         */
        Wizard.addSlide('new-form-step-3', TYPO3.lang['formManager.newFormWizard.step3.title'], TYPO3.lang['formManager.newFormWizard.step3.message'], Severity.info);

        /**
         * Wizard step 4
         */
        Wizard.addFinalProcessingSlide(function() {
          $.post(_formManagerApp.getAjaxEndpoint('create'), {
            tx_form_web_formformbuilder: {
              formName: Wizard.setup.settings['formName'],
              templatePath: Wizard.setup.settings['templatePath'],
              prototypeName: Wizard.setup.settings['prototypeName'],
              savePath: Wizard.setup.settings['savePath']
            }
          }, function(data, textStatus, jqXHR) {
            if (data['status'] === 'success') {
              document.location = data.url;
            } else {
              Notification.error(TYPO3.lang['formManager.newFormWizard.step4.errorTitle'], TYPO3.lang['formManager.newFormWizard.step4.errorMessage'] + " " + data['message']);
            }
            Wizard.dismiss();
          }).fail(function(jqXHR, textStatus, errorThrown) {
            var parser = new DOMParser(),
              responseDocument = parser.parseFromString(jqXHR.responseText, "text/html"),
              responseBody = $(responseDocument.body);

            Notification.error(textStatus, errorThrown, 2);
            Wizard.dismiss();

            $(getDomElementIdentifier('t3Logo', 'class'), responseBody).remove();
            $(getDomElementIdentifier('t3Footer', 'id'), responseBody).remove();
            $(getDomElementIdentifier('moduleBody', 'class')).html(responseBody.html());
          });
        }).done(function() {
          Wizard.show();
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
        that = $(this)

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
        Wizard.addSlide('duplicate-form-step-1', TYPO3.lang['formManager.duplicateFormWizard.step1.title'].replace('{0}', that.data('formName')), '', Severity.info, function(slide) {
          var folders, html, modal, nextButton, savePathSelect;

          modal = Wizard.setup.$carousel.closest('.modal');
          nextButton = modal.find('.modal-footer').find('button[name="next"]');

          folders = _formManagerApp.getAccessibleFormStorageFolders();
          _formManagerApp.assert(folders.length > 0, 'No accessible form storage folders', 1477649539);

          Wizard.set('formPersistenceIdentifier', that.data('formPersistenceIdentifier'));
          Wizard.set('savePath', folders[0]['value']);
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
              Wizard.unlockNextStep();
              Wizard.set('formName', $(this).val());
            } else {
              $(this).addClass('has-error');
              Wizard.lockNextStep();
            }
          });

          $(getDomElementIdentifier('duplicateFormSavePath'), modal).on('change', function(e) {
            Wizard.set('savePath', $(getDomElementIdentifier('duplicateFormSavePath') + ' option:selected', modal).val());
          });
        });

        /**
         * Wizard step 2
         */
        Wizard.addFinalProcessingSlide(function() {
          $.post(_formManagerApp.getAjaxEndpoint('duplicate'), {
            tx_form_web_formformbuilder: {
              formName: Wizard.setup.settings['formName'],
              formPersistenceIdentifier: Wizard.setup.settings['formPersistenceIdentifier'],
              savePath: Wizard.setup.settings['savePath']
            }
          }, function(data, textStatus, jqXHR) {
            if (data['status'] === 'success') {
              document.location = data.url;
            } else {
              Notification.error(TYPO3.lang['formManager.duplicateFormWizard.step2.errorTitle'], TYPO3.lang['formManager.duplicateFormWizard.step2.errorMessage'] + " " + data['message']);
            }
            Wizard.dismiss();
          }).fail(function(jqXHR, textStatus, errorThrown) {
            var parser = new DOMParser(),
              responseDocument = parser.parseFromString(jqXHR.responseText, "text/html"),
              responseBody = $(responseDocument.body);

            Notification.error(textStatus, errorThrown, 2);
            Wizard.dismiss();

            $(getDomElementIdentifier('t3Logo', 'class'), responseBody).remove();
            $(getDomElementIdentifier('t3Footer', 'id'), responseBody).remove();
            $(getDomElementIdentifier('moduleBody', 'class')).html(responseBody.html());
          });
        }).done(function() {
          Wizard.show();
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
  })($, Modal, Severity, Wizard, Icons, Notification);
});
