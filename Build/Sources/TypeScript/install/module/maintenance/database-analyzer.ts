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

import $ from 'jquery';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {AbstractInteractableModule} from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import InfoBox from '../../renderable/info-box';
import ProgressBar from '../../renderable/progress-bar';
import Severity from '../../renderable/severity';
import Router from '../../router';

/**
 * Module: @typo3/install/module/database-analyzer
 */
class DatabaseAnalyzer extends AbstractInteractableModule {
  private selectorAnalyzeTrigger: string = '.t3js-databaseAnalyzer-analyze';
  private selectorExecuteTrigger: string = '.t3js-databaseAnalyzer-execute';
  private selectorOutputContainer: string = '.t3js-databaseAnalyzer-output';
  private selectorSuggestionBlock: string = '.t3js-databaseAnalyzer-suggestion-block';
  private selectorSuggestionList: string = '.t3js-databaseAnalyzer-suggestion-list';
  private selectorSuggestionLineTemplate: string = '.t3js-databaseAnalyzer-suggestion-line-template';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData();

    // Select / deselect all checkboxes
    currentModal.on('click', '.t3js-databaseAnalyzer-suggestion-block-checkbox', (e: JQueryEventObject): void => {
      const $element = $(e.currentTarget);
      $element.closest('fieldset').find(':checkbox').prop('checked', (<HTMLInputElement>$element.get(0)).checked);
    });
    currentModal.on('click', this.selectorAnalyzeTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.analyze();
    });
    currentModal.on('click', this.selectorExecuteTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.execute();
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('databaseAnalyzer')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
            this.analyze();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private analyze(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const modalFooter = this.getModalFooter();
    const outputContainer = modalContent.find(this.selectorOutputContainer);
    const executeTrigger = modalFooter.find(this.selectorExecuteTrigger);
    const analyzeTrigger = modalFooter.find(this.selectorAnalyzeTrigger);

    outputContainer.empty().append(ProgressBar.render(Severity.loading, 'Analyzing current database schema...', ''));
    outputContainer.on('change', 'input[type="checkbox"]', (): void => {
      const hasCheckedCheckboxes = outputContainer.find(':checked').length > 0;
      this.setModalButtonState(executeTrigger, hasCheckedCheckboxes);
    });

    (new AjaxRequest(Router.getUrl('databaseAnalyzerAnalyze')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              outputContainer.find('.alert-loading').remove();
              data.status.forEach((element: any): void => {
                const message = InfoBox.render(element.severity, element.title, element.message);
                outputContainer.append(message);
              });
            }
            if (Array.isArray(data.suggestions)) {
              data.suggestions.forEach((element: any): void => {
                const aBlock = modalContent.find(this.selectorSuggestionBlock).clone();
                aBlock.removeClass(this.selectorSuggestionBlock.substr(1));
                const key = element.key;
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-legend').text(element.label);
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-checkbox').attr('id', 't3-install-' + key + '-checkbox');
                if (element.enabled) {
                  aBlock.find('.t3js-databaseAnalyzer-suggestion-block-checkbox').attr('checked', 'checked');
                }
                aBlock.find('.t3js-databaseAnalyzer-suggestion-block-label').attr('for', 't3-install-' + key + '-checkbox');
                element.children.forEach((line: any): void => {
                  const aLine = modalContent.find(this.selectorSuggestionLineTemplate).children().clone();
                  const hash = line.hash;
                  const $checkbox = aLine.find('.t3js-databaseAnalyzer-suggestion-line-checkbox');
                  $checkbox.attr('id', 't3-install-db-' + hash).attr('data-hash', hash);
                  if (element.enabled) {
                    $checkbox.attr('checked', 'checked');
                  }
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-label').attr('for', 't3-install-db-' + hash);
                  aLine.find('.t3js-databaseAnalyzer-suggestion-line-statement').text(line.statement);
                  if (typeof line.current !== 'undefined') {
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-current-value').text(line.current);
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-current').show();
                  }
                  if (typeof line.rowCount !== 'undefined') {
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-count-value').text(line.rowCount);
                    aLine.find('.t3js-databaseAnalyzer-suggestion-line-count').show();
                  }
                  aBlock.find(this.selectorSuggestionList).append(aLine);
                });
                outputContainer.append(aBlock.html());
              });

              this.setModalButtonState(analyzeTrigger, true);
              this.setModalButtonState(executeTrigger, outputContainer.find(':checked').length > 0);
            }
            if (data.suggestions.length === 0 && data.status.length === 0) {
              outputContainer.append(InfoBox.render(Severity.ok, 'Database schema is up to date. Good job!', ''));
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private execute(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('database-analyzer-execute-token');
    const outputContainer = modalContent.find(this.selectorOutputContainer);

    const selectedHashes: Array<any> = [];
    outputContainer.find('.t3js-databaseAnalyzer-suggestion-line input:checked').each((index: number, element: any): void => {
      selectedHashes.push($(element).data('hash'));
    });
    outputContainer.empty().append(ProgressBar.render(Severity.loading, 'Executing database updates...', ''));

    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'databaseAnalyzerExecute',
          token: executeToken,
          hashes: selectedHashes,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              Notification.showMessage(element.title, element.message, element.severity);
            });
          }
          this.analyze();
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export default new DatabaseAnalyzer();
