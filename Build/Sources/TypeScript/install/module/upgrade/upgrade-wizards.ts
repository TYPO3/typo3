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
import { AbstractInteractableModule, ModuleLoadedResponse } from '../abstract-interactable-module';
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
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';

enum Identifiers {
  outputWizardsContainer = '.t3js-upgradeWizards-wizards-output',
  outputMessagesContainer = '.t3js-upgradeWizards-wizards-messages-output',
  outputDoneContainer = '.t3js-upgradeWizards-done-output',
  wizardsBlockingAddsTemplate = '#t3js-upgradeWizards-blocking-adds-template',
  wizardsBlockingAddsRows = '.t3js-upgradeWizards-blocking-adds-rows',
  wizardsBlockingAddsExecute = '.t3js-upgradeWizards-blocking-adds-execute',
  wizardsBlockingCharsetTemplate = '#t3js-upgradeWizards-blocking-charset-template',
  wizardsBlockingCharsetFix = '.t3js-upgradeWizards-blocking-charset-fix',
  wizardsDoneBodyTemplate = '#t3js-upgradeWizards-done-body-template',
  wizardsDoneRows = '.t3js-upgradeWizards-done-rows',
  wizardsDoneRowTemplate = '#t3js-upgradeWizards-done-row-template',
  wizardsDoneRowMarkUndone = '.t3js-upgradeWizards-done-markUndone',
  wizardsDoneRowTitle = '.t3js-upgradeWizards-done-title',
  wizardsListTemplate = '#t3js-upgradeWizards-list-template',
  wizardsListRows = '.t3js-upgradeWizards-list-rows',
  wizardsListRowTemplate = '#t3js-upgradeWizards-list-row-template',
  wizardsListRowTitle = '.t3js-upgradeWizards-list-row-title',
  wizardsListRowExplanation = '.t3js-upgradeWizards-list-row-explanation',
  wizardsListRowExecute = '.t3js-upgradeWizards-list-row-execute',
  wizardsInputTemplate = '#t3js-upgradeWizards-input',
  wizardsInputTitle = '.t3js-upgradeWizards-input-title',
  wizardsInputDescription = '.t3js-upgradeWizards-input-description',
  wizardsInputHtml = '.t3js-upgradeWizards-input-html',
  wizardsInputPerform = '.t3js-upgradeWizards-input-perform',
  wizardsInputAbort = '.t3js-upgradeWizards-input-abort'
}

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

type UpgradeWizardBlockingDatabaseCharsetTestResponse = {
  needsUpdate: boolean;
  success: boolean;
}

type UpgradeWizardBlockingDatabaseCharsetFixResponse = {
  status: MessageInterface[];
  success: boolean;
}

type UpgradeWizardsBlockingDatabaseExecuteResponse = {
  status: MessageInterface[];
  success: boolean;
}

type UpgradeWizardsListResponse = {
  status: MessageInterface[];
  success: boolean;
  wizards: UpgradeWizard[]
}

type UpgradeWizardsInputResponse = {
  status: MessageInterface[];
  success: boolean;
  userInput: {
    identifier: string;
    title: string;
    description: string;
    wizardHtml: string;
  }
}

type UpgradeWizardsExecuteResponse = {
  status: MessageInterface[];
  success: boolean;
};

type UpgradeWizardsDoneUpgradesResponse = {
  status: MessageInterface[];
  success: boolean;
  wizardsDone: UpgradeWizardDone[];
  rowUpdatersDone: UpgradeWizardDone[];
};

