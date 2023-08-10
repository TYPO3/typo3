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

import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

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

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getData();

    // Select / deselect all checkboxes
    new RegularEvent('click', (event: Event, element: HTMLInputElement): void => {
      element.closest('fieldset').querySelectorAll('input[type="checkbox"]').forEach((checkbox: HTMLInputElement): void => {
        checkbox.checked = element.checked;
      });
    }).delegateTo(currentModal, '.t3js-databaseAnalyzer-suggestion-block-checkbox');

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.analyze();
    }).delegateTo(currentModal, this.selectorAnalyzeTrigger);

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.execute();
    }).delegateTo(currentModal, this.selectorExecuteTrigger);
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('databaseAnalyzer')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
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
    const outputContainer = modalContent.querySelector<HTMLElement>(this.selectorOutputContainer);
    const progressBar = this.renderProgressBar(outputContainer, {
      label: 'Analyzing current database schema...'
    });
    new RegularEvent('change', (): void => {
      const hasCheckedCheckboxes = outputContainer.querySelectorAll(':checked').length > 0;
      this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(this.selectorExecuteTrigger), hasCheckedCheckboxes);
    }).delegateTo(outputContainer, 'input[type="checkbox"]');

    (new AjaxRequest(Router.getUrl('databaseAnalyzerAnalyze')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              progressBar.remove();
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            }
            if (Array.isArray(data.suggestions)) {
              data.suggestions.forEach((element: any): void => {
                const aBlock = modalContent.querySelector(this.selectorSuggestionBlock).cloneNode(true) as HTMLElement;
                aBlock.classList.remove(this.selectorSuggestionBlock.substring(1));
                const key = element.key;
                aBlock.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-block-legend').innerText = element.label;
                aBlock.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-block-checkbox').setAttribute('id', 't3-install-' + key + '-checkbox');
                if (element.enabled) {
                  aBlock.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-block-checkbox').setAttribute('checked', 'checked');
                }
                aBlock.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-block-label').setAttribute('for', 't3-install-' + key + '-checkbox');
                element.children.forEach((line: any): void => {
                  const aLine = modalContent.querySelector<HTMLElement>(this.selectorSuggestionLineTemplate).children[0].cloneNode(true) as HTMLElement;
                  const hash = line.hash;
                  const checkbox = aLine.querySelector<HTMLInputElement>('.t3js-databaseAnalyzer-suggestion-line-checkbox');
                  checkbox.setAttribute('id', 't3-install-db-' + hash);
                  checkbox.setAttribute('data-hash', hash);
                  if (element.enabled) {
                    checkbox.setAttribute('checked', 'checked');
                  }
                  aLine.querySelector('.t3js-databaseAnalyzer-suggestion-line-label').setAttribute('for', 't3-install-db-' + hash);
                  aLine.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-line-statement').innerText = line.statement;
                  if (typeof line.current !== 'undefined') {
                    aLine.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-line-current-value').innerText = line.current;
                    aLine.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-line-current').style.display = 'inline';
                  }
                  if (typeof line.rowCount !== 'undefined') {
                    aLine.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-line-count-value').innerText = line.rowCount;
                    aLine.querySelector<HTMLElement>('.t3js-databaseAnalyzer-suggestion-line-count').style.display = 'inline';
                  }
                  aBlock.querySelector<HTMLElement>(this.selectorSuggestionList).append(aLine);
                });
                outputContainer.append(aBlock);
              });

              this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(this.selectorAnalyzeTrigger), true);
              this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(this.selectorExecuteTrigger), outputContainer.querySelectorAll(':checked').length > 0);
            }
            if (data.suggestions.length === 0 && data.status.length === 0) {
              outputContainer.append(InfoBox.create(Severity.ok, 'Database schema is up to date. Good job!'));
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
    const executeToken = this.getModuleContent().dataset.databaseAnalyzerExecuteToken;
    const outputContainer: HTMLElement = modalContent.querySelector(this.selectorOutputContainer);

    const selectedHashes: string[] = [];
    outputContainer.querySelectorAll('.t3js-databaseAnalyzer-suggestion-line input:checked').forEach((element: HTMLElement): void => {
      selectedHashes.push(element.dataset.hash);
    });
    this.renderProgressBar(outputContainer, {
      label: 'Executing database updates...'
    });
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'databaseAnalyzerExecute',
          token: executeToken,
          hashes: selectedHashes,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
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
