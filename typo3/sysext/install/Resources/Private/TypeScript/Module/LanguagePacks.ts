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
import FlashMessage = require('../Renderable/FlashMessage');
import ProgressBar = require('../Renderable/ProgressBar');
import InfoBox = require('../Renderable/InfoBox');
import Severity = require('../Renderable/Severity');

/**
 * Module: TYPO3/CMS/Install/Module/LanguagePacks
 */
class LanguagePacks implements InteractableModuleInterface {
  private selectorModalBody: string = '.t3js-modal-body';
  private selectorModuleContent: string = '.t3js-module-content';
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
  private selectorExtensionPackMissesIcon: string = '#t3js-languagePacks-extensionPack-misses-icon';
  private selectorNotifications: string = '.t3js-languagePacks-notifications';

  private currentModal: JQuery;

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
    const modalContent = this.currentModal.find(this.selectorModalBody);
    $.ajax({
      url: Router.getUrl('languagePacksGetData'),
      cache: false,
      success: (data: any): void => {
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
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private activateLanguage(iso: string): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().append(message);

    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      context: this,
      data: {
        'install': {
          'action': 'languagePacksActivateLanguage',
          'token': this.currentModal.find(this.selectorModuleContent).data('language-packs-activate-language-token'),
          'iso': iso,
        },
      },
      cache: false,
      beforeSend: (): void => {
        this.getNotificationBox().empty();
      },
      success: (data: any): void => {
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
      error: (xhr: XMLHttpRequest): any => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private deactivateLanguage(iso: string): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().append(message);
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      context: this,
      data: {
        'install': {
          'action': 'languagePacksDeactivateLanguage',
          'token': this.currentModal.find(this.selectorModuleContent).data('language-packs-deactivate-language-token'),
          'iso': iso,
        },
      },
      cache: false,
      beforeSend: (): void => {
        this.getNotificationBox().empty();
      },
      success: (data: any): void => {
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
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private updatePacks(iso: string, extension: string): void {
    const $outputContainer = this.currentModal.find(this.selectorOutputContainer);
    const $contentContainer = this.currentModal.find(this.selectorContentContainer);
    const isos = iso === undefined ? this.activeLanguages : [ iso ];
    let updateIsoTimes = true;
    let extensions = this.activeExtensions;
    if (extension !== undefined) {
      extensions = [ extension ];
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
          $('<span>', {'class': 'text-nowrap'}).text('0 of ' + this.packsUpdateDetails.toHandle + ' language packs updated'),
        ),
      ));
    $contentContainer.empty();

    isos.forEach((isoCode: string): void => {
      extensions.forEach((extensionKey: string): void => {
        $.ajax({
          url: Router.getUrl(),
          method: 'POST',
          context: this,
          data: {
            'install': {
              'action': 'languagePacksUpdatePack',
              'token': this.currentModal.find(this.selectorModuleContent).data('language-packs-update-pack-token'),
              'iso': isoCode,
              'extension': extensionKey,
            },
          },
          cache: false,
          beforeSend: (): void => {
            this.getNotificationBox().empty();
          },
          success: (data: any): void => {
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
          error: (): void => {
            this.packsUpdateDetails.handled++;
            this.packsUpdateDetails.failed++;
            this.packUpdateDone(updateIsoTimes, isos);
          },
        });
      });
    });
  }

  private packUpdateDone(updateIsoTimes: boolean, isos: Array<any>): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputContainer);
    if (this.packsUpdateDetails.handled === this.packsUpdateDetails.toHandle) {
      // All done - create summary, update 'last update' of iso list, render main view
      const message = InfoBox.render(
        Severity.ok,
        'Language packs updated',
        this.packsUpdateDetails.new + ' new language packs downloaded, ' +
        this.packsUpdateDetails.updated + ' language packs updated, ' +
        this.packsUpdateDetails.failed + ' language packs not available',
      );
      this.addNotification(message);
      if (updateIsoTimes === true) {
        $.ajax({
          url: Router.getUrl(),
          method: 'POST',
          context: this,
          data: {
            'install': {
              'action': 'languagePacksUpdateIsoTimes',
              'token': this.currentModal.find(this.selectorModuleContent).data('language-packs-update-iso-times-token'),
              'isos': isos,
            },
          },
          cache: false,
          success: (data: any): void => {
            if (data.success === true) {
              this.getData();
            } else {
              const m: any = FlashMessage.render(Severity.error, 'Something went wrong', '');
              this.addNotification(m);
            }
          },
          error: (xhr: XMLHttpRequest): void => {
            Router.handleAjaxError(xhr, modalContent);
          },
        });
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
        .text(this.packsUpdateDetails.handled + ' of ' + this.packsUpdateDetails.toHandle + ' language packs updated');
    }
  }

  private languageMatrixHtml(data: any): string {
    const activateIcon = this.currentModal.find(this.selectorActivateLanguageIcon).html();
    const deactivateIcon = this.currentModal.find(this.selectorDeactivateLanguageIcon).html();
    const updateIcon = this.currentModal.find(this.selectorLanguageUpdateIcon).html();
    const $markupContainer = $('<div>');

    const $tbody = $('<tbody>');
    data.languages.forEach((language: any): void => {
      const active = language.active;
      const $tr = $('<tr>');
      if (active) {
        $tbody.append(
          $tr.append(
            $('<td>').append(
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
        );
      } else {
        $tbody.append(
          $tr.addClass('t3-languagePacks-inactive t3js-languagePacks-inactive').css({'display': 'none'}).append(
            $('<td>').append(
              $('<a>', {
                'class': 'btn btn-default t3js-languagePacks-activateLanguage',
                'data-iso': language.iso,
                'data-toggle': 'tooltip',
                'title': 'Activate',
              }).append(activateIcon),
            ),
          ),
        );
      }
      $tr.append(
        $('<td>').text(language.name),
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
              $('<button>', {'class': 'btn btn-default t3js-languagePacks-addLanguage-toggle', 'type': 'button'}).append(
                $('<span>').append(activateIcon),
                ' Add language',
              ),
              $('<button>', {'class': 'btn btn-default t3js-languagePacks-update', 'type': 'button'}).append(
                $('<span>').append(updateIcon),
                ' Update all',
              ),
            ),
            $('<th>').text('Language'),
            $('<th>').text('Locale'),
            $('<th>').text('Dependencies'),
            $('<th>').text('Last update'),
          ),
        ),
        $tbody,
      ),
    );
    return $markupContainer.html();
  }

  private extensionMatrixHtml(data: any): any {
    const packMissesIcon: string = this.currentModal.find(this.selectorExtensionPackMissesIcon).html();
    const updateIcon: string = this.currentModal.find(this.selectorLanguageUpdateIcon).html();
    let tooltip: string = '';
    let extensionTitle: JQuery;
    let allPackagesExist: boolean = true;
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
      allPackagesExist = true;
      extension.packs.forEach((pack: any): void => {
        if (pack.exists === false) {
          allPackagesExist = false;
        }
      });
      if (allPackagesExist === true) {
        return;
      }
      rowCount++;
      if (extension.icon !== '') {
        extensionTitle = $('<span>').append(
          $('<img>', {
            'style': 'max-height: 16px; max-width: 16px;',
            'src': '../' + extension.icon,
            'alt': extension.title,
          }),
          $('<span>').text(extension.title),
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
        if (pack.exists !== true) {
          if (pack.lastUpdate !== null) {
            tooltip = 'No language pack available when tried at ' + pack.lastUpdate + '. Click to re-try.';
          } else {
            tooltip = 'Language pack not downloaded. Click to download';
          }
          $tr.append(
            $('<td>').append(
              $('<a>', {
                'class': 'btn btn-default t3js-languagePacks-update',
                'data-extension': extension.key,
                'data-iso': pack.iso,
                'data-toggle': 'tooltip',
                'title': tooltip,
              }).append(packMissesIcon),
            ),
          );
        }
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
    return this.currentModal.find(this.selectorNotifications);
  }

  private addNotification(notification: any): void {
    this.notifications.push(notification);
  }

  private renderNotifications(): void {
    const $notificationBox: JQuery = this.getNotificationBox();
    for (let i = 0; i < this.notifications.length; ++i) {
      $notificationBox.append(this.notifications[i]);
    }
    this.notifications = [];
  }
}

export = new LanguagePacks();
