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
import $ from 'jquery';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ResponseError} from 'TYPO3/CMS/Core/Ajax/ResponseError';
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Notification = require('TYPO3/CMS/Backend/Notification');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import SecurityUtility = require('TYPO3/CMS/Core/SecurityUtility');
import FlashMessage = require('../../Renderable/FlashMessage');
import InfoBox = require('../../Renderable/InfoBox');
import ProgressBar = require('../../Renderable/ProgressBar');
import Severity = require('../../Renderable/Severity');
import Router = require('../../Router');

/**
 * Module: TYPO3/CMS/Install/Module/UpgradeWizards
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
  private selectorWizardsInputHtml: string = '.t3js-upgradeWizards-input-html';
  private selectorWizardsInputPerform: string = '.t3js-upgradeWizards-input-perform';
  private securityUtility: SecurityUtility;

  private static removeLoadingMessage($container: JQuery): void {
    $container.find('.alert-loading').remove();
  }

  private static renderProgressBar(title: string): any {
    return ProgressBar.render(Severity.loading, title, '');
  }

  constructor() {
    super();
    this.securityUtility = new SecurityUtility();
  }

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;

    this.getData().done((): void => {
      this.doneUpgrades();
    });

    // Mark a done wizard undone
    currentModal.on('click', this.selectorWizardsDoneRowMarkUndone, (e: JQueryEventObject): void => {
      this.markUndone((<HTMLElement>e.target).dataset.identifier);
    });

    // Execute "fix default mysql connection db charset" blocking wizard
    currentModal.on('click', this.selectorWizardsBlockingCharsetFix, (): void => {
      this.blockingUpgradesDatabaseCharsetFix();
    });

    // Execute "add required fields + tables" blocking wizard
    currentModal.on('click', this.selectorWizardsBlockingAddsExecute, (): void => {
      this.blockingUpgradesDatabaseAddsExecute();
    });

    // Get user input of a single upgrade wizard
    currentModal.on('click', this.selectorWizardsListRowExecute, (e: JQueryEventObject): void => {
      this.wizardInput((<HTMLElement>e.target).dataset.identifier, (<HTMLElement>e.target).dataset.title);
    });

    // Execute one upgrade wizard
    currentModal.on('click', this.selectorWizardsInputPerform, (e: JQueryEventObject): void => {
      this.wizardExecute((<HTMLElement>e.target).dataset.identifier, (<HTMLElement>e.target).dataset.title);
    });
  }

  private getData(): Promise<any> {
    const modalContent = this.getModalBody();
    return (new AjaxRequest(Router.getUrl('upgradeWizardsGetData')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            this.blockingUpgradesDatabaseCharsetTest();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error);
        }
      );
  }

  private blockingUpgradesDatabaseCharsetTest(): void {
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Checking database charset...'));
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseCharsetTest')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage($outputContainer);
          if (data.success === true) {
            if (data.needsUpdate === true) {
              modalContent.find(this.selectorOutputWizardsContainer)
                .append(modalContent.find(this.selectorWizardsBlockingCharsetTemplate)).clone();
            } else {
              this.blockingUpgradesDatabaseAdds();
            }
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, $outputContainer);
        }
      );
  }

  private blockingUpgradesDatabaseCharsetFix(): void {
    const $outputContainer = $(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Setting database charset to UTF-8...'));
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseCharsetFix')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage($outputContainer);
          if (data.success === true) {
            if (Array.isArray(data.status) && data.status.length > 0) {
              data.status.forEach((element: any): void => {
                const message: any = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
              });
            }
          } else {
            const message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            UpgradeWizards.removeLoadingMessage($outputContainer);
            $outputContainer.append(message);
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, $outputContainer);
        }
      );
  }

  private blockingUpgradesDatabaseAdds(): void {
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Check for missing mandatory database tables and fields...'));
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseAdds')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage($outputContainer);
          if (data.success === true) {
            if (data.needsUpdate === true) {
              const adds = modalContent.find(this.selectorWizardsBlockingAddsTemplate).clone();
              if (typeof (data.adds.tables) === 'object') {
                data.adds.tables.forEach((element: any): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table);
                  adds.find(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
                });
              }
              if (typeof (data.adds.columns) === 'object') {
                data.adds.columns.forEach((element: any): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table)
                    + ', Field: ' + this.securityUtility.encodeHtml(element.field);
                  adds.find(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
                });
              }
              if (typeof (data.adds.indexes) === 'object') {
                data.adds.indexes.forEach((element: any): void => {
                  const title = 'Table: ' + this.securityUtility.encodeHtml(element.table)
                    + ', Index: ' + this.securityUtility.encodeHtml(element.index);
                  adds.find(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
                });
              }
              modalContent.find(this.selectorOutputWizardsContainer).append(adds);
            } else {
              this.wizardsList();
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error);
        }
      );
  }

  private blockingUpgradesDatabaseAddsExecute(): void {
    const $outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Adding database tables and fields...'));
    (new AjaxRequest(Router.getUrl('upgradeWizardsBlockingDatabaseExecute')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage($outputContainer);
          if (data.success === true) {
            if (Array.isArray(data.status) && data.status.length > 0) {
              data.status.forEach((element: any): void => {
                const message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
              });
              this.wizardsList();
            }
          } else {
            const message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            UpgradeWizards.removeLoadingMessage($outputContainer);
            $outputContainer.append(message);
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, $outputContainer);
        }
      );
  }

  private wizardsList(): void {
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    $outputContainer.append(UpgradeWizards.renderProgressBar('Loading upgrade wizards...'));
    (new AjaxRequest(Router.getUrl('upgradeWizardsList')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage($outputContainer);
          const list = modalContent.find(this.selectorWizardsListTemplate).clone();
          list.removeClass('t3js-upgradeWizards-list-template');
          if (data.success === true) {
            let numberOfWizardsTodo = 0;
            let numberOfWizards = 0;
            if (Array.isArray(data.wizards) && data.wizards.length > 0) {
              numberOfWizards = data.wizards.length;
              data.wizards.forEach((element: any): void => {
                if (element.shouldRenderWizard === true) {
                  const aRow = modalContent.find(this.selectorWizardsListRowTemplate).clone();
                  numberOfWizardsTodo = numberOfWizardsTodo + 1;
                  aRow.removeClass('t3js-upgradeWizards-list-row-template');
                  aRow.find(this.selectorWizardsListRowTitle).empty().text(element.title);
                  aRow.find(this.selectorWizardsListRowExplanation).empty().text(element.explanation);
                  aRow.find(this.selectorWizardsListRowExecute).attr('data-identifier', element.identifier).attr('data-title', element.title);
                  list.find(this.selectorWizardsListRows).append(aRow);
                }
              });
              list.find(this.selectorWizardsListRows + ' hr:last').remove();
            }
            let percent: number = 100;
            const $progressBar = list.find('.progress-bar');
            if (numberOfWizardsTodo > 0) {
              percent = Math.round((numberOfWizards - numberOfWizardsTodo) / data.wizards.length * 100);
            } else {
              $progressBar
                .removeClass('progress-bar-info')
                .addClass('progress-bar-success');
            }
            $progressBar
              .removeClass('progress-bar-striped')
              .css('width', percent + '%')
              .attr('aria-valuenow', percent)
              .find('span')
              .text(percent + '%');
            modalContent.find(this.selectorOutputWizardsContainer).append(list);
            this.findInModal(this.selectorWizardsDoneRowMarkUndone).prop('disabled', false);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error);
        }
      );
  }

  private wizardInput(identifier: string, title: string): void {
    const executeToken = this.getModuleContent().data('upgrade-wizards-input-token');
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Loading "' + title + '"...'));

    modalContent.animate(
      {
        scrollTop: modalContent.scrollTop() - Math.abs(modalContent.find('.t3js-upgrade-status-section').position().top),
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
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          $outputContainer.empty();
          const input = modalContent.find(this.selectorWizardsInputTemplate).clone();
          input.removeClass('t3js-upgradeWizards-input');
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach((element: any): void => {
                const message = FlashMessage.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
              });
            }
            if (data.userInput.wizardHtml.length > 0) {
              input.find(this.selectorWizardsInputHtml).html(data.userInput.wizardHtml);
            }
            input.find(this.selectorWizardsInputTitle).text(data.userInput.title);
            input.find(this.selectorWizardsInputPerform)
              .attr('data-identifier', data.userInput.identifier)
              .attr('data-title', data.userInput.title);
          }
          modalContent.find(this.selectorOutputWizardsContainer).append(input);
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, $outputContainer);
        }
      );
  }

  private wizardExecute(identifier: string, title: string): void {
    const executeToken = this.getModuleContent().data('upgrade-wizards-execute-token');
    const modalContent = this.getModalBody();
    const postData: any = {
      'install[action]': 'upgradeWizardsExecute',
      'install[token]': executeToken,
      'install[identifier]': identifier,
    };
    $(this.findInModal(this.selectorOutputWizardsContainer + ' form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    const $outputContainer = this.findInModal(this.selectorOutputWizardsContainer);
    // modalContent.find(this.selectorOutputWizardsContainer).empty();
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Executing "' + title + '"...'));
    this.findInModal(this.selectorWizardsDoneRowMarkUndone).prop('disabled', true);
    (new AjaxRequest(Router.getUrl()))
      .post(postData)
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          $outputContainer.empty();
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach((element: any): void => {
                const message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
              });
            }
            this.wizardsList();
            modalContent.find(this.selectorOutputDoneContainer).empty();
            this.doneUpgrades();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, $outputContainer);
        }
      );
  }

  private doneUpgrades(): void {
    const modalContent = this.getModalBody();
    const $outputContainer = modalContent.find(this.selectorOutputDoneContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Loading executed upgrade wizards...'));
    (new AjaxRequest(Router.getUrl('upgradeWizardsDoneUpgrades')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          UpgradeWizards.removeLoadingMessage($outputContainer);
          if (data.success === true) {
            if (Array.isArray(data.status) && data.status.length > 0) {
              data.status.forEach((element: any): void => {
                const message = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(message);
              });
            }
            const body = modalContent.find(this.selectorWizardsDoneBodyTemplate).clone();
            const $wizardsDoneContainer = body.find(this.selectorWizardsDoneRows);
            let hasBodyContent: boolean = false;
            if (Array.isArray(data.wizardsDone) && data.wizardsDone.length > 0) {
              data.wizardsDone.forEach((element: any): void => {
                hasBodyContent = true;
                const aRow = modalContent.find(this.selectorWizardsDoneRowTemplate).clone();
                aRow.find(this.selectorWizardsDoneRowMarkUndone).attr('data-identifier', element.identifier);
                aRow.find(this.selectorWizardsDoneRowTitle).text(element.title);
                $wizardsDoneContainer.append(aRow);
              });
            }
            if (Array.isArray(data.rowUpdatersDone) && data.rowUpdatersDone.length > 0) {
              data.rowUpdatersDone.forEach((element: any): void => {
                hasBodyContent = true;
                const aRow = modalContent.find(this.selectorWizardsDoneRowTemplate).clone();
                aRow.find(this.selectorWizardsDoneRowMarkUndone).attr('data-identifier', element.identifier);
                aRow.find(this.selectorWizardsDoneRowTitle).text(element.title);
                $wizardsDoneContainer.append(aRow);
              });
            }
            if (hasBodyContent) {
              modalContent.find(this.selectorOutputDoneContainer).append(body);
              this.findInModal(this.selectorWizardsDoneRowMarkUndone).prop('disabled', true);
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, $outputContainer);
        }
      );
  }

  private markUndone(identifier: string): void {
    const executeToken = this.getModuleContent().data('upgrade-wizards-mark-undone-token');
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputDoneContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Marking upgrade wizard as undone...'));
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'upgradeWizardsMarkUndone',
          token: executeToken,
          identifier: identifier,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          $outputContainer.empty();
          modalContent.find(this.selectorOutputDoneContainer).empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              Notification.success(element.title, element.message);
              this.doneUpgrades();
              this.blockingUpgradesDatabaseCharsetTest();
            });
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, $outputContainer);
        }
      );
  }
}

export = new UpgradeWizards();
