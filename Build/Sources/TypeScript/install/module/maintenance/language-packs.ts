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
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule, ModuleLoadedResponse } from '../abstract-interactable-module';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import SecurityUtility from '@typo3/core/security-utility';
import FlashMessage from '../../renderable/flash-message';
import InfoBox from '../../renderable/info-box';
import ProgressBar from '../../renderable/progress-bar';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';

type LanguageActivationChangedResponse = {
  status: MessageInterface[],
  success: boolean,
};

type LanguageUpdatedResponse = {
  packResult: string,
  success: true,
};

type Language = {
  iso: string,
  name: string,
  active: boolean,
  lastUpdate: string,
  dependencies: string[]
};

type ExtensionPack = {
  iso: string,
  exists: boolean,
  lastUpdate: string
};

type Extension = {
  key: string,
  title: string,
  type: string,
  icon: string,
  packs: ExtensionPack[]
};

type LanguagePacksGetDataResponse = {
  languages: Language[],
  extensions: Extension[],
  activeLanguages: string[],
  activeExtensions: string[],
};

/**
 * Module: @typo3/install/module/language-packs
 */
class LanguagePacks extends AbstractInteractableModule {
  private readonly selectorOutputContainer: string = '.t3js-languagePacks-output';
  private readonly selectorContentContainer: string = '.t3js-languagePacks-mainContent';
  private readonly selectorActivateLanguage: string = '.t3js-languagePacks-activateLanguage';
  private readonly selectorActivateLanguageIcon: string = '#t3js-languagePacks-activate-icon';
  private readonly selectorAddLanguageToggle: string = '.t3js-languagePacks-addLanguage-toggle';
  private readonly selectorLanguageInactive: string = '.t3js-languagePacks-inactive';
  private readonly selectorDeactivateLanguage: string = '.t3js-languagePacks-deactivateLanguage';
  private readonly selectorDeactivateLanguageIcon: string = '#t3js-languagePacks-deactivate-icon';
  private readonly selectorUpdate: string = '.t3js-languagePacks-update';
  private readonly selectorLanguageUpdateIcon: string = '#t3js-languagePacks-languageUpdate-icon';
  private readonly selectorNotifications: string = '.t3js-languagePacks-notifications';

  private activeLanguages: string[] = [];
  private activeExtensions: string[] = [];

  private packsUpdateDetails: { [id: string]: number } = {
    toHandle: 0,
    handled: 0,
    updated: 0,
    new: 0,
    failed: 0,
    skipped: 0,
  };

  private notifications: JQuery[] = [];

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
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponse & LanguagePacksGetDataResponse = await response.resolve();
          if (data.success === true) {
            this.activeLanguages = data.activeLanguages;
            this.activeExtensions = data.activeExtensions;
            modalContent.empty().append(data.html);
            const contentContainer: JQuery = modalContent.parent().find(this.selectorContentContainer);
            contentContainer.empty();
            contentContainer.append(this.languageMatrixHtml(data));
            contentContainer.append(this.extensionMatrixHtml(data));
          } else {
            const message = InfoBox.render(Severity.error, 'Something went wrong', '');
            this.addNotification(message);
          }

