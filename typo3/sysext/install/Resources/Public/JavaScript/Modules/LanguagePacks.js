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
 * Module: TYPO3/CMS/Install/LanguagePacks
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'bootstrap'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity) {
  'use strict';

  return {
    selectorGridderOpener: 't3js-languagePacks-open',
    selectorOutputContainer: '.t3js-languagePacks-output',
    selectorContentContainer: '.t3js-languagePacks-content',
    selectorActivateLanguage: '.t3js-languagePacks-activateLanguage',
    selectorActivateLanguageToken: '#t3js-languagePacks-activateLanguage-token',
    selectorActivateLanguageIcon: '#t3js-languagePacks-activate-icon',
    selectorAddLanguageToggle: '.t3js-languagePacks-addLanguage-toggle',
    selectorLanguageInactive: '.t3js-languagePacks-inactive',
    selectorDeactivateLanguage: '.t3js-languagePacks-deactivateLanguage',
    selectorDeactivateLanguageToken: '#t3js-languagePacks-deactivateLanguage-token',
    selectorDeactivateLanguageIcon: '#t3js-languagePacks-deactivate-icon',
    selectorUpdate: '.t3js-languagePacks-update',
    selectorUpdatePackToken: '#t3js-languagePacks-updatePack-token',
    selectorLanguageUpdateIcon: '#t3js-languagePacks-languageUpdate-icon',
    selectorExtensionPackMissesIcon: '#t3js-languagePacks-extensionPack-misses-icon',
    selectorUpdateIsoTimesToken: '#t3js-languagePacks-updateIsoTimes-token',

    activeLanguages: [],
    activeExtensions: [],

    packsUpdateDetails: {
      toHandle: 0,
      handled: 0,
      updated: 0,
      new: 0,
      failed: 0
    },

    initialize: function() {
      var self = this;

      // Get configuration list on card open
      $(document).on('cardlayout:card-opened', function(event, $card) {
        if ($card.hasClass(self.selectorGridderOpener)) {
          self.getData();
        }
      });

      $(document).on('click', this.selectorAddLanguageToggle, function(e) {
        $(document).find(self.selectorContentContainer + ' ' + self.selectorLanguageInactive).toggle();
      });

      $(document).on('click', this.selectorActivateLanguage, function(e) {
        var iso = $(e.target).closest(self.selectorActivateLanguage).data('iso');
        e.preventDefault();
        self.activateLanguage(iso);
      });

      $(document).on('click', this.selectorDeactivateLanguage, function(e) {
        var iso = $(e.target).closest(self.selectorDeactivateLanguage).data('iso');
        e.preventDefault();
        self.deactivateLanguage(iso);
      });

      $(document).on('click', this.selectorUpdate, function(e) {
        var iso = $(e.target).closest(self.selectorUpdate).data('iso');
        var extension = $(e.target).closest(self.selectorUpdate).data('extension');
        e.preventDefault();
        self.updatePacks(iso, extension);
      });
    },

    getData: function() {
      var self = this;
      var contentContainer = $(this.selectorContentContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      contentContainer.empty().html(message);
      $.ajax({
        url: Router.getUrl('languagePacksGetData'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            self.activeLanguages = data.activeLanguages;
            self.activeExtensions = data.activeExtensions;
            contentContainer.empty();
            contentContainer.append(self.languageMatrixHtml(data));
            contentContainer.append(self.extensionMatrixHtml(data));
            $('[data-toggle="tooltip"]').tooltip({container: contentContainer});
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            contentContainer.empty().append(message);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    activateLanguage: function(iso) {
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().append(message);
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        context: this,
        data: {
          'install': {
            'action': 'languagePacksActivateLanguage',
            'token': $(this.selectorActivateLanguageToken).text(),
            'iso': iso
          }
        },
        cache: false,
        success: function(data) {
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              var message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.append(message);
            });
          } else {
            var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            $outputContainer.append(message);
          }
          this.getData();
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    deactivateLanguage: function(iso) {
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().append(message);
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        context: this,
        data: {
          'install': {
            'action': 'languagePacksDeactivateLanguage',
            'token': $(this.selectorDeactivateLanguageToken).text(),
            'iso': iso
          }
        },
        cache: false,
        success: function(data) {
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              var message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.append(message);
            });
          } else {
            var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            $outputContainer.append(message);
          }
          this.getData();
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    updatePacks: function(iso, extension) {
      var self = this;
      var $outputContainer = $(this.selectorOutputContainer);
      var $contentContainer = $(this.selectorContentContainer);
      var updateIsoTimes = true;
      var isos = iso === undefined ? this.activeLanguages : [ iso ];
      var extensions = this.activeExtensions;
      if (extension !== undefined) {
        extensions = [ extension ];
        updateIsoTimes = false;
      }

      this.packsUpdateDetails = {
        toHandle: isos.length * extensions.length,
        handled: 0,
        updated: 0,
        new: 0,
        failed: 0
      };

      var message = '<div class="progress">' +
        '<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">' +
          '<span class="text-nowrap">0 of ' + this.packsUpdateDetails.toHandle + ' language packs updated</span>' +
        '</div>' +
      '</div>';

      $outputContainer.empty().append(message);
      $contentContainer.empty();

      isos.forEach(function(iso) {
        extensions.forEach(function(extension) {
          $.ajax({
            url: Router.getUrl(),
            method: 'POST',
            context: this,
            data: {
              'install': {
                'action': 'languagePacksUpdatePack',
                'token': $(self.selectorUpdatePackToken).text(),
                'iso': iso,
                'extension': extension
              }
            },
            cache: false,
            success: function(data) {
              if (data.success === true) {
                self.packsUpdateDetails.handled++;
                if (data.packResult === 'new') {
                  self.packsUpdateDetails.new++;
                } else if (data.packResult === 'update') {
                  self.packsUpdateDetails.updated++;
                } else {
                  self.packsUpdateDetails.failed++;
                }
                self.packUpdateDone(updateIsoTimes, isos);
              } else {
                self.packsUpdateDetails.handled++;
                self.packsUpdateDetails.failed++;
                self.packUpdateDone(updateIsoTimes, isos);
              }
            },
            error: function(xhr) {
              self.packsUpdateDetails.handled++;
              self.packsUpdateDetails.failed++;
              self.packUpdateDone(updateIsoTimes, isos);
            }
          });
        });
      });
    },

    packUpdateDone: function(updateIsoTimes, isos) {
      var self = this;
      var $outputContainer = $(this.selectorOutputContainer);
      if (this.packsUpdateDetails.handled === this.packsUpdateDetails.toHandle) {
        // All done - create summary, update 'last update' of iso list, render main view
        var message = InfoBox.render(
          Severity.ok,
          'Language packs updated',
          this.packsUpdateDetails.new + ' new language packs downloaded, ' +
          this.packsUpdateDetails.updated + ' language packs updated, ' +
          this.packsUpdateDetails.failed + ' language packs not available'
        );
        $outputContainer.empty().append(message);
        if (updateIsoTimes === true) {
          $.ajax({
            url: Router.getUrl(),
            method: 'POST',
            context: this,
            data: {
              'install': {
                'action': 'languagePacksUpdateIsoTimes',
                'token': $(self.selectorUpdateIsoTimesToken).text(),
                'isos': isos
              }
            },
            cache: false,
            success: function(data) {
              if (data.success === true) {
                self.getData();
              } else {
                var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
                $outputContainer.append(message);
              }
            },
            error: function(xhr) {
              Router.handleAjaxError(xhr);
            }
          });
        } else {
          this.getData();
        }
      } else {
        // Update progress bar
        var percent = (this.packsUpdateDetails.handled / this.packsUpdateDetails.toHandle) * 100;
        $outputContainer.find('.progress-bar')
          .css('width', percent + '%')
          .attr('aria-valuenow', percent)
          .find('span')
          .text(this.packsUpdateDetails.handled + ' of ' + this.packsUpdateDetails.toHandle + ' language packs updated');
      }
    },

    languageMatrixHtml: function(data) {
      var activateIcon = $(this.selectorActivateLanguageIcon).html();
      var deactivateIcon = $(this.selectorDeactivateLanguageIcon).html();
      var updateIcon = $(this.selectorLanguageUpdateIcon).html();
      var html = '<h3>Active languages</h3>' +
        '<table class="table table-striped table-bordered">' +
        '<thead><tr>' +
          '<th>' +
            '<button class="btn btn-default t3js-languagePacks-addLanguage-toggle" type="button">' +
              '<span> ' + activateIcon + '</span>Add language' +
            '</button> ' +
            '<button class="btn btn-default t3js-languagePacks-update" type="button">' +
              '<span> ' + updateIcon + '</span>Update all' +
            '</button>' +
          '</th>' +
          '<th>Language</th>' +
          '<th>Locale</th>' +
          '<th>Dependencies</th>' +
          '<th>Last update</th>' +
        '</tr></thead>' +
        '<tbody>';
      data.languages.forEach(function(language) {
        var active = language.active;
        if (active) {
          html = html +
            '<tr>' +
              '<td>' +
                '<a class="btn btn-default t3js-languagePacks-deactivateLanguage" data-iso="' + language.iso + '" data-toggle="tooltip" title="Deactivate">' +
                  deactivateIcon +
                '</a> ' +
                '<a class="btn btn-default t3js-languagePacks-update" data-iso="' + language.iso + '" data-toggle="tooltip" title="Download language packs">' +
                  updateIcon +
                '</a>' +
              '</td>';
        } else {
          html = html +
            '<tr class="t3-languagePacks-inactive t3js-languagePacks-inactive" style="display:none">' +
              '<td>' +
                '<a class="btn btn-default t3js-languagePacks-activateLanguage" data-iso="' + language.iso + '" data-toggle="tooltip" title="Activate">' +
                  activateIcon +
                '</a>' +
              '</td>';
        }
        html = html +
          '<td>' + language.name +'</td>' +
          '<td>' + language.iso +'</td>' +
          '<td>' + language.dependencies.join(', ') +'</td>' +
          '<td>' + (language.lastUpdate === null ? '' : language.lastUpdate) +'</td>' +
          '</tr>';
      });
      html = html + '</tbody></table>';
      return html;
    },

    extensionMatrixHtml: function(data) {
      var packMissesIcon = $(this.selectorExtensionPackMissesIcon).html();
      var updateIcon = $(this.selectorLanguageUpdateIcon).html();
      var tooltip = '';
      var extensionTitle = '';
      var allPackagesExist = true;
      var rowCount = 0;
      var html = '<h3>Translation status</h3>' +
        '<table class="table table-striped table-bordered"><thead><tr>' +
        '<th>Extension</th>' +
        '<th>Key</th>';
      data.activeLanguages.forEach(function(language) {
        html = html + '<th>' +
          '<a class="btn btn-default t3js-languagePacks-update" data-iso="' + language + '" data-toggle="tooltip" title="Download and update all language packs">' +
            '<span>' + updateIcon + '</span> ' + language +
          '</a>' +
        '</th>';
      });
      html = html + '</tr></thead><tbody>';
      data.extensions.forEach(function(extension) {
        allPackagesExist = true;
        extension.packs.forEach(function(pack) {
          if (pack.exists === false) {
            allPackagesExist = false;
          }
        });
        if (allPackagesExist === true) {
          return;
        }
        rowCount++;
        if (extension.icon !== '') {
          extensionTitle = '<img style="max-height: 16px; max-width: 16px;" src="../' + extension.icon + '" alt="' + extension.title + '" /> ' +
            '<span>' + extension.title + '</span>';
        } else {
          extensionTitle = extension.title;
        }
        html = html + '<tr>' +
          '<td>' + extensionTitle + '</td>' +
          '<td>' + extension.key + '</td>';
        extension.packs.forEach(function(pack) {
          html = html + '<td>';
          if (pack.exists !== true) {
            if (pack.lastUpdate !== null) {
              tooltip = 'No language pack available when tried at ' + pack.lastUpdate + '. Click to re-try.';
            } else {
              tooltip = 'Language pack not downloaded. Click to download';
            }
            html = html +
              '<a class="btn btn-default t3js-languagePacks-update" data-extension="' + extension.key + '" data-iso="' + pack.iso + '" data-toggle="tooltip" title="' + tooltip + '">' +
                packMissesIcon +
              '</a>';
          }
          html = html + '</td>';
        });
        html = html + '</tr>';
      });
      html = html + '</tbody>';
      if (rowCount === 0) {
        return InfoBox.render(Severity.ok, 'Language packs have been found for every installed extension.', 'To download the latest changes, use the refresh button in the list above.');
      }
      return html;
    }
  };
});
