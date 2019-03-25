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

import {InteractableModuleInterface} from './InteractableModuleInterface';
import * as $ from 'jquery';
import 'bootstrap';
import Router = require('../Router');
import Severity = require('../Renderable/Severity');
import ProgressBar = require('../Renderable/ProgressBar');
import InfoBox = require('../Renderable/InfoBox');
import FlashMessage = require('../Renderable/FlashMessage');
import Notification = require('TYPO3/CMS/Backend/Notification');
import SecurityUtility = require('TYPO3/CMS/Core/SecurityUtility');

/**
 * Module: TYPO3/CMS/Install/Module/UpgradeWizards
 */
class UpgradeWizards implements InteractableModuleInterface {
  private selectorModalBody: string = '.t3js-modal-body';
  private selectorModuleContent: string = '.t3js-module-content';
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
  private currentModal: JQuery;
  private securityUtility: SecurityUtility;

  constructor() {
    this.securityUtility = new SecurityUtility();
  }

  private static removeLoadingMessage($container: JQuery): void {
    $container.find('.alert-loading').remove();
  }

  private static renderProgressBar(title: string): any {
    return ProgressBar.render(Severity.loading, title, '');
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
    currentModal.on('click', this.selectorWizardsBlockingCharsetFix, (e: JQueryEventObject): void => {
      this.blockingUpgradesDatabaseCharsetFix();
    });

    // Execute "add required fields + tables" blocking wizard
    currentModal.on('click', this.selectorWizardsBlockingAddsExecute, (e: JQueryEventObject): void => {
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

  private getData(): JQueryPromise<any> {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    return $.ajax({
      url: Router.getUrl('upgradeWizardsGetData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          modalContent.empty().append(data.html);
          this.blockingUpgradesDatabaseCharsetTest();
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr);
      },
    });
  }

  private blockingUpgradesDatabaseCharsetTest(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Checking database charset...'));
    $.ajax({
      url: Router.getUrl('upgradeWizardsBlockingDatabaseCharsetTest'),
      cache: false,
      success: (data: any): void => {
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
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private blockingUpgradesDatabaseCharsetFix(): void {
    const $outputContainer = $(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Setting database charset to UTF-8...'));
    $.ajax({
      url: Router.getUrl('upgradeWizardsBlockingDatabaseCharsetFix'),
      cache: false,
      success: (data: any): void => {
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
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private blockingUpgradesDatabaseAdds(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Check for missing mandatory database tables and fields...'));
    $.ajax({
      url: Router.getUrl('upgradeWizardsBlockingDatabaseAdds'),
      cache: false,
      success: (data: any): void => {
        UpgradeWizards.removeLoadingMessage($outputContainer);
        if (data.success === true) {
          if (data.needsUpdate === true) {
            const adds = modalContent.find(this.selectorWizardsBlockingAddsTemplate).clone();
            if (typeof(data.adds.tables) === 'object') {
              data.adds.tables.forEach((element: any): void => {
                const title = 'Table: ' + this.securityUtility.encodeHtml(element.table);
                adds.find(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
              });
            }
            if (typeof(data.adds.columns) === 'object') {
              data.adds.columns.forEach((element: any): void => {
                const title = 'Table: ' + this.securityUtility.encodeHtml(element.table)
                  + ', Field: ' + this.securityUtility.encodeHtml(element.field);
                adds.find(this.selectorWizardsBlockingAddsRows).append(title, '<br>');
              });
            }
            if (typeof(data.adds.indexes) === 'object') {
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
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private blockingUpgradesDatabaseAddsExecute(): void {
    const $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Adding database tables and fields...'));
    $.ajax({
      url: Router.getUrl('upgradeWizardsBlockingDatabaseExecute'),
      cache: false,
      success: (data: any): void => {
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
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private wizardsList(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
    $outputContainer.append(UpgradeWizards.renderProgressBar('Loading upgrade wizards...'));

    $.ajax({
      url: Router.getUrl('upgradeWizardsList'),
      cache: false,
      success: (data: any): void => {
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
          this.currentModal.find(this.selectorWizardsDoneRowMarkUndone).prop('disabled', false);
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private wizardInput(identifier: string, title: string): void {
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-wizards-input-token');
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Loading "' + title + '"...'));

    modalContent.animate(
      {
        scrollTop: modalContent.scrollTop() - Math.abs(modalContent.find('.t3js-upgrade-status-section').position().top),
      },
      250,
    );

    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      data: {
        'install': {
          'action': 'upgradeWizardsInput',
          'token': executeToken,
          'identifier': identifier,
        },
      },
      cache: false,
      success: (data: any): void => {
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
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private wizardExecute(identifier: string, title: string): void {
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-wizards-execute-token');
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const postData: any = {
      'install[action]': 'upgradeWizardsExecute',
      'install[token]': executeToken,
      'install[identifier]': identifier,
    };
    $(this.currentModal.find(this.selectorOutputWizardsContainer + ' form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    const $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
    // modalContent.find(this.selectorOutputWizardsContainer).empty();
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Executing "' + title + '"...'));
    this.currentModal.find(this.selectorWizardsDoneRowMarkUndone).prop('disabled', true);
    $.ajax({
      method: 'POST',
      data: postData,
      url: Router.getUrl(),
      cache: false,
      success: (data: any): void => {
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
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private doneUpgrades(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = modalContent.find(this.selectorOutputDoneContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Loading executed upgrade wizards...'));

    $.ajax({
      url: Router.getUrl('upgradeWizardsDoneUpgrades'),
      cache: false,
      success: (data: any): void => {
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
            this.currentModal.find(this.selectorWizardsDoneRowMarkUndone).prop('disabled', true);
          }
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }

  private markUndone(identifier: string): void {
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-wizards-mark-undone-token');
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputDoneContainer);
    $outputContainer.empty().html(UpgradeWizards.renderProgressBar('Marking upgrade wizard as undone...'));
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      data: {
        'install': {
          'action': 'upgradeWizardsMarkUndone',
          'token': executeToken,
          'identifier': identifier,
        },
      },
      cache: false,
      success: (data: any): void => {
        $outputContainer.empty();
        modalContent.find(this.selectorOutputDoneContainer).empty();
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            Notification.success(element.message);
            this.doneUpgrades();
            this.blockingUpgradesDatabaseCharsetTest();
          });
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, $outputContainer);
      },
    });
  }
}

export = new UpgradeWizards();
