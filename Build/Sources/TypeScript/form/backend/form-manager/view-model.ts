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
import Modal, { type ModalElement } from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import Icons from '@typo3/backend/icons';
import Notification from '@typo3/backend/notification';
import SecurityUtility from '@typo3/core/security-utility';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { FormManager } from '@typo3/form/backend/form-manager';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import { html } from 'lit';
import formManagerLabels from '~labels/form.form_manager_javascript';

const securityUtility = new SecurityUtility();

enum Identifiers {
  newFormModalTrigger = '[data-identifier="newForm"]',
  duplicateFormModalTrigger = '[data-identifier="duplicateForm"]',
  removeFormModalTrigger = '[data-identifier="removeForm"]',

  showReferences = '[data-identifier="showReferences"]',
  referenceLink = '[data-identifier="referenceLink"]',
}

function newFormSetup(formManagerApp: FormManager): void {
  $(Identifiers.newFormModalTrigger).on('click', async function() {
    await topLevelModuleImport('@typo3/form/backend/form-wizard/form-wizard.js');
    const content = html`<typo3-backend-form-wizard .formManager="${formManagerApp}"
    ></typo3-backend-form-wizard>`;

    Modal.advanced({
      title: formManagerLabels.get('formManager.newFormWizard.step1.title'),
      content: content,
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      staticBackdrop: true,
      buttons: []
    });
  });
}

function removeFormSetup(formManagerApp: FormManager): void {
  $(Identifiers.removeFormModalTrigger).on('click', function(e: Event) {
    const modalButtons = [];

    e.preventDefault();
    const that = e.currentTarget as HTMLElement;

    modalButtons.push({
      text: formManagerLabels.get('formManager.cancel'),
      active: true,
      btnClass: 'btn-default',
      name: 'cancel',
      trigger: function(e: Event, modal: ModalElement) {
        modal.hideModal();
      }
    });

    modalButtons.push({
      text: formManagerLabels.get('formManager.remove_form'),
      active: true,
      btnClass: 'btn-danger',
      name: 'createform',
      trigger: function(e: Event, modal: ModalElement) {
        new AjaxRequest(formManagerApp.getAjaxEndpoint('delete')).post({
          formPersistenceIdentifier: that.dataset.formPersistenceIdentifier,
        }).then(async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.status === 'success') {
            document.location = data.url;
          } else {
            Notification.error(data.title, data.message);
          }
          modal.hideModal();
        });
      }
    });

    Modal.show(
      formManagerLabels.get('formManager.remove_form_title'),
      formManagerLabels.get('formManager.remove_form_message', { '0': that.dataset.formName }),
      Severity.error ,
      modalButtons
    );
  });
}

function duplicateFormSetup(formManagerApp: FormManager): void {
  $(Identifiers.duplicateFormModalTrigger).on('click', async function(e: Event) {
    e.preventDefault();
    const formElement = e.currentTarget as HTMLElement;
    await topLevelModuleImport('@typo3/form/backend/form-wizard/form-wizard.js');
    const duplicateForm = {
      name: formElement.dataset.formName,
      persistenceIdentifier: formElement.dataset.formPersistenceIdentifier
    };

    const content = html`
        <typo3-backend-form-wizard
          .formManager="${formManagerApp}"
          .duplicateForm="${duplicateForm}"
        ></typo3-backend-form-wizard>
    `;
    Modal.advanced({
      title: formManagerLabels.get('formManager.duplicateFormWizard.step1.title', { '0': formElement.dataset.formName }),
      content: content,
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      staticBackdrop: true,
      buttons: []
    });
  });
}

function showReferencesSetup(formManagerApp: FormManager): void {
  $(Identifiers.showReferences).on('click', (e: Event): void => {
    e.preventDefault();
    const currentTarget = e.currentTarget as HTMLElement;
    const url = formManagerApp.getAjaxEndpoint('references') + '&formPersistenceIdentifier=' + currentTarget.dataset.formPersistenceIdentifier;

    new AjaxRequest(url).get().then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      let html;
      const modalButtons = [];

      modalButtons.push({
        text: formManagerLabels.get('formManager.cancel'),
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        trigger: function(e: Event, modal: ModalElement) {
          modal.hideModal();
        }
      });

      const referencesLength = data.references.length;
      const editIconMarkup = await Icons.getIcon('actions-open', Icons.sizes.small);

      if (referencesLength > 0) {
        html = '<h2 class="h3">' + formManagerLabels.get('formManager.references.headline') + '</h2>'
          + '<div class="table-fit">'
          + '<table id="forms" class="table table-striped table-hover">'
          + '<thead>'
          + '<tr>'
          + '<th class="col-icon"></th>'
          + '<th class="col-recordtitle">' + formManagerLabels.get('formManager.table.field.title') + '</th>'
          + '<th>' + formManagerLabels.get('formManager.table.field.uid') + '</th>'
          + '<th class="col-control nowrap"><span class="visually-hidden">' + formManagerLabels.get('formManager.table.field.control') + '</span></th>'
          + '</tr>'
          + '</thead>'
          + '<tbody>';

        for (let i = 0, len = data.references.length; i < len; ++i) {
          html += '<tr>'
            + '<td class="col-icon">' + data.references[i].recordIcon + '</td>'
            + '<td class="col-recordtitle">'
            + '<a href="' + securityUtility.encodeHtml(data.references[i].recordEditUrl) + '" data-identifier="referenceLink">' + securityUtility.encodeHtml(data.references[i].recordTitle) + '</a>'
            + '</td>'
            + '<td>' + securityUtility.encodeHtml(data.references[i].recordUid) + '</td>'
            + '<td class="col-control">'
            + '<div class="btn-group" role="group">'
            + '<a href="' + securityUtility.encodeHtml(data.references[i].recordEditUrl) + '" data-identifier="referenceLink" class="btn btn-default" title="' + formManagerLabels.get('formManager.btn.edit.title') + '">'
            + editIconMarkup
            + '</a>'
            + '</div>'
            + '</td>'
            + '</tr>';
        }

        html += '</tbody>'
          + '</table>'
          + '</div>';
      } else {
        html = '<div>'
          + '<h1>' + formManagerLabels.get('formManager.references.title', { '0': securityUtility.encodeHtml(data.formPersistenceIdentifier) }) + '</h1>'
          + '</div>'
          + '<div>' + formManagerLabels.get('formManager.no_references') + '</div>';
      }

      html = $(html);
      $(Identifiers.referenceLink, html).on('click', function(e) {
        e.preventDefault();
        Modal.currentModal.hideModal();
        document.location = $(e.currentTarget).prop('href');
      });

      Modal.show(
        formManagerLabels.get('formManager.references.title', { '0': currentTarget.dataset.formName }),
        html,
        Severity.notice,
        modalButtons
      );
    }).catch((error: unknown): void => {
      if (error instanceof AjaxResponse) {
        Notification.error(error.response.statusText, String(error.response.status), 2);
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