          this.renderNotifications();
        },
        (error: AjaxResponse): void => {
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
        async (response: AjaxResponse): Promise<void> => {
          const data: LanguageActivationChangedResponse = await response.resolve();
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              const message = InfoBox.render(element.severity, element.title, element.message);
              this.addNotification(message);
            });
          } else {
            const message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            this.addNotification(message);
          }
          this.getData();
        },
        (error: AjaxResponse): void => {
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
        async (response: AjaxResponse): Promise<void> => {
          const data: LanguageActivationChangedResponse = await response.resolve();
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              const message = InfoBox.render(element.severity, element.title, element.message);
              this.addNotification(message);
            });
          } else {
            const message = FlashMessage.render(Severity.error, 'Something went wrong', '');
            this.addNotification(message);
          }
          this.getData();
        },
        (error: AjaxResponse): void => {
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
      skipped: 0,
    };

    $outputContainer.empty().append(
      $('<div>', { 'class': 'progress' }).append(
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
            { 'class': 'text-nowrap' }).text('0 of ' + this.packsUpdateDetails.toHandle + ' language ' +
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
            async (response: AjaxResponse): Promise<void> => {
              const data: LanguageUpdatedResponse = await response.resolve();
              if (data.success === true) {
                this.packsUpdateDetails.handled++;
                if (data.packResult === 'new') {
                  this.packsUpdateDetails.new++;
                } else if (data.packResult === 'update') {
                  this.packsUpdateDetails.updated++;
                } else if (data.packResult === 'skipped') {
                  this.packsUpdateDetails.skipped++;
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

  private packUpdateDone(updateIsoTimes: boolean, isos: string[]): void {
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputContainer);
    if (this.packsUpdateDetails.handled === this.packsUpdateDetails.toHandle) {
      // All done - create summary, update 'last update' of iso list, render main view
      const message = InfoBox.render(
        Severity.ok,
        'Language packs updated',
        this.packsUpdateDetails.new + ' new language ' + LanguagePacks.pluralize(this.packsUpdateDetails.new) + ' downloaded, ' +
        this.packsUpdateDetails.updated + ' language ' + LanguagePacks.pluralize(this.packsUpdateDetails.updated) + ' updated, ' +
        this.packsUpdateDetails.skipped + ' language ' + LanguagePacks.pluralize(this.packsUpdateDetails.skipped) + ' skipped, ' +
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
            async (response: AjaxResponse): Promise<void> => {
              const data = await response.resolve();
              if (data.success === true) {
                this.getData();
              } else {
                const m = FlashMessage.render(Severity.error, 'Something went wrong', '');
                this.addNotification(m);
              }
            },
            (error: AjaxResponse): void => {
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
    const configurationIsWritable = this.getModuleContent().data('configuration-is-writable') === true;
    const $markupContainer = $('<div>');

    const $tbody = $('<tbody>');
    data.languages.forEach((language: any): void => {
      const availableMatrixActions = [];
      const active = language.active;
      if (configurationIsWritable) {
        availableMatrixActions.push($('<a>', {
          'class': 'btn btn-default t3js-languagePacks-deactivateLanguage',
          'data-iso': language.iso,
          'title': 'Deactivate',
        }).append(deactivateIcon));
      }
      availableMatrixActions.push($('<a>', {
        'class': 'btn btn-default t3js-languagePacks-update',
        'data-iso': language.iso,
        'title': 'Download language packs',
      }).append(updateIcon));
      const $tr = $('<tr>');
      if (active) {
        $tbody.append(
          $tr.append(
            $('<td>').text(' ' + language.name).prepend(
              $('<div />', { class: 'btn-group' }).append(
                availableMatrixActions
              ),
            ),
          ),
        );
      } else {
        if (configurationIsWritable) {
          availableMatrixActions.push($('<a>', {
            'class': 'btn btn-default t3js-languagePacks-activateLanguage',
            'data-iso': language.iso,
            'title': 'Activate',
          }).append(activateIcon));
        }
        $tbody.append(
          $tr.addClass('t3-languagePacks-inactive t3js-languagePacks-inactive').css({ 'display': 'none' }).append(
            $('<td>').text(' ' + language.name).prepend(
              $('<div />', { class: 'btn-group' }).append(
                availableMatrixActions
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
    const globalActions = [];
    if (configurationIsWritable) {
      globalActions.push($('<button>', {
        'class': 'btn btn-default t3js-languagePacks-addLanguage-toggle',
        'type': 'button'
      }).append(
        $('<span>').append(activateIcon),
        ' Add language',
      ));
    }
    globalActions.push($('<button>', {
      'class': 'btn btn-default disabled update-all t3js-languagePacks-update',
      'type': 'button',
      'disabled': 'disabled'
    }).append(
      $('<span>').append(updateIcon),
      ' Update all',
    ));
    $markupContainer.append(
      $('<h3>').text('Active languages'),
      $('<table>', { 'class': 'table table-striped table-bordered' }).append(
        $('<thead>').append(
          $('<tr>').append(
            $('<th>').append(
              $('<div />', { class: 'btn-group' }).append(
                globalActions
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
            'src': extension.icon,
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

      data.activeLanguages.forEach((language: string): void => {
        let packFoundForLanguage: boolean = false;
        extension.packs.forEach((pack: any): void => {
          if (pack.iso !== language) {
            return;
          }
          packFoundForLanguage = true;
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
              'title': securityUtility.encodeHtml(tooltip),
            }).append(updateIcon),
          );
        });
        // Render empty colum to avoid disturbed table build up if pack was not found for language.
        if (!packFoundForLanguage) {
          const $column = $('<td>');
          $tr.append($column).append('&nbsp;');
        }
      });
      $tbody.append($tr);

    });

    $markupContainer.append(
      $('<h3>').text('Translation status'),
      $('<table>', { 'class': 'table table-striped table-bordered' }).append(
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

  private addNotification(notification: JQuery): void {
    this.notifications.push(notification);
  }

  private renderNotifications(): void {
    const $notificationBox: JQuery = this.getNotificationBox();
    for (const notification of this.notifications) {
      $notificationBox.append(notification);
    }
    this.notifications = [];
  }
}

export default new LanguagePacks();
