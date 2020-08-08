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
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import SecurityUtility = require('TYPO3/CMS/Core/SecurityUtility');
import FlashMessage = require('../../Renderable/FlashMessage');
import InfoBox = require('../../Renderable/InfoBox');
import ProgressBar = require('../../Renderable/ProgressBar');
import Severity = require('../../Renderable/Severity');
import Router = require('../../Router');

/**
 * Module: TYPO3/CMS/Install/Module/LanguagePacks
 */
class LanguagePacks extends AbstractInteractableModule {
  private selectorOutputContainer: string = '.t3js-languagePacks-output';
  private selectorContentContainer: string = '.t3js-languagePacks-mainContent';
  private selectorActivateLanguage: string = '.t3js-languagePacks-activateLanguage';
  private selectorActivateLanguageIcon: string = '#t3js-languagePacks-activate-icon';
  private selectorAddLanguageToggle: string = '.t3js-languagePacks-addLanguage-toggle';
  private selectorLanguageInactive: string = '.t3js-languagePacks-inactive';
  private selectorDeactivateLanguage: string = '.t3js-languagePacks-deactivateLanguage';
  private selectorDeactivateLanguageIcon: string = '#t3js-languagePacks-deactivate-icon';
  private selectorUpdate: string = '.t3js-languagePacks-update';
  private selectorLanguageUpdateIcon: string = '#t3js-languagePacks-languageUpdate-icon';
  private selectorNotifications: string = '.t3js-languagePacks-notifications';

  private activeLanguages: Array<any> = [];
  private activeExtensions: Array<any> = [];

  private packsUpdateDetails: { [id: string]: number } = {
    toHandle: 0,
    handled: 0,
    updated: 0,
    new: 0,
    failed: 0,
  };

  private notifications: Array<any> = [];

  private static pluralize(count: number, word: string = 'pack', suffix: string = 's', additionalCount: number = 0): string {
    return count !== 1 && additionalCount !== 1 ? word + suffix : word;
  }

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;

    // Get configuration list on modal open
    this.getData();

    currentModal.on('click', this.selectorAddLanguageToggle, (): void => {
      currentModal.find(this.selectorContentContainer + ' ' + this.selectorLanguageInactive).toggle();
    });

    currentModal.on('click', this.selectorActivateLanguage, (e: JQueryEventObject): void => {
      const iso = $(e.target).closest(this.selectorActivateLanguage).data('iso');
      e.preventDefault();
      this.activateLanguage(iso);
    });

    currentModal.on('click', this.selectorDeactivateLanguage, (e: JQueryEventObject): void => {
      const iso = $(e.target).closest(this.selectorDeactivateLanguage).data('iso');
      e.preventDefault();
      this.deactivateLanguage(iso);
    });

