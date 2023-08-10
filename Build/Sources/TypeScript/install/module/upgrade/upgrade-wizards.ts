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

import 'bootstrap';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import SecurityUtility from '@typo3/core/security-utility';
import { FlashMessage } from '../../renderable/flash-message';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

type UpgradeWizardsBlockingDatabaseAddsResponse = {
  success: boolean;
  needsUpdate: boolean;
  adds: {
    tables?: {
      table: string;
    }[],
    columns?: {
      table: string;
      field: string;
    }[]
    indexes?: {
      table: string;
      index: string;
    }[]
  }
};

type UpgradeWizard = {
  class: string;
  identifier: string;
  title: string;
  shouldRenderWizard: boolean;
  explanation: string;
};

type UpgradeWizardDone = {
  class: string;
  identifier: string;
  title: string;
};

/**
 * Module: @typo3/install/module/upgrade-wizards
 */
class UpgradeWizards extends AbstractInteractableModule {
  private selectorOutputWizardsContainer: string = '.t3js-upgradeWizards-wizards-output';
  private selectorOutputDoneContainer: string = '.t3js-upgradeWizards-done-output';
  private selectorWizardsBlockingAddsTemplate: string = '.t3js-upgradeWizards-blocking-adds-template';
  private selectorWizardsBlockingAddsRows: string = '.t3js-upgradeWizards-blocking-adds-rows';
  private selectorWizardsBlockingAddsExecute: string = '.t3js-upgradeWizards-blocking-adds-execute';
  private selectorWizardsBlockingCharsetTemplate: string = '.t3js-upgradeWizards-blocking-charset-template';
  private selectorWizardsBlockingCharsetFix: string = '.t3js-upgradeWizards-blocking-charset-fix';
  private selectorWizardsDoneBodyTemplate: string = '.t3js-upgradeWizards-done-body-template';
  private selectorWizardsDoneRows: string = '.t3js-upgradeWizards-done-rows';
  private selectorWizardsDoneRowTemplate: string = '.t3js-upgradeWizards-done-row-template table tr';
  private selectorWizardsDoneRowMarkUndone: string = '.t3js-upgradeWizards-done-markUndone';
  private selectorWizardsDoneRowTitle: string = '.t3js-upgradeWizards-done-title';
  private selectorWizardsListTemplate: string = '.t3js-upgradeWizards-list-template';
  private selectorWizardsListRows: string = '.t3js-upgradeWizards-list-rows';
  private selectorWizardsListRowTemplate: string = '.t3js-upgradeWizards-list-row-template';
  private selectorWizardsListRowTitle: string = '.t3js-upgradeWizards-list-row-title';
  private selectorWizardsListRowExplanation: string = '.t3js-upgradeWizards-list-row-explanation';
  private selectorWizardsListRowExecute: string = '.t3js-upgradeWizards-list-row-execute';
  private selectorWizardsInputTemplate: string = '.t3js-upgradeWizards-input';
  private selectorWizardsInputTitle: string = '.t3js-upgradeWizards-input-title';
  private selectorWizardsInputDescription: string = '.t3js-upgradeWizards-input-description';
  private selectorWizardsInputHtml: string = '.t3js-upgradeWizards-input-html';
  private selectorWizardsInputPerform: string = '.t3js-upgradeWizards-input-perform';
  private selectorWizardsInputAbort: string = '.t3js-upgradeWizards-input-abort';
  private securityUtility: SecurityUtility;

  constructor() {
    super();
    this.securityUtility = new SecurityUtility();
  }

  private static removeLoadingMessage(container: HTMLElement): void {
    container.querySelector('typo3-install-progress-bar').remove();
  }

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    this.getData().then((): void => {
      this.doneUpgrades();
    });

