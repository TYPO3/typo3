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
 * Module: @typo3/form/backend/form-manager/view-model
 */
import $ from 'jquery';
import Modal, { ModalElement } from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import MultiStepWizard from '@typo3/backend/multi-step-wizard';
import Icons from '@typo3/backend/icons';
import Notification from '@typo3/backend/notification';
import SecurityUtility from '@typo3/core/security-utility';
import type { FormManager } from '@typo3/form/backend/form-manager';

const securityUtility = new SecurityUtility();

enum Identifiers {
  newFormModalTrigger = '[data-identifier="newForm"]',
  duplicateFormModalTrigger = '[data-identifier="duplicateForm"]',
  removeFormModalTrigger = '[data-identifier="removeForm"]',

  newFormModeButton = '[data-identifier="newFormModeButton"]',
  newFormName = '[data-identifier="newFormName"]',
  newFormSavePath = '[data-identifier="newFormSavePath"]',
  newFormPrototypeName = '[data-identifier="newFormPrototypeName"]',
  newFormTemplate = '[data-identifier="newFormTemplate"]',

  duplicateFormName = '[data-identifier="duplicateFormName"]',
  duplicateFormSavePath = '[data-identifier="duplicateFormSavePath"]',

  showReferences = '[data-identifier="showReferences"]',
  referenceLink = '[data-identifier="referenceLink"]',

  moduleBody = '.module-body.t3js-module-body',
  t3Logo = '.t3-message-page-logo',

  t3Footer = '#t3-footer'
}

/**
 * @throws 1477506500
 * @throws 1477506501
 * @throws 1477506502
 */
