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
 * Module: TYPO3/CMS/Install/UpgradeWizards
 */
define([
    'jquery',
    'TYPO3/CMS/Install/Router',
    'TYPO3/CMS/Install/FlashMessage',
    'TYPO3/CMS/Install/ProgressBar',
    'TYPO3/CMS/Install/InfoBox',
    'TYPO3/CMS/Install/Severity',
    'TYPO3/CMS/Backend/Notification'
  ],
  function ($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Notification) {
    'use strict';

    return {
      selectorModalBody: '.t3js-modal-body',
      selectorModuleContent: '.t3js-module-content',
      selectorOutputWizardsContainer: '.t3js-upgradeWizards-wizards-output',
      selectorOutputDoneContainer: '.t3js-upgradeWizards-done-output',
      selectorWizardsBlockingAddsTemplate: '.t3js-upgradeWizards-blocking-adds-template',
      selectorWizardsBlockingAddsRows: '.t3js-upgradeWizards-blocking-adds-rows',
      selectorWizardsBlockingAddsExecute: '.t3js-upgradeWizards-blocking-adds-execute',
      selectorWizardsBlockingCharsetTemplate: '.t3js-upgradeWizards-blocking-charset-template',
      selectorWizardsBlockingCharsetFix: '.t3js-upgradeWizards-blocking-charset-fix',
      selectorWizardsDoneBodyTemplate: '.t3js-upgradeWizards-done-body-template',
      selectorWizardsDoneRows: '.t3js-upgradeWizards-done-rows',
      selectorWizardsDoneRowTemplate: '.t3js-upgradeWizards-done-row-template table tr',
      selectorWizardsDoneRowMarkUndone: '.t3js-upgradeWizards-done-markUndone',
      selectorWizardsDoneRowTitle: '.t3js-upgradeWizards-done-title',
      selectorWizardsListTemplate: '.t3js-upgradeWizards-list-template',
      selectorWizardsListRows: '.t3js-upgradeWizards-list-rows',
      selectorWizardsListRowTemplate: '.t3js-upgradeWizards-list-row-template',
      selectorWizardsListRowTitle: '.t3js-upgradeWizards-list-row-title',
      selectorWizardsListRowExplanation: '.t3js-upgradeWizards-list-row-explanation',
      selectorWizardsListRowExecute: '.t3js-upgradeWizards-list-row-execute',
      selectorWizardsInputTemplate: '.t3js-upgradeWizards-input',
      selectorWizardsInputTitle: '.t3js-upgradeWizards-input-title',
      selectorWizardsInputHtml: '.t3js-upgradeWizards-input-html',
      selectorWizardsInputPerform: '.t3js-upgradeWizards-input-perform',

      initialize: function (currentModal) {
        var self = this;
        this.currentModal = currentModal;

        this.getData().done(function() {
          self.doneUpgrades();
        });

        // Mark a done wizard undone
        currentModal.on('click', this.selectorWizardsDoneRowMarkUndone, function (e) {
          self.markUndone(e.target.dataset.identifier);
        });

        // Execute "fix default mysql connection db charset" blocking wizard
        currentModal.on('click', this.selectorWizardsBlockingCharsetFix, function (e) {
          self.blockingUpgradesDatabaseCharsetFix();
        });

        // Execute "add required fields + tables" blocking wizard
        currentModal.on('click', this.selectorWizardsBlockingAddsExecute, function (e) {
          self.blockingUpgradesDatabaseAddsExecute();
        });

        // Get user input of a single upgrade wizard
        currentModal.on('click', this.selectorWizardsListRowExecute, function (e) {
          self.wizardInput(e.target.dataset.identifier, e.target.dataset.title);
        });

        // Execute one upgrade wizard
        currentModal.on('click', this.selectorWizardsInputPerform, function (e) {
          self.wizardExecute(e.target.dataset.identifier, e.target.dataset.title);
        });
      },

      renderProgressBar: function (title) {
        return ProgressBar.render(Severity.loading, title, '')
      },

      getData: function () {
        var self = this;
        var modalContent = this.currentModal.find(this.selectorModalBody);
        return $.ajax({
          url: Router.getUrl('upgradeWizardsGetData'),
          cache: false,
          success: function (data) {
            if (data.success === true) {
              modalContent.empty().append(data.html);
              self.blockingUpgradesDatabaseCharsetTest();
            } else {
              Notification.error('Something went wrong');
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      blockingUpgradesDatabaseCharsetTest: function () {
        var self = this;
        var modalContent = this.currentModal.find(this.selectorModalBody);
        var $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
        $outputContainer.empty().html(this.renderProgressBar('Checking database charset...'));
        $.ajax({
          url: Router.getUrl('upgradeWizardsBlockingDatabaseCharsetTest'),
          cache: false,
          success: function (data) {
            self.removeLoadingMessage($outputContainer);
            if (data.success === true) {
              if (data.needsUpdate === true) {
                modalContent.find(self.selectorOutputWizardsContainer).append(modalContent.find(self.selectorWizardsBlockingCharsetTemplate)).clone();
              } else {
                self.blockingUpgradesDatabaseAdds();
              }
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      blockingUpgradesDatabaseCharsetFix: function () {
        var self = this;
        var $outputContainer = $(this.selectorOutputWizardsContainer);
        $outputContainer.empty().html(this.renderProgressBar('Setting database charset to UTF-8...'));
        $.ajax({
          url: Router.getUrl('upgradeWizardsBlockingDatabaseCharsetFix'),
          cache: false,
          success: function (data) {
            self.removeLoadingMessage($outputContainer);
            if (data.success === true) {
              if (Array.isArray(data.status) && data.status.length > 0) {
                data.status.forEach(function (element) {
                  var message = InfoBox.render(element.severity, element.title, element.message);
                  $outputContainer.append(message);
                });
              }
            } else {
              var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
              self.removeLoadingMessage($outputContainer);
              $outputContainer.append(message);
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      blockingUpgradesDatabaseAdds: function () {
        var self = this;
        var modalContent = this.currentModal.find(this.selectorModalBody);
        var $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
        $outputContainer.empty().html(this.renderProgressBar('Check for missing mandatory database tables and fields...'));
        $.ajax({
          url: Router.getUrl('upgradeWizardsBlockingDatabaseAdds'),
          cache: false,
          success: function (data) {
            self.removeLoadingMessage($outputContainer);
            if (data.success === true) {
              if (data.needsUpdate === true) {
                var adds = modalContent.find(self.selectorWizardsBlockingAddsTemplate).clone();
                if (typeof(data.adds.tables) === 'object') {
                  data.adds.tables.forEach(function (element) {
                    adds.find(self.selectorWizardsBlockingAddsRows).append('Table: ' + element.table + '<br>');
                  });
                }
                if (typeof(data.adds.columns) === 'object') {
                  data.adds.columns.forEach(function (element) {
                    adds.find(self.selectorWizardsBlockingAddsRows).append('Table: ' + element.table + ', Field: ' + element.field + '<br>');
                  });
                }
                if (typeof(data.adds.indexes) === 'object') {
                  data.adds.indexes.forEach(function (element) {
                    adds.find(self.selectorWizardsBlockingAddsRows).append('Table: ' + element.table + ', Index: ' + element.index + '<br>');
                  });
                }
                modalContent.find(self.selectorOutputWizardsContainer).append(adds);
              } else {
                self.wizardsList();
              }
            } else {
              Notification.error('Something went wrong');
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      blockingUpgradesDatabaseAddsExecute: function () {
        var self = this;
        var $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
        $outputContainer.empty().html(this.renderProgressBar('Adding database tables and fields...'));
        $.ajax({
          url: Router.getUrl('upgradeWizardsBlockingDatabaseExecute'),
          cache: false,
          success: function (data) {
            self.removeLoadingMessage($outputContainer);
            if (data.success === true) {
              if (Array.isArray(data.status) && data.status.length > 0) {
                data.status.forEach(function (element) {
                  var message = InfoBox.render(element.severity, element.title, element.message);
                  $outputContainer.append(message);
                });
                self.wizardsList();
              }
            } else {
              var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
              self.removeLoadingMessage($outputContainer);
              $outputContainer.append(message);
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      wizardsList: function () {
        var self = this;
        var modalContent = this.currentModal.find(this.selectorModalBody);
        var $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
        $outputContainer.append(this.renderProgressBar('Loading upgrade wizards...'));

        $.ajax({
          url: Router.getUrl('upgradeWizardsList'),
          cache: false,
          success: function (data) {
            self.removeLoadingMessage($outputContainer);
            var list = modalContent.find(self.selectorWizardsListTemplate).clone();
            list.removeClass('t3js-upgradeWizards-list-template');
            if (data.success === true) {
              var numberOfWizardsTodo = 0;
              var numberOfWizards = 0;
              if (Array.isArray(data.wizards) && data.wizards.length > 0) {
                numberOfWizards = data.wizards.length;
                data.wizards.forEach(function (element) {
                  if (element.shouldRenderWizard === true) {
                    var aRow = modalContent.find(self.selectorWizardsListRowTemplate).clone();
                    numberOfWizardsTodo = numberOfWizardsTodo + 1;
                    aRow.removeClass('t3js-upgradeWizards-list-row-template');
                    aRow.find(self.selectorWizardsListRowTitle).empty().text(element.title);
                    aRow.find(self.selectorWizardsListRowExplanation).empty().text(element.explanation);
                    aRow.find(self.selectorWizardsListRowExecute).attr('data-identifier', element.identifier).attr('data-title', element.title);
                    list.find(self.selectorWizardsListRows).append(aRow);
                  }
                });
                list.find(self.selectorWizardsListRows + ' hr:last').remove();
              }
              var percent = 100;
              var $progressBar = list.find('.progress-bar');
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
                .text(parseInt(percent) + '%');
              modalContent.find(self.selectorOutputWizardsContainer).append(list);
              self.currentModal.find(self.selectorWizardsDoneRowMarkUndone).prop("disabled", false);
            } else {
              Notification.error('Something went wrong');
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      wizardInput: function (identifier, title) {
        var self = this;
        var executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-wizards-input-token');
        var modalContent = this.currentModal.find(this.selectorModalBody);
        var $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
        $outputContainer.empty().html(this.renderProgressBar('Loading "' + title + '"...'));

        modalContent.animate({
          scrollTop: modalContent.scrollTop() - Math.abs(modalContent.find('.t3js-upgrade-status-section').position().top)
        }, 250);

        $.ajax({
          url: Router.getUrl(),
          method: 'POST',
          data: {
            'install': {
              'action': 'upgradeWizardsInput',
              'token': executeToken,
              'identifier': identifier
            }
          },
          cache: false,
          success: function (data) {
            $outputContainer.empty();
            var input = modalContent.find(self.selectorWizardsInputTemplate).clone();
            input.removeClass('t3js-upgradeWizards-input');
            if (data.success === true) {
              if (Array.isArray(data.status)) {
                data.status.forEach(function (element) {
                  var message = FlashMessage.render(element.severity, element.title, element.message);
                  $outputContainer.append(message);
                });
              }
              if (data.userInput.wizardHtml.length > 0) {
                input.find(self.selectorWizardsInputHtml).html(data.userInput.wizardHtml);
              }
              input.find(self.selectorWizardsInputTitle).text(data.userInput.title);
              input.find(self.selectorWizardsInputPerform).attr('data-identifier', data.userInput.identifier).attr('data-title', data.userInput.title);
            }
            modalContent.find(self.selectorOutputWizardsContainer).append(input);
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      wizardExecute: function (identifier, title) {
        var self = this;
        var executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-wizards-execute-token');
        var modalContent = this.currentModal.find(this.selectorModalBody);
        var postData = {
          'install[action]': 'upgradeWizardsExecute',
          'install[token]': executeToken,
          'install[identifier]': identifier
        };
        $(this.currentModal.find(this.selectorOutputWizardsContainer + ' form').serializeArray()).each(function () {
          postData[this.name] = this.value;
        });
        var $outputContainer = this.currentModal.find(this.selectorOutputWizardsContainer);
        // modalContent.find(self.selectorOutputWizardsContainer).empty();
        $outputContainer.empty().html(this.renderProgressBar('Executing "' + title + '"...'));
        this.currentModal.find(this.selectorWizardsDoneRowMarkUndone).prop("disabled", true);
        $.ajax({
          method: 'POST',
          data: postData,
          url: Router.getUrl(),
          cache: false,
          success: function (data) {
            $outputContainer.empty();
            if (data.success === true) {
              if (Array.isArray(data.status)) {
                data.status.forEach(function (element) {
                  var message = InfoBox.render(element.severity, element.title, element.message);
                  $outputContainer.append(message);
                });
              }
              self.wizardsList();
              modalContent.find(self.selectorOutputDoneContainer).empty();
              self.doneUpgrades();
            } else {
              Notification.error('Something went wrong');
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      doneUpgrades: function () {
        var self = this;
        var modalContent = this.currentModal.find(this.selectorModalBody);
        var $outputContainer = modalContent.find(this.selectorOutputDoneContainer);
        $outputContainer.empty().html(this.renderProgressBar('Loading executed upgrade wizards...'));

        $.ajax({
          url: Router.getUrl('upgradeWizardsDoneUpgrades'),
          cache: false,
          success: function (data) {
            self.removeLoadingMessage($outputContainer);
            if (data.success === true) {
              if (Array.isArray(data.status) && data.status.length > 0) {
                data.status.forEach(function (element) {
                  var message = InfoBox.render(element.severity, element.title, element.message);
                  $outputContainer.append(message);
                });
              }
              var body = modalContent.find(self.selectorWizardsDoneBodyTemplate).clone();
              var hasBodyContent = false;
              var $wizardsDoneContainer = body.find(self.selectorWizardsDoneRows);
              if (Array.isArray(data.wizardsDone) && data.wizardsDone.length > 0) {
                data.wizardsDone.forEach(function (element) {
                  hasBodyContent = true;
                  var aRow = modalContent.find(self.selectorWizardsDoneRowTemplate).clone();
                  aRow.find(self.selectorWizardsDoneRowMarkUndone).attr('data-identifier', element.identifier);
                  aRow.find(self.selectorWizardsDoneRowTitle).text(element.title);
                  $wizardsDoneContainer.append(aRow);
                });
              }
              if (Array.isArray(data.rowUpdatersDone) && data.rowUpdatersDone.length > 0) {
                data.rowUpdatersDone.forEach(function (element) {
                  hasBodyContent = true;
                  var aRow = modalContent.find(self.selectorWizardsDoneRowTemplate).clone();
                  aRow.find(self.selectorWizardsDoneRowMarkUndone).attr('data-identifier', element.identifier);
                  aRow.find(self.selectorWizardsDoneRowTitle).text(element.title);
                  $wizardsDoneContainer.append(aRow);
                });
              }
              if (hasBodyContent === true) {
                modalContent.find(self.selectorOutputDoneContainer).append(body);
                self.currentModal.find(self.selectorWizardsDoneRowMarkUndone).prop("disabled", true);
              }
            } else {
              Notification.error('Something went wrong');
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      markUndone: function (identifier) {
        var self = this;
        var executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-wizards-mark-undone-token');
        var modalContent = this.currentModal.find(this.selectorModalBody);
        var $outputContainer = this.currentModal.find(this.selectorOutputDoneContainer);
        $outputContainer.empty().html(this.renderProgressBar('Marking upgrade wizard as undone...'));
        $.ajax({
          url: Router.getUrl(),
          method: 'POST',
          data: {
            'install': {
              'action': 'upgradeWizardsMarkUndone',
              'token': executeToken,
              'identifier': identifier
            }
          },
          cache: false,
          success: function (data) {
            $outputContainer.empty();
            modalContent.find(self.selectorOutputDoneContainer).empty();
            if (data.success === true && Array.isArray(data.status)) {
              data.status.forEach(function (element) {
                Notification.success(element.message);
                self.doneUpgrades();
                self.blockingUpgradesDatabaseCharsetTest();
              });
            } else {
              Notification.error('Something went wrong');
            }
          },
          error: function (xhr) {
            Router.handleAjaxError(xhr, $outputContainer);
          }
        });
      },

      removeLoadingMessage: function ($container) {
        $container.find('.alert-loading').remove();
      }
    };
  });