    // Mark a done wizard undone
    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.markUndone(target.dataset.identifier);
    }).delegateTo(currentModal, this.selectorWizardsDoneRowMarkUndone);

    // Execute "fix default mysql connection db charset" blocking wizard
    new RegularEvent('click', (): void => {
      this.blockingUpgradesDatabaseCharsetFix();
    }).delegateTo(currentModal, this.selectorWizardsBlockingCharsetFix);

    // Execute "add required fields + tables" blocking wizard
    new RegularEvent('click', (): void => {
      this.blockingUpgradesDatabaseAddsExecute();
    }).delegateTo(currentModal, this.selectorWizardsBlockingAddsExecute);

    // Get user input of a single upgrade wizard
    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.wizardInput(target.dataset.identifier, target.dataset.title);
    }).delegateTo(currentModal, this.selectorWizardsListRowExecute);

    // Execute one upgrade wizard
    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.wizardExecute(target.dataset.identifier, target.dataset.title);
    }).delegateTo(currentModal, this.selectorWizardsInputPerform);

    // Abort upgrade wizard
    new RegularEvent('click', (): void => {
      this.findInModal(this.selectorOutputWizardsContainer).innerHTML = '';
      this.wizardsList();
    }).delegateTo(currentModal, this.selectorWizardsInputAbort);
  }

  private getData(): Promise<void> {
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    return (new AjaxRequest(Router.getUrl('upgradeWizardsGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            this.blockingUpgradesDatabaseCharsetTest();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private blockingUpgradesDatabaseCharsetTest(): void {
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);

    this.renderProgressBar(outputContainer, {
      label: 'Checking database charset...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseCharsetTest')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          if (data.success === true) {
            if (data.needsUpdate === true) {
              modalContent.querySelector(this.selectorOutputWizardsContainer)
                .appendChild(modalContent.querySelector(this.selectorWizardsBlockingCharsetTemplate)).cloneNode(true);
            } else {
              this.blockingUpgradesDatabaseAdds();
            }
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private blockingUpgradesDatabaseCharsetFix(): void {
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Setting database charset to UTF-8...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseCharsetFix')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          if (data.success === true) {
            if (Array.isArray(data.status) && data.status.length > 0) {
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            }
          } else {
            UpgradeWizards.removeLoadingMessage(outputContainer);
            outputContainer.append(FlashMessage.create(Severity.error, 'Something went wrong'));
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private blockingUpgradesDatabaseAdds(): void {
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Check for missing mandatory database tables and fields...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseAdds')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardsBlockingDatabaseAddsResponse = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          if (data.success === true) {
            if (data.needsUpdate === true) {
              const adds = modalContent.querySelector(this.selectorWizardsBlockingAddsTemplate).cloneNode(true) as HTMLElement;
              if (typeof (data.adds.tables) === 'object') {
                data.adds.tables.forEach((element): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table);
                  adds.querySelector(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
                });
              }
              if (typeof (data.adds.columns) === 'object') {
                data.adds.columns.forEach((element): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table)
                    + ', Field: ' + this.securityUtility.encodeHtml(element.field);
                  adds.querySelector(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
                });
              }
              if (typeof (data.adds.indexes) === 'object') {
                data.adds.indexes.forEach((element): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table)
                    + ', Index: ' + this.securityUtility.encodeHtml(element.index);
                  adds.querySelector(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
                });
              }
              modalContent.querySelector(this.selectorOutputWizardsContainer).appendChild(adds);
            } else {
              this.wizardsList();
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private blockingUpgradesDatabaseAddsExecute(): void {
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Adding database tables and fields...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseExecute')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          if (Array.isArray(data.status) && data.status.length > 0) {
            data.status.forEach((element: MessageInterface): void => {
              outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
            });
          }
          if (data.success === true) {
            this.wizardsList();
          } else if (!Array.isArray(data.status) || data.status.length === 0) {
            outputContainer.append(FlashMessage.create(Severity.error, 'Something went wrong'));
          } else {
            const toolbar = document.createElement('div');
            toolbar.classList.add('btn-toolbar', 'mt-3', 'mb-4');

            const retryButton = document.createElement('button');
            retryButton.classList.add('btn', 'btn-default');
            retryButton.innerText = 'Retry database migration';

            const proceedButton = document.createElement('button');
            proceedButton.classList.add('btn', 'btn-danger');
            proceedButton.innerText = 'Proceed despite of errors';

            new RegularEvent('click', (): void => {
              this.blockingUpgradesDatabaseAddsExecute();
            }).bindTo(retryButton);

            new RegularEvent('click', (): void => {
              toolbar.remove();
              this.wizardsList();
            }).bindTo(proceedButton);

            toolbar.appendChild(retryButton);
            toolbar.appendChild(proceedButton);
            outputContainer.appendChild(toolbar);
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private wizardsList(): void {
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Loading upgrade wizards...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsList')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          const list = modalContent.querySelector(this.selectorWizardsListTemplate).cloneNode(true) as HTMLElement;
          list.classList.remove('t3js-upgradeWizards-list-template');
          if (data.success === true) {
            let numberOfWizardsTodo = 0;
            let numberOfWizards = 0;
            if (Array.isArray(data.wizards) && data.wizards.length > 0) {
              numberOfWizards = data.wizards.length;
              data.wizards.forEach((element: UpgradeWizard): void => {
                if (element.shouldRenderWizard === true) {
                  const aRow = modalContent.querySelector(this.selectorWizardsListRowTemplate).cloneNode(true) as HTMLElement;
                  numberOfWizardsTodo = numberOfWizardsTodo + 1;
                  aRow.classList.remove('t3js-upgradeWizards-list-row-template');
                  aRow.querySelector<HTMLElement>(this.selectorWizardsListRowTitle).innerText = element.title;
                  aRow.querySelector<HTMLElement>(this.selectorWizardsListRowExplanation).innerText = element.explanation;
                  aRow.querySelector<HTMLElement>(this.selectorWizardsListRowExecute).setAttribute('data-identifier', element.identifier);
                  aRow.querySelector<HTMLElement>(this.selectorWizardsListRowExecute).setAttribute('data-title', element.title);
                  list.querySelector<HTMLElement>(this.selectorWizardsListRows).append(aRow);
                }
              });
            }
            let percent: number = 100;
            const progressBar = list.querySelector<HTMLElement>('.progress-bar');
            if (numberOfWizardsTodo > 0) {
              percent = Math.round((numberOfWizards - numberOfWizardsTodo) / data.wizards.length * 100);
            } else {
              progressBar.classList.remove('progress-bar-info');
              progressBar.classList.add('progress-bar-success');
            }
            progressBar.classList.remove('progress-bar-striped');
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', String(percent));
            progressBar.querySelector('span').innerText = percent + '%';
            modalContent.querySelector(this.selectorOutputWizardsContainer).appendChild(list);
            (this.findInModal(this.selectorWizardsDoneRowMarkUndone) as HTMLInputElement).disabled = false;
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private wizardInput(identifier: string, title: string): void {
    const executeToken = this.getModuleContent().dataset.upgradeWizardsInputToken;
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Loading "' + title + '"...'
    });

    modalContent.animate(
      {
        scrollTop: modalContent.scrollTop - Math.abs(modalContent.querySelector('.t3js-upgrade-status-section').getBoundingClientRect().top),
      },
      250,
    );

    (new AjaxRequest(Router.getUrl('upgradeWizardsInput')))
      .post({
        install: {
          action: 'upgradeWizardsInput',
          token: executeToken,
          identifier: identifier,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          outputContainer.innerHTML = '';
          const input = modalContent.querySelector(this.selectorWizardsInputTemplate).cloneNode(true) as HTMLElement;
          input.classList.remove('t3js-upgradeWizards-input');
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(FlashMessage.create(element.severity, element.title, element.message));
              });
            }
            if (data.userInput.wizardHtml.length > 0) {
              input.querySelector<HTMLElement>(this.selectorWizardsInputHtml).innerHTML = data.userInput.wizardHtml;
            }
            input.querySelector<HTMLElement>(this.selectorWizardsInputTitle).innerText = data.userInput.title;
            input.querySelector<HTMLElement>(this.selectorWizardsInputDescription).innerHTML = this.securityUtility.stripHtml(data.userInput.description).replace(/\n/g, '<br>');
            const selectorWizardsInputPerform = input.querySelector<HTMLElement>(this.selectorWizardsInputPerform);
            selectorWizardsInputPerform.setAttribute('data-identifier', data.userInput.identifier);
            selectorWizardsInputPerform.setAttribute('data-title', data.userInput.title);
          }
          modalContent.querySelector(this.selectorOutputWizardsContainer).appendChild(input);
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private wizardExecute(identifier: string, title: string): void {
    const executeToken = this.getModuleContent().dataset.upgradeWizardsExecuteToken;
    const modalContent = this.getModalBody();
    const postData: Record<string, string> = {
      'install[action]': 'upgradeWizardsExecute',
      'install[token]': executeToken,
      'install[identifier]': identifier,
    };
    const formData = new FormData(this.findInModal(this.selectorOutputWizardsContainer + ' form') as HTMLFormElement);
    for (const [name, value] of formData) {
      postData[name] = value.toString();
    }
    const outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Executing "' + title + '"...'
    });
    (this.findInModal(this.selectorWizardsDoneRowMarkUndone) as HTMLInputElement).disabled = true;
    (new AjaxRequest(Router.getUrl()))
      .post(postData)
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          outputContainer.innerHTML = '';
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            }
            this.wizardsList();
            modalContent.querySelector(this.selectorOutputDoneContainer).innerHTML = '';
            this.doneUpgrades();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private doneUpgrades(): void {
    const modalContent = this.getModalBody();
    const outputContainer = modalContent.querySelector<HTMLElement>(this.selectorOutputDoneContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Loading executed upgrade wizards...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsDoneUpgrades')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          if (data.success === true) {
            if (Array.isArray(data.status) && data.status.length > 0) {
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            }
            const body = modalContent.querySelector(this.selectorWizardsDoneBodyTemplate).cloneNode(true) as HTMLElement;
            const wizardsDoneContainer = body.querySelector(this.selectorWizardsDoneRows);
            let hasBodyContent: boolean = false;
            if (Array.isArray(data.wizardsDone) && data.wizardsDone.length > 0) {
              data.wizardsDone.forEach((element: UpgradeWizardDone): void => {
                hasBodyContent = true;
                const aRow = modalContent.querySelector(this.selectorWizardsDoneRowTemplate).cloneNode(true) as HTMLElement;
                aRow.querySelector(this.selectorWizardsDoneRowMarkUndone).setAttribute('data-identifier', element.identifier);
                aRow.querySelector<HTMLElement>(this.selectorWizardsDoneRowTitle).innerText = element.title;
                wizardsDoneContainer.appendChild(aRow);
              });
            }
            if (Array.isArray(data.rowUpdatersDone) && data.rowUpdatersDone.length > 0) {
              data.rowUpdatersDone.forEach((element: UpgradeWizardDone): void => {
                hasBodyContent = true;
                const aRow = modalContent.querySelector(this.selectorWizardsDoneRowTemplate).cloneNode(true) as HTMLElement;
                aRow.querySelector(this.selectorWizardsDoneRowMarkUndone).setAttribute('data-identifier', element.identifier);
                aRow.querySelector<HTMLElement>(this.selectorWizardsDoneRowTitle).innerText = element.title;
                wizardsDoneContainer.appendChild(aRow);
              });
            }
            if (hasBodyContent) {
              modalContent.querySelector(this.selectorOutputDoneContainer).appendChild(body);
              (this.findInModal(this.selectorWizardsDoneRowMarkUndone) as HTMLInputElement).disabled = true;
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private markUndone(identifier: string): void {
    const executeToken = this.getModuleContent().dataset.upgradeWizardsMarkUndoneToken;
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(this.selectorOutputDoneContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Marking upgrade wizard as undone...'
    });
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'upgradeWizardsMarkUndone',
          token: executeToken,
          identifier: identifier,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          outputContainer.innerHTML = '';
          modalContent.querySelector(this.selectorOutputDoneContainer).innerHTML = '';
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              Notification.success(element.title, element.message);
              this.doneUpgrades();
              this.blockingUpgradesDatabaseCharsetTest();
            });
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }
}

export default new UpgradeWizards();