function newFormSetup(formManagerApp: FormManager): void {
  $(Identifiers.newFormModalTrigger).on('click', function(e) {
    e.preventDefault();

    /**
     * Wizard step 1
     */
    MultiStepWizard.addSlide('new-form-step-1', TYPO3.lang['formManager.newFormWizard.step1.title'], '', Severity.info, TYPO3.lang['formManager.newFormWizard.step1.progressLabel'], function(slide) {
      Icons.getIcon('actions-plus', Icons.sizes.small).then(function (addIconMarkup) {
        Icons.getIcon('form-page', Icons.sizes.large).then(function (duplicateIconMarkup) {
          Icons.getIcon('apps-pagetree-page-default', Icons.sizes.large).then(function (blankIconMarkup) {
            let html;
            const modal = MultiStepWizard.setup.$carousel.closest('.modal');
            const nextButton = modal.find('.modal-footer').find('button[name="next"]');

            MultiStepWizard.blurCancelStep();
            MultiStepWizard.lockNextStep();
            MultiStepWizard.lockPrevStep();

            const folders = formManagerApp.getAccessibleFormStorageFolders();
            if (folders.length === 0) {
              html = '<div class="new-form-modal">'
                + '<div class="row">'
                + '<label class="col col-form-label">' + TYPO3.lang['formManager.newFormWizard.step1.noStorages'] + '</label>'
                + '</div>'
                + '</div>';

              slide.html(html);
              formManagerApp.assert(false, 'No accessible form storage folders', 1477506500);
            }

            html = '<div class="new-form-modal">'

            html += '<div class="card-container">'
              + '<div class="card card-size-medium">'
              + '<div class="card-header">'
              + '<div class="card-icon">' + blankIconMarkup + '</div>'
              + '<div class="card-header-body">'
              + '<h2 class="card-title">' + TYPO3.lang['formManager.blankForm.label'] + '</h2>'
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
              + '<h2 class="card-title">' + TYPO3.lang['formManager.predefinedForm.label'] + '</h2>'
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

            $(Identifiers.newFormModeButton, modal).on('click', function (e: Event) {
              MultiStepWizard.set('newFormMode', $(e.currentTarget).val());
              MultiStepWizard.unlockNextStep().trigger('click');
            });

            nextButton.on('click', function() {
              Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
                slide.html($('<div />', { class: 'text-center' }).append(markup).prop('outerHTML'));
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
      let html, savePathSelect;

      MultiStepWizard.lockNextStep();
      MultiStepWizard.unlockPrevStep();

      const modal = MultiStepWizard.setup.$carousel.closest('.modal');
      const nextButton = modal.find('.modal-footer').find('button[name="next"]');

      const folders = formManagerApp.getAccessibleFormStorageFolders();

      if (!settings.savePath) {
        MultiStepWizard.set('savePath', folders[0].value);
        MultiStepWizard.set('savePathName', folders[0].label);
      }

      if (folders.length > 1) {
        savePathSelect = $('<select class="new-form-save-path form-select" id="new-form-save-path" data-identifier="newFormSavePath" />');
        for (let i = 0, len = folders.length; i < len; ++i) {
          const option = new Option(folders[i].label, folders[i].value);
          $(savePathSelect).append(option);
        }
      }

      const prototypes = formManagerApp.getPrototypes();
      formManagerApp.assert(prototypes.length > 0, 'No prototypes available', 1477506501);

      if (!settings.prototypeName) {
        MultiStepWizard.set('prototypeName', prototypes[0].value);
        MultiStepWizard.set('prototypeNameName', prototypes[0].label);
      }

      const prototypeNameSelect = $('<select class="new-form-prototype-name form-select" id="new-form-prototype-name" data-identifier="newFormPrototypeName" />');
      for (let i = 0, len = prototypes.length; i < len; ++i) {
        const option = new Option(prototypes[i].label, prototypes[i].value);
        $(prototypeNameSelect).append(option);
      }

      let templates = formManagerApp.getTemplatesForPrototype(prototypes[0].value);
      formManagerApp.assert(templates.length > 0, 'No templates available', 1477506502);

      if (!settings.templatePath) {
        MultiStepWizard.set('templatePath', templates[0].value);
        MultiStepWizard.set('templatePathName', templates[0].label);
      }

      const templateSelect = $('<select class="new-form-template form-select" id="new-form-template" data-identifier="newFormTemplate" />');
      for (let i = 0, len = templates.length; i < len; ++i) {
        const option = new Option(templates[i].label, templates[i].value);
        $(templateSelect).append(option);
      }

      html = '<div class="new-form-modal">';

      if (settings.newFormMode === 'blank') {
        html += '<h5 class="form-section-headline">' + TYPO3.lang['formManager.blankForm.label'] + '</h5>';
        MultiStepWizard.set('templatePath', 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml');
        MultiStepWizard.set('templatePathName', TYPO3.lang['formManager.blankForm.label']);
      } else {
        html += '<h5 class="form-section-headline">' + TYPO3.lang['formManager.predefinedForm.label'] + '</h5>'
        if (prototypes.length > 1) {
          html += '<div class="mb-3">'
            + '<label for="new-form-prototype-name">' + '<strong>' + TYPO3.lang['formManager.form_prototype'] + '</strong>' + '</label>'
            + '<div class="formengine-field-item t3js-formengine-field-item">'
            + '<div class="form-control-wrap">' + $(prototypeNameSelect)[0].outerHTML + '</div>'
            + '</div>'
            + '</div>';
        }

        if (templates.length > 1) {
          html += '<div class="mb-3">'
            + '<label for="new-form-template">' + '<strong>' + TYPO3.lang['formManager.form_template'] + '</strong>' + '</label>'
            + '<div class="formengine-field-item t3js-formengine-field-item">'
            + '<div class="form-description">' + TYPO3.lang['formManager.form_template_description'] + '</div>'
            + '<div class="form-control-wrap">' + $(templateSelect)[0].outerHTML + '</div>'
            + '</div>'
            + '</div>';
        }
      }

      html += '<div class="mb-3">'
        + '<label for="new-form-name">' + '<strong>' + TYPO3.lang['formManager.form_name'] + '</strong>' + '</label>'
        + '<div class="formengine-field-item t3js-formengine-field-item">'
        + '<div class="form-description">' + TYPO3.lang['formManager.form_name_description'] + '</div>'
        + '<div class="form-control-wrap">';

      if (settings.formName) {
        html += '<input class="form-control" id="new-form-name" data-identifier="newFormName" value="' + securityUtility.encodeHtml(settings.formName) + '" />';

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
        html += '<div class="mb-3">'
          + '<label for="new-form-save-path">' + '<strong>' + TYPO3.lang['formManager.form_save_path'] + '</strong>' + '</label>'
          + '<div class="formengine-field-item t3js-formengine-field-item">'
          + '<div class="form-description">' + TYPO3.lang['formManager.form_save_path_description'] + '</div>'
          + '<div class="form-control-wrap">' + $(savePathSelect)[0].outerHTML + '</div>'
          + '</div>'
          + '</div>';
      }

      html += '</div>';

      slide.html(html);

      if (settings.savePath) {
        $(Identifiers.newFormSavePath, modal).val(settings.savePath);
      }

      if (settings.templatePath) {
        $(Identifiers.newFormTemplate, modal).val(settings.templatePath);
      }

      if (prototypes.length > 1) {
        $(Identifiers.newFormPrototypeName, modal).focus();
      } else if (templates.length > 1) {
        $(Identifiers.newFormTemplate, modal).focus();
      }

      const addOnTemplateChangeEvents = function() {
        $(Identifiers.newFormTemplate, modal).on('change', function() {
          MultiStepWizard.set('templatePath', $(Identifiers.newFormTemplate + ' option:selected', modal).val());
          MultiStepWizard.set('templatePathName', $(Identifiers.newFormTemplate + ' option:selected', modal).text());
          MultiStepWizard.set('templatePathOnPrev', $(Identifiers.newFormTemplate + ' option:selected', modal).val());
        });
      };

      $(Identifiers.newFormPrototypeName, modal).on('change', function(e: Event) {
        MultiStepWizard.set('prototypeName', $(Identifiers.newFormPrototypeName + ' option:selected', modal).val());
        MultiStepWizard.set('prototypeNameName', $(Identifiers.newFormPrototypeName + ' option:selected', modal).text());

        templates = formManagerApp.getTemplatesForPrototype($(e.currentTarget).val());
        $(Identifiers.newFormTemplate, modal).off().empty();
        for (let i = 0, len = templates.length; i < len; ++i) {
          const option = new Option(templates[i].label, templates[i].value);
          $(Identifiers.newFormTemplate, modal).append(option);
          MultiStepWizard.set('templatePath', templates[0].value);
          MultiStepWizard.set('templatePathName', templates[0].label);
        }
        addOnTemplateChangeEvents();
      });

      addOnTemplateChangeEvents();

      if (settings.prototypeName) {
        $(Identifiers.newFormPrototypeName, modal).val(settings.prototypeName);
        $(Identifiers.newFormPrototypeName, modal).trigger('change');

        if (settings.templatePathOnPrev) {
          $(Identifiers.newFormTemplate, modal).find('option[value="' + settings.templatePathOnPrev + '"]').prop('selected', true);
          $(Identifiers.newFormTemplate, modal).trigger('change');
        }
      }

      $(Identifiers.newFormName, modal).focus();

      $(Identifiers.newFormName, modal).on('keyup paste', function(e: JQueryEventObject) {
        if ($(e.currentTarget).val().length > 0) {
          $(e.currentTarget).removeClass('has-error');
          MultiStepWizard.unlockNextStep();
          MultiStepWizard.set('formName', $(e.currentTarget).val());
          if ('code' in e && e.code === 'Enter') {
            MultiStepWizard.triggerStepButton('next');
          }
        } else {
          $(e.currentTarget).addClass('has-error');
          MultiStepWizard.lockNextStep();
        }
      });

      $(Identifiers.newFormSavePath, modal).on('change', function() {
        MultiStepWizard.set('savePath', $(Identifiers.newFormSavePath + ' option:selected', modal).val());
        MultiStepWizard.set('savePathName', $(Identifiers.newFormSavePath + ' option:selected', modal).text());
      });

      if (settings.newFormMode !== 'blank' && !settings.templatePathName) {
        MultiStepWizard.set('templatePathName', $(Identifiers.newFormTemplate + ' option:selected', modal).text());
      }

      nextButton.on('click', function() {
        MultiStepWizard.setup.forceSelection = false;
        Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
          slide.html($('<div />', { class: 'text-center' }).append(markup).prop('outerHTML'));
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
              const modal = MultiStepWizard.setup.$carousel.closest('.modal');
              const nextButton = modal.find('.modal-footer').find('button[name="next"]');

              let html = '<div class="new-form-modal">';

              html += '<div class="mb-3">'
                + '<h5 class="form-section-headline">' + TYPO3.lang['formManager.newFormWizard.step3.check'] + '</h5>'
                + '<p>' + TYPO3.lang['formManager.newFormWizard.step3.message'] + '</p>'
                + '</div>'
                + '<div class="alert alert-notice">'
                + '<div class="alert-body mt-1">'

              if (settings.prototypeNameName) {
                html += '<div class="row my-1">'
                  + '<div class="col col-sm-6">'
                  + formPrototypeIconMarkup + ' '
                  + TYPO3.lang['formManager.form_prototype']
                  + '</div>'
                  + '<div class="col">'
                  + securityUtility.encodeHtml(settings.prototypeNameName)
                  + '</div>'
                  + '</div>';
              }

              if (settings.templatePathName) {
                html += '<div class="row my-1">'
                  + '<div class="col col-sm-6">'
                  + formTemplateIconMarkup + ' '
                  + TYPO3.lang['formManager.form_template']
                  + '</div>'
                  + '<div class="col">'
                  + securityUtility.encodeHtml(settings.templatePathName)
                  + '</div>'
                  + '</div>';
              }

              html += '<div class="row my-1">'
                + '<div class="col col-sm-6">'
                + formNameIconMarkup + ' '
                + TYPO3.lang['formManager.form_name']
                + '</div>'
                + '<div class="col">'
                + securityUtility.encodeHtml(settings.formName)
                + '</div>'
                + '</div>'
                + '<div class="row my-1">'
                + '<div class="col col-sm-6">'
                + formStorageMarkup + ' '
                + TYPO3.lang['formManager.form_save_path']
                + '</div>'
                + '<div class="col">'
                + securityUtility.encodeHtml(settings.savePathName)
                + '</div>'
                + '</div>';

              html += '</div>'
                + '</div>'
                + '</div>';

              slide.html(html);

              nextButton.focus();

              nextButton.on('click', function() {
                MultiStepWizard.setup.forceSelection = false;
                Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
                  slide.html($('<div />', { class: 'text-center' }).append(markup).prop('outerHTML'));
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
      $.post(formManagerApp.getAjaxEndpoint('create'), {
        formName: MultiStepWizard.setup.settings.formName,
        templatePath: MultiStepWizard.setup.settings.templatePath,
        prototypeName: MultiStepWizard.setup.settings.prototypeName,
        savePath: MultiStepWizard.setup.settings.savePath
      }, function(data) {
        if (data.status === 'success') {
          document.location = data.url;
        } else {
          Notification.error(TYPO3.lang['formManager.newFormWizard.step4.errorTitle'], TYPO3.lang['formManager.newFormWizard.step4.errorMessage'] + ' ' + data.message);
        }
        MultiStepWizard.dismiss();
      }).fail(function(jqXHR, textStatus, errorThrown) {
        const parser = new DOMParser(),
          responseDocument = parser.parseFromString(jqXHR.responseText, 'text/html'),
          responseBody = $(responseDocument.body);

        Notification.error(textStatus, errorThrown, 2);
        MultiStepWizard.dismiss();

        $(Identifiers.t3Logo, responseBody).remove();
        $(Identifiers.t3Footer, responseBody).remove();
        $(Identifiers.moduleBody).html(responseBody.html());
      });
    }).then(function() {
      MultiStepWizard.show();
    });
  });
}

function removeFormSetup(formManagerApp: FormManager): void {
  $(Identifiers.removeFormModalTrigger).on('click', function(e: Event) {
    const modalButtons = [];

    e.preventDefault();
    const that = $(e.currentTarget);

    modalButtons.push({
      text: TYPO3.lang['formManager.cancel'],
      active: true,
      btnClass: 'btn-default',
      name: 'cancel',
      trigger: function(e: Event, modal: ModalElement) {
        modal.hideModal();
      }
    });

    modalButtons.push({
      text: TYPO3.lang['formManager.remove_form'],
      active: true,
      btnClass: 'btn-warning',
      name: 'createform',
      trigger: function(e: Event, modal: ModalElement) {
        document.location = formManagerApp.getAjaxEndpoint('delete') + '&formPersistenceIdentifier=' + that.data('formPersistenceIdentifier');
        modal.hideModal();
      }
    });

    Modal.show(
      TYPO3.lang['formManager.remove_form_title'],
      TYPO3.lang['formManager.remove_form_message'],
      Severity.warning,
      modalButtons
    );
  });
}

/**
 * @throws 1477649539
 */
function duplicateFormSetup(formManagerApp: FormManager): void {
  $(Identifiers.duplicateFormModalTrigger).on('click', function(e: Event) {
    e.preventDefault();
    const that = $(e.currentTarget);

    /**
     * Wizard step 1
     */
    MultiStepWizard.addSlide('duplicate-form-step-1', TYPO3.lang['formManager.duplicateFormWizard.step1.title'].replace('{0}', that.data('formName')), '', Severity.info, top.TYPO3.lang['wizard.progressStep.configure'], function(slide) {
      let html, savePathSelect;

      MultiStepWizard.lockPrevStep();
      MultiStepWizard.lockNextStep();

      const modal = MultiStepWizard.setup.$carousel.closest('.modal');
      const nextButton = modal.find('.modal-footer').find('button[name="next"]');

      const folders = formManagerApp.getAccessibleFormStorageFolders();
      formManagerApp.assert(folders.length > 0, 'No accessible form storage folders', 1477649539);

      MultiStepWizard.set('formPersistenceIdentifier', that.data('formPersistenceIdentifier'));
      MultiStepWizard.set('savePath', folders[0].value);
      if (folders.length > 1) {
        savePathSelect = $('<select id="duplicate-form-save-path" class="form-select" data-identifier="duplicateFormSavePath" />');
        for (let i = 0, len = folders.length; i < len; ++i) {
          const option = new Option(folders[i].label, folders[i].value);
          $(savePathSelect).append(option);
        }
      }

      html = '<div class="duplicate-form-modal">'
        + '<h5 class="form-section-headline">' + TYPO3.lang['formManager.new_form_name'] + '</h5>'
        + '<div class="mb-3">'
        + '<label for="duplicate-form-name">' + '<strong>' + TYPO3.lang['formManager.form_name'] + '</strong>' + '</label>'
        + '<div class="formengine-field-item t3js-formengine-field-item">'
        + '<div class="form-description">' + TYPO3.lang['formManager.form_name_description'] + '</div>'
        + '<div class="form-control-wrap">'
        + '<input id="duplicate-form-name" class="form-control has-error" data-identifier="duplicateFormName" />'
        + '</div>'
        + '</div>'
        + '</div>';

      if (savePathSelect) {
        html += '<div class="mb-3">'
          + '<label for="duplicate-form-save-path">' + '<strong>' + TYPO3.lang['formManager.form_save_path'] + '</strong>' + '</label>'
          + '<div class="formengine-field-item t3js-formengine-field-item">'
          + '<div class="form-description">' + TYPO3.lang['formManager.form_save_path_description'] + '</div>'
          + '<div class="form-control-wrap">' + $(savePathSelect)[0].outerHTML + '</div>'
          + '</div>'
          + '</div>';
      }

      html += '</div>';

      slide.html(html);
      $(Identifiers.duplicateFormName, modal).focus();

      $(Identifiers.duplicateFormName, modal).on('keyup paste', function(e: JQueryEventObject) {
        const $el = $(event.currentTarget);
        if ($el.val().length > 0) {
          $el.removeClass('has-error');
          MultiStepWizard.unlockNextStep();
          MultiStepWizard.set('formName', $el.val());
          if ('code' in e && e.code === 'Enter') {
            MultiStepWizard.triggerStepButton('next');
          }
        } else {
          $el.addClass('has-error');
          MultiStepWizard.lockNextStep();
        }
      });

      nextButton.on('click', function() {
        MultiStepWizard.setup.forceSelection = false;
        Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
          MultiStepWizard.set('confirmationDuplicateFormName', that.data('formName'));

          if (folders.length > 1) {
            MultiStepWizard.set('savePath', $(Identifiers.duplicateFormSavePath + ' option:selected', modal).val());
            MultiStepWizard.set('confirmationDuplicateFormSavePath', $(Identifiers.duplicateFormSavePath + ' option:selected', modal).text());
          } else {
            MultiStepWizard.set('savePath', folders[0].value);
            MultiStepWizard.set('confirmationDuplicateFormSavePath', folders[0].label);
          }

          slide.html($('<div />', { class: 'text-center' }).append(markup).prop('outerHTML'));
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
            MultiStepWizard.unlockPrevStep();
            MultiStepWizard.unlockNextStep();

            const modal = MultiStepWizard.setup.$carousel.closest('.modal');
            const nextButton = modal.find('.modal-footer').find('button[name="next"]');

            let html = '<div class="new-form-modal">'
              + '<div class="row">'
              + '<div class="col">';

            html += '<div class="mb-3">'
              + '<h5 class="form-section-headline">' + TYPO3.lang['formManager.duplicateFormWizard.step2.check'] + '</h5>'
              + '<p>' + TYPO3.lang['formManager.newFormWizard.step3.message'] + '</p>'
              + '</div>'
              + '<div class="alert alert-notice">'
              + '<div class="alert-body mt-1">'
              + '<div class="dropdown-table-row">'
              + '<div class="dropdown-table-column dropdown-table-icon">'
              + formTemplateIconMarkup
              + '</div>'
              + '<div class="dropdown-table-column dropdown-table-title">'
              + TYPO3.lang['formManager.form_copied']
              + '</div>'
              + '<div class="dropdown-table-column dropdown-table-value">'
              + securityUtility.encodeHtml(settings.confirmationDuplicateFormName)
              + '</div>'
              + '</div>'
              + '<div class="dropdown-table-row">'
              + '<div class="dropdown-table-column dropdown-table-icon">'
              + formNameIconMarkup
              + '</div>'
              + '<div class="dropdown-table-column dropdown-table-title">'
              + TYPO3.lang['formManager.form_name']
              + '</div>'
              + '<div class="dropdown-table-column dropdown-table-value">'
              + securityUtility.encodeHtml(settings.formName)
              + '</div>'
              + '</div>'
              + '<div class="dropdown-table-row">'
              + '<div class="dropdown-table-column dropdown-table-icon">'
              + formStorageMarkup
              + '</div>'
              + '<div class="dropdown-table-column dropdown-table-title">'
              + TYPO3.lang['formManager.form_save_path']
              + '</div>'
              + '<div class="dropdown-table-column dropdown-table-value">'
              + securityUtility.encodeHtml(settings.confirmationDuplicateFormSavePath)
              + '</div>'
              + '</div>'
              + '</div>'
              + '</div>';

            html += '</div>'
              + '</div>'
              + '</div>';

            slide.html(html);

            nextButton.focus();

            nextButton.on('click', function() {
              MultiStepWizard.setup.forceSelection = false;
              Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then(function(markup) {
                slide.html($('<div />', { class: 'text-center' }).append(markup).prop('outerHTML'));
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
      $.post(formManagerApp.getAjaxEndpoint('duplicate'), {
        formName: MultiStepWizard.setup.settings.formName,
        formPersistenceIdentifier: MultiStepWizard.setup.settings.formPersistenceIdentifier,
        savePath: MultiStepWizard.setup.settings.savePath
      }, function(data) {
        if (data.status === 'success') {
          document.location = data.url;
        } else {
          Notification.error(TYPO3.lang['formManager.duplicateFormWizard.step3.errorTitle'], TYPO3.lang['formManager.duplicateFormWizard.step3.errorMessage'] + ' ' + data.message);
        }
        MultiStepWizard.dismiss();
      }).fail(function(jqXHR, textStatus, errorThrown) {
        const parser = new DOMParser(),
          responseDocument = parser.parseFromString(jqXHR.responseText, 'text/html'),
          responseBody = $(responseDocument.body);

        Notification.error(textStatus, errorThrown, 2);
        MultiStepWizard.dismiss();

        $(Identifiers.t3Logo, responseBody).remove();
        $(Identifiers.t3Footer, responseBody).remove();
        $(Identifiers.moduleBody).html(responseBody.html());
      });
    }).then(function() {
      MultiStepWizard.show();
    });
  });
}

function showReferencesSetup(formManagerApp: FormManager): void {
  $(Identifiers.showReferences).on('click', (e: Event): void => {
    e.preventDefault();
    const $that = $(e.currentTarget);
    const url = formManagerApp.getAjaxEndpoint('references') + '&formPersistenceIdentifier=' + $that.data('formPersistenceIdentifier');

    $.get(url, function(data) {
      let html;
      const modalButtons = [];

      modalButtons.push({
        text: TYPO3.lang['formManager.cancel'],
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        trigger: function(e: Event, modal: ModalElement) {
          modal.hideModal();
        }
      });

      const referencesLength = data.references.length;
      if (referencesLength > 0) {
        html = '<div>'
          + '<h3>' + TYPO3.lang['formManager.references.headline'].replace('{0}', $that.data('formName')) + '</h3>'
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

        for (let i = 0, len = data.references.length; i < len; ++i) {
          html += '<tr>'
            + '<td>' + data.references[i].recordPageTitle + '</td>'
            + '<td>'
            + data.references[i].recordIcon
            + '<a href="' + data.references[i].recordEditUrl + '" data-identifier="referenceLink">'
            + data.references[i].recordTitle + ' (uid: ' + data.references[i].recordUid + ')'
            + '</a>'
            + '</td>'
            + '</tr>';
        }

        html += '</tbody>'
          + '</table>'
          + '</div>';
      } else {
        html = '<div>'
          + '<h1>' + TYPO3.lang['formManager.references.title'].replace('{0}', data.formPersistenceIdentifier) + '</h1>'
          + '</div>'
          + '<div>' + TYPO3.lang['formManager.no_references'] + '</div>';
      }

      html = $(html);
      $(Identifiers.referenceLink, html).on('click', function(e) {
        e.preventDefault();
        Modal.currentModal.hideModal();
        document.location = $(e.currentTarget).prop('href');
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
}

export function bootstrap(formManagerApp: FormManager): void {
  removeFormSetup(formManagerApp);
  newFormSetup(formManagerApp);
  duplicateFormSetup(formManagerApp);
  showReferencesSetup(formManagerApp);
}