    currentModal.on('click', this.selectorUpdate, (e: JQueryEventObject): void => {
      const iso = $(e.target).closest(this.selectorUpdate).data('iso');
      const extension = $(e.target).closest(this.selectorUpdate).data('extension');
      e.preventDefault();
      this.updatePacks(iso, extension);
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('languagePacksGetData')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.activeLanguages = data.activeLanguages;
            this.activeExtensions = data.activeExtensions;
            modalContent.empty().append(data.html);
            const contentContainer: JQuery = modalContent.parent().find(this.selectorContentContainer);
            contentContainer.empty();
            contentContainer.append(this.languageMatrixHtml(data));
            contentContainer.append(this.extensionMatrixHtml(data));
            $('[data-toggle="tooltip"]').tooltip(<any>({container: contentContainer}));
          } else {
            const message = InfoBox.render(Severity.error, 'Something went wrong', '');
            this.addNotification(message);
          }

          this.renderNotifications();
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private activateLanguage(iso: string): void {
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().append(message);

    this.getNotificationBox().empty();
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'languagePacksActivateLanguage',
          token: this.getModuleContent().data('language-packs-activate-language-token'),
          iso: iso,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              const m: any = InfoBox.render(element.severity, element.title, element.message);
              this.addNotification(m);
            });
          } else {
            const m2: any = FlashMessage.render(Severity.error, 'Something went wrong', '');
            this.addNotification(m2);
          }
          this.getData();
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private deactivateLanguage(iso: string): void {
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().append(message);
    this.getNotificationBox().empty();
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'languagePacksDeactivateLanguage',
          token: this.getModuleContent().data('language-packs-deactivate-language-token'),
          iso: iso,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              const m: any = InfoBox.render(element.severity, element.title, element.message);
              this.addNotification(m);
            });
          } else {
            const m2: any = FlashMessage.render(Severity.error, 'Something went wrong', '');
            this.addNotification(m2);
          }
          this.getData();
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private updatePacks(iso: string, extension: string): void {
    const $outputContainer = this.findInModal(this.selectorOutputContainer);
    const $contentContainer = this.findInModal(this.selectorContentContainer);
    const isos = iso === undefined ? this.activeLanguages : [iso];
    let updateIsoTimes = true;
    let extensions = this.activeExtensions;
    if (extension !== undefined) {
      extensions = [extension];
      updateIsoTimes = false;
    }

    this.packsUpdateDetails = {
      toHandle: isos.length * extensions.length,
      handled: 0,
      updated: 0,
      new: 0,
      failed: 0,
    };

    $outputContainer.empty().append(
      $('<div>', {'class': 'progress'}).append(
        $('<div>', {
          'class': 'progress-bar progress-bar-info',
          'role': 'progressbar',
          'aria-valuenow': 0,
          'aria-valuemin': 0,
          'aria-valuemax': 100,
          'style': 'width: 0;',
        }).append(
          $(
            '<span>',
            {'class': 'text-nowrap'}).text('0 of ' + this.packsUpdateDetails.toHandle + ' language ' +
            LanguagePacks.pluralize(this.packsUpdateDetails.toHandle) + ' updated'
          ),
        ),
      ));
    $contentContainer.empty();

    isos.forEach((isoCode: string): void => {
      extensions.forEach((extensionKey: string): void => {
        this.getNotificationBox().empty();

        (new AjaxRequest(Router.getUrl()))
          .post({
            install: {
              action: 'languagePacksUpdatePack',
              token: this.getModuleContent().data('language-packs-update-pack-token'),
              iso: isoCode,
              extension: extensionKey,
            },
          })
          .then(
            async (response: AjaxResponse): Promise<any> => {
              const data = await response.resolve();
              if (data.success === true) {
                this.packsUpdateDetails.handled++;
                if (data.packResult === 'new') {
                  this.packsUpdateDetails.new++;
                } else if (data.packResult === 'update') {
                  this.packsUpdateDetails.updated++;
                } else {
                  this.packsUpdateDetails.failed++;
                }
                this.packUpdateDone(updateIsoTimes, isos);
              } else {
                this.packsUpdateDetails.handled++;
                this.packsUpdateDetails.failed++;
                this.packUpdateDone(updateIsoTimes, isos);
              }
            },
            (): void => {
              this.packsUpdateDetails.handled++;
              this.packsUpdateDetails.failed++;
              this.packUpdateDone(updateIsoTimes, isos);
            }
          );
      });
    });
  }

  private packUpdateDone(updateIsoTimes: boolean, isos: Array<any>): void {
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputContainer);
    if (this.packsUpdateDetails.handled === this.packsUpdateDetails.toHandle) {
      // All done - create summary, update 'last update' of iso list, render main view
      const message = InfoBox.render(
        Severity.ok,
        'Language packs updated',
        this.packsUpdateDetails.new + ' new language ' + LanguagePacks.pluralize(this.packsUpdateDetails.new) + ' downloaded, ' +
        this.packsUpdateDetails.updated + ' language ' + LanguagePacks.pluralize(this.packsUpdateDetails.updated) + ' updated, ' +
        this.packsUpdateDetails.failed + ' language ' + LanguagePacks.pluralize(this.packsUpdateDetails.failed) + ' not available',
      );
      this.addNotification(message);
      if (updateIsoTimes === true) {
        (new AjaxRequest(Router.getUrl()))
          .post({
            install: {
              action: 'languagePacksUpdateIsoTimes',
              token: this.getModuleContent().data('language-packs-update-iso-times-token'),
              isos: isos,
            },
          })
          .then(
            async (response: AjaxResponse): Promise<any> => {
              const data = await response.resolve();
              if (data.success === true) {
                this.getData();
              } else {
                const m: any = FlashMessage.render(Severity.error, 'Something went wrong', '');
                this.addNotification(m);
              }
            },
            (error: ResponseError): void => {
              Router.handleAjaxError(error, modalContent);
            }
          );
      } else {
        this.getData();
      }
    } else {
      // Update progress bar
      const percent = (this.packsUpdateDetails.handled / this.packsUpdateDetails.toHandle) * 100;
      $outputContainer.find('.progress-bar')
        .css('width', percent + '%')
        .attr('aria-valuenow', percent)
        .find('span')
        .text(
          this.packsUpdateDetails.handled + ' of ' + this.packsUpdateDetails.toHandle + ' language ' +
          LanguagePacks.pluralize(this.packsUpdateDetails.handled, 'pack', 's', this.packsUpdateDetails.toHandle) + ' updated'
        );
    }
  }

  private languageMatrixHtml(data: any): string {
    const activateIcon = this.findInModal(this.selectorActivateLanguageIcon).html();
    const deactivateIcon = this.findInModal(this.selectorDeactivateLanguageIcon).html();
    const updateIcon = this.findInModal(this.selectorLanguageUpdateIcon).html();
    const $markupContainer = $('<div>');

    const $tbody = $('<tbody>');
    data.languages.forEach((language: any): void => {
      const active = language.active;
      const $tr = $('<tr>');
      if (active) {
        $tbody.append(
          $tr.append(
            $('<td>').text(' ' + language.name).prepend(
              $('<div />', {class: 'btn-group'}).append(
                $('<a>', {
                  'class': 'btn btn-default t3js-languagePacks-deactivateLanguage',
                  'data-iso': language.iso,
                  'data-toggle': 'tooltip',
                  'title': 'Deactivate',
                }).append(deactivateIcon),
                $('<a>', {
                  'class': 'btn btn-default t3js-languagePacks-update',
                  'data-iso': language.iso,
                  'data-toggle': 'tooltip',
                  'title': 'Download language packs',
                }).append(updateIcon),
              ),
            ),
          ),
        );
      } else {
        $tbody.append(
          $tr.addClass('t3-languagePacks-inactive t3js-languagePacks-inactive').css({'display': 'none'}).append(
            $('<td>').text(' ' + language.name).prepend(
              $('<div />', {class: 'btn-group'}).append(
                $('<a>', {
                  'class': 'btn btn-default t3js-languagePacks-activateLanguage',
                  'data-iso': language.iso,
                  'data-toggle': 'tooltip',
                  'title': 'Activate',
                }).append(activateIcon),
              ),
            ),
          ),
        );
      }

      $tr.append(
        $('<td>').text(language.iso),
        $('<td>').text(language.dependencies.join(', ')),
        $('<td>').text(language.lastUpdate === null ? '' : language.lastUpdate),
      );
      $tbody.append($tr);
    });
    $markupContainer.append(
      $('<h3>').text('Active languages'),
      $('<table>', {'class': 'table table-striped table-bordered'}).append(
        $('<thead>').append(
          $('<tr>').append(
            $('<th>').append(
              $('<div />', {class: 'btn-group'}).append(
                $('<button>', {
                  'class': 'btn btn-default t3js-languagePacks-addLanguage-toggle',
                  'type': 'button'
                }).append(
                  $('<span>').append(activateIcon),
                  ' Add language',
                ),
                $('<button>', {'class': 'btn btn-default disabled update-all t3js-languagePacks-update', 'type': 'button', 'disabled': 'disabled'}).append(
                  $('<span>').append(updateIcon),
                  ' Update all',
                ),
              ),
            ),
            $('<th>').text('Locale'),
            $('<th>').text('Dependencies'),
            $('<th>').text('Last update'),
          ),
        ),
        $tbody,
      ),
    );

    if (Array.isArray(this.activeLanguages) && this.activeLanguages.length) {
      $markupContainer.find('.update-all').removeClass('disabled').removeAttr('disabled');
    }
    return $markupContainer.html();
  }

  private extensionMatrixHtml(data: any): any {
    const securityUtility = new SecurityUtility();
    const updateIcon: string = this.findInModal(this.selectorLanguageUpdateIcon).html();
    let tooltip: string = '';
    let extensionTitle: JQuery;
    let rowCount: number = 0;
    const $markupContainer: JQuery = $('<div>');

    const $headerRow: JQuery = $('<tr>');
    $headerRow.append(
      $('<th>').text('Extension'),
      $('<th>').text('Key'),
    );
    data.activeLanguages.forEach((language: string): void => {
      $headerRow.append(
        $('<th>').append(
          $('<a>', {
            'class': 'btn btn-default t3js-languagePacks-update',
            'data-iso': language,
            'data-toggle': 'tooltip',
            'title': 'Download and update all language packs',
          }).append(
            $('<span>').append(updateIcon),
            ' ' + language,
          ),
        ),
      );
    });

    const $tbody = $('<tbody>');
    data.extensions.forEach((extension: any): void => {
      rowCount++;
      if (typeof extension.icon !== 'undefined') {
        extensionTitle = $('<span>').append(
          $('<img>', {
            'style': 'max-height: 16px; max-width: 16px;',
            'src': '../' + extension.icon,
            'alt': extension.title,
          }),
          $('<span>').text(' ' + extension.title),
        );
      } else {
        extensionTitle = $('<span>').text(extension.title);
      }
      const $tr = $('<tr>');
      $tr.append(
        $('<td>').html(extensionTitle.html()),
        $('<td>').text(extension.key),
      );
      extension.packs.forEach((pack: any): void => {
        const $column = $('<td>');
        $tr.append($column);
        if (pack.exists !== true) {
          if (pack.lastUpdate !== null) {
            tooltip = 'No language pack available for ' + pack.iso + ' when tried at ' + pack.lastUpdate + '. Click to re-try.';
          } else {
            tooltip = 'Language pack not downloaded. Click to download';
          }
        } else {
          if (pack.lastUpdate === null) {
            tooltip = 'Downloaded. Click to renew';
          } else {
            tooltip = 'Language pack downloaded at ' + pack.lastUpdate + '. Click to renew';
          }
        }
        $column.append(
          $('<a>', {
            'class': 'btn btn-default t3js-languagePacks-update',
            'data-extension': extension.key,
            'data-iso': pack.iso,
            'data-toggle': 'tooltip',
            'title': securityUtility.encodeHtml(tooltip),
          }).append(updateIcon),
        );
      });
      $tbody.append($tr);
    });

    $markupContainer.append(
      $('<h3>').text('Translation status'),
      $('<table>', {'class': 'table table-striped table-bordered'}).append(
        $('<thead>').append($headerRow),
        $tbody,
      ),
    );
    if (rowCount === 0) {
      return InfoBox.render(
        Severity.ok,
        'Language packs have been found for every installed extension.',
        'To download the latest changes, use the refresh button in the list above.',
      );
    }
    return $markupContainer.html();
  }

  private getNotificationBox(): JQuery {
    return this.findInModal(this.selectorNotifications);
  }

  private addNotification(notification: any): void {
    this.notifications.push(notification);
  }

  private renderNotifications(): void {
    const $notificationBox: JQuery = this.getNotificationBox();
    for (let notification of this.notifications) {
      $notificationBox.append(notification);
    }
    this.notifications = [];
  }
}

export = new LanguagePacks();
