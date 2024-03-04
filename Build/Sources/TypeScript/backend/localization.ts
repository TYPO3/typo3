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
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { SeverityEnum } from './enum/severity';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Icons from './icons';
import Modal, { ModalElement } from './modal';
import MultiStepWizard, { MultiStepWizardSettings } from './multi-step-wizard';
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
          + '<label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-translate">'
          + localizeIconMarkup
          + '<input type="radio" name="mode" id="mode_translate" value="localize" style="display: none">'
          + '<br>' + TYPO3.lang['localize.wizard.button.translate'] + '</label>'
          + '</div>'
          + '<div class="col-sm-9">'
          + '<p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang['localize.educate.translate'] + '</p>'
          + '</div>'
          + '</div>',
        );
        availableLocalizationModes.push('localize');
      }

      if ($triggerButton.data('allowCopy')) {
        actions.push(
          '<div class="row">'
          + '<div class="col-sm-3">'
          + '<label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-copy">'
          + copyIconMarkup
          + '<input type="radio" name="mode" id="mode_copy" value="copyFromLanguage" style="display: none">'
          + '<br>' + TYPO3.lang['localize.wizard.button.copy'] + '</label>'
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
            MultiStepWizard.getComponent().on('click', '.t3js-language-option', (optionEvt: JQueryEventObject): void => {
              const $me = $(optionEvt.currentTarget);
              const $radio = $me.prev();

              MultiStepWizard.set('sourceLanguage', $radio.val());
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
                style: 'display: none;',
                class: 'btn-check'
              });
              const $label: JQuery = $('<label />', {
                class: 'btn btn-default d-block t3js-language-option option',
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
            const $row = $('<div />', { class: 'row' });

            result.records[colPos].forEach((record: SummaryColPosRecord): void => {
              const label = ' (' + record.uid + ') ' + record.title;
              settings.records.push(record.uid);

              $row.append(
                $('<div />', { 'class': 'col-sm-6' }).append(
                  $('<div />', { 'class': 'input-group' }).append(
                    $('<span />', { 'class': 'input-group-text' }).append(
                      $('<input />', {
                        type: 'checkbox',
                        'class': 't3js-localization-toggle-record',
                        id: 'record-uid-' + record.uid,
                        checked: 'checked',
                        'data-uid': record.uid,
                        'aria-label': label,
                      }),
                    ),
                    $('<label />', {
                      'class': 'form-control',
                      for: 'record-uid-' + record.uid,
                    }).text(label).prepend(record.icon),
                  ),
                ),
              );
            });

            $slide.append(
              $('<fieldset />', {
                'class': 'localization-fieldset',
              }).append(
                $('<label />').text(column).prepend(
                  $('<input />', {
                    'class': 't3js-localization-toggle-column',
                    type: 'checkbox',
                    checked: 'checked',
                  }),
                ),
                $row,
              ),
            );
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

        MultiStepWizard.getComponent().on('click', '.t3js-localization-option', (optionEvt: JQueryEventObject): void => {
          const $me = $(optionEvt.currentTarget);
          const $radio = $me.find('input[type="radio"]');

          if ($me.data('helptext')) {
            const $container = $(optionEvt.delegateTarget);
            $container.find('.t3js-localization-option').removeClass('active');
            $container.find('.t3js-helptext').addClass('text-body-secondary');
            $me.addClass('active');
            $container.find($me.data('helptext')).removeClass('text-body-secondary');
          }
          MultiStepWizard.set('localizationMode', $radio.val());
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
