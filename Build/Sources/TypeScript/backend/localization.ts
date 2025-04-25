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

import DocumentService from '@typo3/core/document-service';
import $ from 'jquery';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { SeverityEnum } from './enum/severity';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Icons from './icons';
import Modal, { type ModalElement } from './modal';
import MultiStepWizard, { type MultiStepWizardSettings } from './multi-step-wizard';
import '@typo3/backend/element/icon-element';

type LanguageRecord = {
  uid: number;
  title: string;
  flagIcon: string;
};

type SummaryColumns = {
  columns: { [key: number]: string };
  columnList: Array<number>;
};

type SummaryColPosRecord = {
  uid: number;
  title: string;
  icon: string;
};

type SummaryRecord = {
  columns: SummaryColumns;
  records: Array<Array<SummaryColPosRecord>>;
};

class Localization {
  private readonly triggerButton: string = '.t3js-localize';

  constructor() {
    DocumentService.ready().then((): void => {
      this.initialize();
    });
  }

  private async initialize(): Promise<void> {
    const localizeIconMarkup = await Icons.getIcon('actions-localize', Icons.sizes.large);
    const copyIconMarkup = await Icons.getIcon('actions-edit-copy', Icons.sizes.large);

    $(this.triggerButton).removeClass('disabled');

    $(document).on('click', this.triggerButton, async (e: JQueryEventObject): Promise<void> => {
      e.preventDefault();

      const $triggerButton = $(e.currentTarget);
      const actions: Array<string> = [];
      const availableLocalizationModes: Array<string> = [];

      if ($triggerButton.data('allowTranslate') === 0 && $triggerButton.data('allowCopy') === 0) {
        Modal.confirm(
          TYPO3.lang['window.localization.mixed_mode.title'],
          TYPO3.lang['window.localization.mixed_mode.message'],
          SeverityEnum.warning,
          [
            {
              text: TYPO3?.lang?.['button.ok'] || 'OK',
              btnClass: 'btn-warning',
              name: 'ok',
              trigger: (e: Event, modal: ModalElement): void => modal.hideModal()
            }
          ]
        );
        return;
      }

      const availableLanguages: LanguageRecord[] = await (await this.loadAvailableLanguages(
        parseInt($triggerButton.data('pageId'), 10),
        parseInt($triggerButton.data('languageId'), 10),
      )).resolve();

      if ($triggerButton.data('allowTranslate')) {
        actions.push(
          '<div class="row">'
          + '<div class="col-sm-3">'
          + '<input class="btn-check t3js-localization-option" type="radio" name="mode" id="mode_translate" value="localize">'
          + '<label class="btn btn-default btn-block-vertical" for="mode_translate" data-action="localize">'
          + localizeIconMarkup
          + TYPO3.lang['localize.wizard.button.translate']
          + '</label>'
          + '</div>'
          + '<div class="col-sm-9">'
          + '<p class="text-body-secondary">' + TYPO3.lang['localize.educate.translate'] + '</p>'
          + '</div>'
          + '</div>',
        );
        availableLocalizationModes.push('localize');
      }

      if ($triggerButton.data('allowCopy')) {
        actions.push(
          '<div class="row">'
          + '<div class="col-sm-3">'
          + '<input class="btn-check t3js-localization-option" type="radio" name="mode" id="mode_copy" value="copyFromLanguage">'
          + '<label class="btn btn-default btn-block-vertical" for="mode_copy" data-action="copy">'
          + copyIconMarkup
          + TYPO3.lang['localize.wizard.button.copy']
          + '</label>'
          + '</div>'
          + '<div class="col-sm-9">'
          + '<p class="t3js-helptext t3js-helptext-copy text-body-secondary">' + TYPO3.lang['localize.educate.copy'] + '</p>'
          + '</div>'
          + '</div>',
        );
        availableLocalizationModes.push('copyFromLanguage');
      }

      if (availableLocalizationModes.length === 1) {
        MultiStepWizard.set('localizationMode', availableLocalizationModes[0]);
      } else {
        const buttonContainer = document.createElement('div');
        buttonContainer.dataset.bsToggle = 'buttons';
        buttonContainer.append(...actions.map((actionMarkup: string): DocumentFragment => document.createRange().createContextualFragment(actionMarkup)));

        MultiStepWizard.addSlide(
          'localize-choose-action',
          TYPO3.lang['localize.wizard.header_page']
            .replace('{0}', $triggerButton.data('page'))
            .replace('{1}', $triggerButton.data('languageName')),
          buttonContainer,
          SeverityEnum.notice,
          TYPO3.lang['localize.wizard.step.selectMode'],
          ($slide: JQuery, settings: MultiStepWizardSettings): void => {
            if (settings.localizationMode !== undefined) {
              MultiStepWizard.unlockNextStep();
            }
          }
        );
      }

      if (availableLanguages.length === 1) {
        MultiStepWizard.set('sourceLanguage', availableLanguages[0].uid);
      } else {
        MultiStepWizard.addSlide(
          'localize-choose-language',
          TYPO3.lang['localize.view.chooseLanguage'],
          '',
          SeverityEnum.notice,
          TYPO3.lang['localize.wizard.step.chooseLanguage'],
          async ($slide: JQuery, settings: MultiStepWizardSettings): Promise<void> => {
            if (settings.sourceLanguage !== undefined) {
              MultiStepWizard.unlockNextStep();
            }

            $slide.html('<div class="text-center">' + (await Icons.getIcon('spinner-circle', Icons.sizes.large)) + '</div>');
            MultiStepWizard.getComponent().on('change', '.t3js-language-option', (optionEvt: JQueryEventObject): void => {
              MultiStepWizard.set('sourceLanguage', $(optionEvt.currentTarget).val());
              MultiStepWizard.unlockNextStep();
            });

            const $languageButtons = $('<div />', { class: 'row' });

            for (const languageObject of availableLanguages) {
              const id: string = 'language' + languageObject.uid;
              const $input: JQuery = $('<input />', {
                type: 'radio',
                name: 'language',
                id: id,
                value: languageObject.uid,
                class: 'btn-check t3js-language-option'
              });
              const $label: JQuery = $('<label />', {
                class: 'btn btn-default btn-block',
                'for': id
              })
                .text(' ' + languageObject.title)
                .prepend(languageObject.flagIcon);

              $languageButtons.append(
                $('<div />', { class: 'col-sm-4' })
                  .append($input)
                  .append($label),
              );
            }
            $slide.empty().append($languageButtons);
          },
        );
      }
      MultiStepWizard.addSlide(
        'localize-summary',
        TYPO3.lang['localize.view.summary'],
        '',
        SeverityEnum.notice,
        TYPO3.lang['localize.wizard.step.selectRecords'],
        async ($slide: JQuery, settings: MultiStepWizardSettings): Promise<void> => {
          $slide.empty().html('<div class="text-center">' + (await Icons.getIcon('spinner-circle', Icons.sizes.large)) + '</div>');

          const result: SummaryRecord = await (await this.getSummary(
            parseInt($triggerButton.data('pageId'), 10),
            parseInt($triggerButton.data('languageId'), 10),
            settings.sourceLanguage
          )).resolve();

          $slide.empty();

          MultiStepWizard.set('records', []);

          const columns = result.columns.columns;
          const columnList = result.columns.columnList;

          columnList.forEach((colPos: number): void => {
            if (typeof result.records[colPos] === 'undefined') {
              return;
            }

            const column = columns[colPos];
            const rowElement = document.createElement('div');
            rowElement.classList.add('row', 'gy-2');

            result.records[colPos].forEach((record: SummaryColPosRecord): void => {
              const label = ' (' + record.uid + ') ' + record.title;
              settings.records.push(record.uid);

              const columnElement = document.createElement('div');
              columnElement.classList.add('col-sm-6');

              const inputGroupElement = document.createElement('div');
              inputGroupElement.classList.add('input-group');

              const inputGroupTextElement = document.createElement('span');
              inputGroupTextElement.classList.add('input-group-text');

              const checkboxContainerElement = document.createElement('span');
              checkboxContainerElement.classList.add('form-check', 'form-check-type-toggle');

              const checkboxInputElement = document.createElement('input');
              checkboxInputElement.type = 'checkbox';
              checkboxInputElement.id = 'record-uid-' + record.uid;
              checkboxInputElement.classList.add('form-check-input', 't3js-localization-toggle-record');
              checkboxInputElement.checked = true;
              checkboxInputElement.dataset.uid = record.uid.toString();
              checkboxInputElement.ariaLabel = label;

              const labelElement = document.createElement('label');
              labelElement.classList.add('form-control');
              labelElement.htmlFor = 'record-uid-' + record.uid;
              labelElement.innerHTML = record.icon;
              labelElement.appendChild(document.createTextNode(label));

              checkboxContainerElement.appendChild(checkboxInputElement);
              inputGroupTextElement.appendChild(checkboxContainerElement);
              inputGroupElement.appendChild(inputGroupTextElement);
              inputGroupElement.appendChild(labelElement);
              columnElement.appendChild(inputGroupElement);

              rowElement.appendChild(columnElement);
            });

            const fieldsetElement = document.createElement('fieldset');
            fieldsetElement.classList.add('localization-fieldset');

            const fieldsetCheckboxContaineElement = document.createElement('div');
            fieldsetCheckboxContaineElement.classList.add('form-check', 'form-check-type-toggle');

            const fieldsetCheckboxInputElement = document.createElement('input');
            fieldsetCheckboxInputElement.classList.add('form-check-input', 't3js-localization-toggle-column');
            fieldsetCheckboxInputElement.id = 'records-column-' + colPos;
            fieldsetCheckboxInputElement.type = 'checkbox';
            fieldsetCheckboxInputElement.checked = true;

            const fieldsetCheckboxInputLabel = document.createElement('label');
            fieldsetCheckboxInputLabel.classList.add('form-check-label');
            fieldsetCheckboxInputLabel.htmlFor = 'records-column-' + colPos;
            fieldsetCheckboxInputLabel.textContent = column;

            fieldsetCheckboxContaineElement.appendChild(fieldsetCheckboxInputElement);
            fieldsetCheckboxContaineElement.appendChild(fieldsetCheckboxInputLabel);
            fieldsetElement.appendChild(fieldsetCheckboxContaineElement);
            fieldsetElement.appendChild(rowElement);

            $slide.append(fieldsetElement);
          });

          MultiStepWizard.unlockNextStep();

          MultiStepWizard.getComponent().on('change', '.t3js-localization-toggle-record', (cmpEvt: JQueryEventObject): void => {
            const $me = $(cmpEvt.currentTarget);
            const uid = $me.data('uid');
            const $parent = $me.closest('fieldset');
            const $columnCheckbox = $parent.find('.t3js-localization-toggle-column');

            if ($me.is(':checked')) {
              settings.records.push(uid);
            } else {
              const index = settings.records.indexOf(uid);
              if (index > -1) {
                settings.records.splice(index, 1);
              }
            }

            const $allChildren = $parent.find('.t3js-localization-toggle-record');
            const $checkedChildren = $parent.find('.t3js-localization-toggle-record:checked');

            $columnCheckbox.prop('checked', $checkedChildren.length > 0);
            $columnCheckbox.prop('__indeterminate', $checkedChildren.length > 0 && $checkedChildren.length < $allChildren.length);

            if (settings.records.length > 0) {
              MultiStepWizard.unlockNextStep();
            } else {
              MultiStepWizard.lockNextStep();
            }
          }).on('change', '.t3js-localization-toggle-column', (toggleEvt: JQueryEventObject): void => {
            const $me = $(toggleEvt.currentTarget);
            const $children = $me.closest('fieldset').find('.t3js-localization-toggle-record');

            $children.prop('checked', $me.is(':checked'));
            $children.trigger('change');
          });
        },
      );

      MultiStepWizard.addFinalProcessingSlide(async ($slide: JQuery, settings: MultiStepWizardSettings): Promise<void> => {
        await this.localizeRecords(
          parseInt($triggerButton.data('pageId'), 10),
          parseInt($triggerButton.data('languageId'), 10),
          settings.sourceLanguage,
          settings.localizationMode,
          settings.records,
        );
        MultiStepWizard.dismiss();
        document.location.reload();
      }).then((): void => {
        MultiStepWizard.show();

        MultiStepWizard.getComponent().on('change', '.t3js-localization-option', (optionEvt: JQueryEventObject): void => {
          MultiStepWizard.set('localizationMode', $(optionEvt.currentTarget).val());
          MultiStepWizard.unlockNextStep();
        });
      });
    });
  }

  /**
   * Load available languages from page
   *
   * @param {number} pageId
   * @param {number} languageId
   * @returns {Promise<AjaxResponse>}
   */
  private loadAvailableLanguages(pageId: number, languageId: number): Promise<AjaxResponse> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.page_languages).withQueryArguments({
      pageId: pageId,
      languageId: languageId,
    }).get();
  }

  /**
   * Get summary for record processing
   */
  private getSummary(pageId: number, languageId: number, sourceLanguage: number): Promise<AjaxResponse> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize_summary).withQueryArguments({
      pageId: pageId,
      destLanguageId: languageId,
      languageId: sourceLanguage,
    }).get();
  }

  /**
   * Localize records
   */
  private localizeRecords(pageId: number, languageId: number, sourceLanguage: number, localizationMode: string, uidList: Array<number>): Promise<AjaxResponse> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize).withQueryArguments({
      pageId: pageId,
      srcLanguageId: sourceLanguage,
      destLanguageId: languageId,
      action: localizationMode,
      uidList: uidList,
    }).get();
  }
}

export default new Localization();