type UpgradeWizardsMarkUndoneResponse = {
  status: MessageInterface[];
  success: boolean;
}

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
  private readonly securityUtility: SecurityUtility;

  constructor() {
    super();
    this.securityUtility = new SecurityUtility();
  }

  private static removeLoadingMessage(container: HTMLElement): void {
    container.querySelectorAll('typo3-backend-progress-bar').forEach((progressBar: ProgressBarElement): void => progressBar.remove());
  }

  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    Promise.all([
      this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js'),
      this.loadModuleFrameAgnostic('@typo3/install/renderable/flash-message.js')
    ]).then(async (): Promise<void> => {
      await this.getData();
      this.doneUpgrades();
    });

    // Mark a done wizard undone
    new RegularEvent('click', (event: Event, target: HTMLInputElement): void => {
      target.disabled = true;

      this.markUndone(target.dataset.identifier);
    }).delegateTo(currentModal, Identifiers.wizardsDoneRowMarkUndone);

    // Execute "fix default mysql connection db charset" blocking wizard
    new RegularEvent('click', (): void => {
      this.blockingUpgradesDatabaseCharsetFix();
    }).delegateTo(currentModal, Identifiers.wizardsBlockingCharsetFix);

    // Execute "add required fields + tables" blocking wizard
    new RegularEvent('click', (): void => {
      this.blockingUpgradesDatabaseAddsExecute();
    }).delegateTo(currentModal, Identifiers.wizardsBlockingAddsExecute);

    // Get user input of a single upgrade wizard
    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.wizardInput(target.dataset.identifier, target.dataset.title);
    }).delegateTo(currentModal, Identifiers.wizardsListRowExecute);

    // Execute one upgrade wizard
    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.wizardExecute(target.dataset.identifier, target.dataset.title);
    }).delegateTo(currentModal, Identifiers.wizardsInputPerform);

    // Abort upgrade wizard
    new RegularEvent('click', (): void => {
      this.findInModal(Identifiers.outputWizardsContainer).innerHTML = '';
      this.wizardsList();
    }).delegateTo(currentModal, Identifiers.wizardsInputAbort);
  }

  private getData(): Promise<void> {
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);
    return (new AjaxRequest(Router.getUrl('upgradeWizardsGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponse = await response.resolve();
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
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);

    this.renderProgressBar(outputContainer, {
      label: 'Checking database charset...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseCharsetTest')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardBlockingDatabaseCharsetTestResponse = await response.resolve();
          if (data.success === true) {
            if (data.needsUpdate === true) {
              UpgradeWizards.removeLoadingMessage(outputContainer);
              modalContent.querySelector(Identifiers.outputWizardsContainer)
                .appendChild((modalContent.querySelector(Identifiers.wizardsBlockingCharsetTemplate) as HTMLTemplateElement).content.cloneNode(true));
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
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Setting database charset to UTF-8...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseCharsetFix')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardBlockingDatabaseCharsetFixResponse = await response.resolve();
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
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Check for missing mandatory database tables and fields...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseAdds')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardsBlockingDatabaseAddsResponse = await response.resolve();
          if (data.success === true) {
            if (data.needsUpdate === true) {
              const adds = (modalContent.querySelector(Identifiers.wizardsBlockingAddsTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
              if (typeof (data.adds.tables) === 'object') {
                data.adds.tables.forEach((element): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table);
                  adds.querySelector(Identifiers.wizardsBlockingAddsRows).append(title, document.createElement('br'));
                });
              }
              if (typeof (data.adds.columns) === 'object') {
                data.adds.columns.forEach((element): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table)
                    + ', Field: ' + this.securityUtility.encodeHtml(element.field);
                  adds.querySelector(Identifiers.wizardsBlockingAddsRows).append(title, document.createElement('br'));
                });
              }
              if (typeof (data.adds.indexes) === 'object') {
                data.adds.indexes.forEach((element): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table)
                    + ', Index: ' + this.securityUtility.encodeHtml(element.index);
                  adds.querySelector(Identifiers.wizardsBlockingAddsRows).append(title, document.createElement('br'));
                });
              }
              modalContent.querySelector(Identifiers.outputWizardsContainer).appendChild(adds);
            } else {
              this.wizardsList();
            }
          } else {
            UpgradeWizards.removeLoadingMessage(outputContainer);
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, outputContainer);
        }
      );
  }

  private blockingUpgradesDatabaseAddsExecute(): void {
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Adding database tables and fields...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseExecute')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardsBlockingDatabaseExecuteResponse = await response.resolve();
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
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Loading upgrade wizards...',
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsList')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardsListResponse = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          const list = (modalContent.querySelector(Identifiers.wizardsListTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
          if (data.success === true) {
            let numberOfWizardsTodo = 0;
            let numberOfWizards = 0;
            if (Array.isArray(data.wizards) && data.wizards.length > 0) {
              numberOfWizards = data.wizards.length;
              data.wizards.forEach((element): void => {
                if (element.shouldRenderWizard === true) {
                  const aRow = (modalContent.querySelector(Identifiers.wizardsListRowTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
                  numberOfWizardsTodo = numberOfWizardsTodo + 1;
                  aRow.querySelector<HTMLElement>(Identifiers.wizardsListRowTitle).innerText = element.title;
                  aRow.querySelector<HTMLElement>(Identifiers.wizardsListRowExplanation).innerText = element.explanation;
                  aRow.querySelector<HTMLElement>(Identifiers.wizardsListRowExecute).setAttribute('data-identifier', element.identifier);
                  aRow.querySelector<HTMLElement>(Identifiers.wizardsListRowExecute).setAttribute('data-title', element.title);
                  list.querySelector<HTMLElement>(Identifiers.wizardsListRows).append(aRow);
                }
              });
            }
            let percent: number = 100;
            const upgradeWizardProgress = list.querySelector('typo3-backend-progress-bar');
            if (numberOfWizardsTodo > 0) {
              percent = Math.round((numberOfWizards - numberOfWizardsTodo) / data.wizards.length * 100);
            } else {
              upgradeWizardProgress.severity = Severity.ok;
            }
            upgradeWizardProgress.value = percent;
            upgradeWizardProgress.label = `${numberOfWizards - numberOfWizardsTodo} of ${numberOfWizards} upgrade wizards executed`;
            modalContent.querySelector(Identifiers.outputWizardsContainer).appendChild(list);
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
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);
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
          const data: UpgradeWizardsInputResponse = await response.resolve();
          outputContainer.innerHTML = '';
          const input = (modalContent.querySelector(Identifiers.wizardsInputTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(FlashMessage.create(element.severity, element.title, element.message));
              });
            }
            if (data.userInput.wizardHtml.length > 0) {
              input.querySelector<HTMLElement>(Identifiers.wizardsInputHtml).innerHTML = data.userInput.wizardHtml;
            }
            input.querySelector<HTMLElement>(Identifiers.wizardsInputTitle).innerText = data.userInput.title;
            input.querySelector<HTMLElement>(Identifiers.wizardsInputDescription).innerHTML = this.securityUtility.stripHtml(data.userInput.description).replace(/\n/g, '<br>');
            const selectorWizardsInputPerform = input.querySelector<HTMLElement>(Identifiers.wizardsInputPerform);
            selectorWizardsInputPerform.setAttribute('data-identifier', data.userInput.identifier);
            selectorWizardsInputPerform.setAttribute('data-title', data.userInput.title);
          }
          modalContent.querySelector(Identifiers.outputWizardsContainer).appendChild(input);
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
    const formData = new FormData(this.findInModal(Identifiers.outputWizardsContainer + ' form') as HTMLFormElement);
    for (const [name, value] of formData) {
      postData[name] = value.toString();
    }
    const outputContainer = this.findInModal(Identifiers.outputWizardsContainer);
    const messagesContainer = this.findInModal(Identifiers.outputMessagesContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Executing "' + title + '"...'
    });
    (new AjaxRequest(Router.getUrl()))
      .post(postData)
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardsExecuteResponse = await response.resolve();
          messagesContainer.replaceChildren();

          if (data.success === true) {
            if (Array.isArray(data.status)) {
              const messages: InfoBox[] = [];
              data.status.forEach((element: MessageInterface): void => {
                messages.push(InfoBox.create(element.severity, element.title, element.message));
              });
              messagesContainer.append(...messages);
            }
            this.wizardsList();
            modalContent.querySelector(Identifiers.outputDoneContainer).innerHTML = '';
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
    const outputContainer = modalContent.querySelector<HTMLElement>(Identifiers.outputDoneContainer);
    this.renderProgressBar(outputContainer, {
      label: 'Loading executed upgrade wizards...'
    });
    (new AjaxRequest(Router.getUrl('upgradeWizardsDoneUpgrades')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: UpgradeWizardsDoneUpgradesResponse = await response.resolve();
          UpgradeWizards.removeLoadingMessage(outputContainer);
          if (data.success === true) {
            if (Array.isArray(data.status) && data.status.length > 0) {
              data.status.forEach((element: MessageInterface): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            }
            const body = (modalContent.querySelector(Identifiers.wizardsDoneBodyTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
            const wizardsDoneContainer = body.querySelector(Identifiers.wizardsDoneRows);
            let hasBodyContent: boolean = false;
            if (Array.isArray(data.wizardsDone) && data.wizardsDone.length > 0) {
              data.wizardsDone.forEach((element): void => {
                hasBodyContent = true;
                const aRow = (modalContent.querySelector(Identifiers.wizardsDoneRowTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
                aRow.querySelector(Identifiers.wizardsDoneRowMarkUndone).setAttribute('data-identifier', element.identifier);
                aRow.querySelector<HTMLElement>(Identifiers.wizardsDoneRowTitle).innerText = element.title;
                wizardsDoneContainer.appendChild(aRow);
              });
            }
            if (Array.isArray(data.rowUpdatersDone) && data.rowUpdatersDone.length > 0) {
              data.rowUpdatersDone.forEach((element): void => {
                hasBodyContent = true;
                const aRow = (modalContent.querySelector(Identifiers.wizardsDoneRowTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
                aRow.querySelector(Identifiers.wizardsDoneRowMarkUndone).setAttribute('data-identifier', element.identifier);
                aRow.querySelector<HTMLElement>(Identifiers.wizardsDoneRowTitle).innerText = element.title;
                wizardsDoneContainer.appendChild(aRow);
              });
            }
            if (hasBodyContent) {
              modalContent.querySelector(Identifiers.outputDoneContainer).appendChild(body);
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
    const messagesContainer = this.findInModal(Identifiers.outputMessagesContainer);
    const outputContainer = this.findInModal(Identifiers.outputDoneContainer);
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
          const data: UpgradeWizardsMarkUndoneResponse = await response.resolve();
          messagesContainer.replaceChildren();
          outputContainer.replaceChildren();
          modalContent.querySelector(Identifiers.outputDoneContainer).replaceChildren();

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
