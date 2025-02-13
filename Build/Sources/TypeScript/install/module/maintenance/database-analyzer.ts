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
import { AbstractInteractableModule, ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';
import SecurityUtility from '@typo3/core/security-utility';

enum Identifiers {
  analyzeTrigger = '.t3js-databaseAnalyzer-analyze',
  executeTrigger = '.t3js-databaseAnalyzer-execute',
  outputContainer = '.t3js-databaseAnalyzer-output',
  notificationContainer = '.t3js-databaseAnalyzer-notification',
  suggestionBlock = '#t3js-databaseAnalyzer-suggestion-block',
  suggestionBlockCheckbox = '.t3js-databaseAnalyzer-suggestion-block-checkbox',
  suggestionBlockLegend = '.t3js-databaseAnalyzer-suggestion-block-legend',
  suggestionBlockLabel = '.t3js-databaseAnalyzer-suggestion-block-label',
  suggestionList = '.t3js-databaseAnalyzer-suggestion-list',
  suggestionLineTemplate = '#t3js-databaseAnalyzer-suggestion-line-template',
  suggestionLineCheckbox = '.t3js-databaseAnalyzer-suggestion-line-checkbox',
  suggestionLineLabel = '.t3js-databaseAnalyzer-suggestion-line-label',
  suggestionLineStatement = '.t3js-databaseAnalyzer-suggestion-line-statement',
  suggestionLineCurrent = '.t3js-databaseAnalyzer-suggestion-line-current',
  suggestionLineCurrentValue = '.t3js-databaseAnalyzer-suggestion-line-current-value',
  suggestionLineCount = '.t3js-databaseAnalyzer-suggestion-line-count',
  suggestionLineCountValue = '.t3js-databaseAnalyzer-suggestion-line-count-value'
}

type SuggestionsResponse = {
  status: MessageInterface[],
  success: boolean,
  suggestions: {
    children: {
      hash: string,
      statement: string,
    }[],
    enabled: boolean,
    key: string,
    label: string,
  }[]
}

type SuggestionsExecutedResponse = {
  status: MessageInterface[],
  success: boolean,
};

/**
 * Module: @typo3/install/module/database-analyzer
 */
class DatabaseAnalyzer extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js').then((): void => {
      this.getData();
    });

    // Select / deselect all checkboxes
    new RegularEvent('click', (event: Event, element: HTMLInputElement): void => {
      element.closest('fieldset').querySelectorAll('input[type="checkbox"]').forEach((checkbox: HTMLInputElement): void => {
        checkbox.checked = element.checked;
      });
    }).delegateTo(currentModal, Identifiers.suggestionBlockCheckbox);

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.clearNotifications();
      this.analyze();
    }).delegateTo(currentModal, Identifiers.analyzeTrigger);

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.clearNotifications();
      this.execute();
    }).delegateTo(currentModal, Identifiers.executeTrigger);
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('databaseAnalyzer')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponseWithButtons = await response.resolve();
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
    const outputContainer = modalContent.querySelector<HTMLElement>(Identifiers.outputContainer);
    const progressBar = this.renderProgressBar(outputContainer, {
      label: 'Analyzing current database schema...'
    });
    new RegularEvent('change', (): void => {
      const hasCheckedCheckboxes = outputContainer.querySelectorAll(':checked').length > 0;
      this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.executeTrigger), hasCheckedCheckboxes);
    }).delegateTo(outputContainer, 'input[type="checkbox"]');

    (new AjaxRequest(Router.getUrl('databaseAnalyzerAnalyze')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: SuggestionsResponse = await response.resolve();
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              progressBar.remove();
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            }
            if (Array.isArray(data.suggestions)) {
              data.suggestions.forEach((element: any): void => {
                const aBlock = (modalContent.querySelector(Identifiers.suggestionBlock) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
                const key = element.key;
                aBlock.querySelector<HTMLElement>(Identifiers.suggestionBlockLegend).innerText = element.label;
                aBlock.querySelector<HTMLElement>(Identifiers.suggestionBlockCheckbox).setAttribute('id', 't3-install-' + key + '-checkbox');
                if (element.enabled) {
                  aBlock.querySelector<HTMLElement>(Identifiers.suggestionBlockCheckbox).setAttribute('checked', 'checked');
                }
                aBlock.querySelector<HTMLElement>(Identifiers.suggestionBlockLabel).setAttribute('for', 't3-install-' + key + '-checkbox');
                element.children.forEach((line: any): void => {
                  const aLine = (modalContent.querySelector(Identifiers.suggestionLineTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
                  const hash = line.hash;
                  const checkbox = aLine.querySelector<HTMLInputElement>(Identifiers.suggestionLineCheckbox);
                  checkbox.setAttribute('id', 't3-install-db-' + hash);
                  checkbox.setAttribute('data-hash', hash);
                  if (element.enabled) {
                    checkbox.setAttribute('checked', 'checked');
                  }
                  aLine.querySelector(Identifiers.suggestionLineLabel).setAttribute('for', 't3-install-db-' + hash);
                  aLine.querySelector<HTMLElement>(Identifiers.suggestionLineStatement).innerText = line.statement;
                  if (typeof line.current !== 'undefined') {
                    aLine.querySelector<HTMLElement>(Identifiers.suggestionLineCurrentValue).innerText = line.current;
                    aLine.querySelector<HTMLElement>(Identifiers.suggestionLineCurrent).style.display = 'inline';
                  }
                  if (typeof line.rowCount !== 'undefined') {
                    aLine.querySelector<HTMLElement>(Identifiers.suggestionLineCountValue).innerText = line.rowCount;
                    aLine.querySelector<HTMLElement>(Identifiers.suggestionLineCount).style.display = 'inline';
                  }
                  aBlock.querySelector<HTMLElement>(Identifiers.suggestionList).append(aLine);
                });
                outputContainer.append(aBlock);
              });

              this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.analyzeTrigger), true);
              this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.executeTrigger), outputContainer.querySelectorAll(':checked').length > 0);
            }
            if (data.suggestions.length === 0 && data.status.length === 0) {
              outputContainer.append(InfoBox.create(Severity.ok, 'Database schema is up to date. Good job!'));
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
            this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.analyzeTrigger), true);
            this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.executeTrigger), false);
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
          this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.analyzeTrigger), true);
          this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.executeTrigger), false);
        }
      );
  }

  private execute(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.databaseAnalyzerExecuteToken;
    const outputContainer: HTMLElement = modalContent.querySelector(Identifiers.outputContainer);
    const notificationContainer: HTMLElement = modalContent.querySelector(Identifiers.notificationContainer);

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
      }).then(
        async (response: AjaxResponse): Promise<void> => {
          const data: SuggestionsExecutedResponse = await response.resolve();
          if (Array.isArray(data.status)) {
            let groupedErrors: string = '';
            data.status.forEach((element: MessageInterface): void => {
              if(element.severity === Severity.error) {
                const securityUtility = new SecurityUtility();
                groupedErrors += '<li>' + securityUtility.encodeHtml(element.message) + '</li>';
              } else {
                Notification.showMessage(element.title, element.message, element.severity);
              }
            });

            if(groupedErrors !== '') {
              notificationContainer.innerHTML = `<div class="alert alert-danger">
                <div class="alert-inner">
                  <div class="alert-icon">
                      <span class="icon-emphasized">
                          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
                      </span>
                  </div>
                  <div class="alert-content">
                      <div class="alert-title">Database update failed</div>
                      <div class="alert-message">
                        <ul>${groupedErrors}</ul>
                      </div>
                  </div>
                </div>
              </div>`;
            }
          }
          this.analyze();
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      ).finally((): void => {
        this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.analyzeTrigger), true);
        this.setModalButtonState(this.getModalFooter().querySelector<HTMLButtonElement>(Identifiers.executeTrigger), false);
      });
  }

  private clearNotifications() {
    this.currentModal.querySelector(Identifiers.notificationContainer).replaceChildren('');
  }
}

export default new DatabaseAnalyzer();
