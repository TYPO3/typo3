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
import { AbstractInteractableModule, type ModuleLoadedResponse } from '../abstract-interactable-module';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { FlashMessage } from '../../renderable/flash-message';
import { InfoBox } from '../../renderable/info-box';
import '../../renderable/language-packs';
import Severity from '../../renderable/severity';
import Router from '../../router';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type MessageInterface from '@typo3/install/message-interface';
import type { ModalElement } from '@typo3/backend/modal';
import type {
  ActivateLanguageEvent,
  DeactivateLanguageEvent,
  DownloadPacksEvent,
  LanguagePacksGetDataResponse
} from '../../renderable/language-packs';
import type { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';

enum Identifiers {
  outputContainer = '.t3js-languagePacks-output',
  contentContainer = '.t3js-languagePacks-mainContent',
  notifications = '.t3js-languagePacks-notifications'
}

type LanguageActivationChangedResponse = {
  status: MessageInterface[],
  success: boolean,
};

type LanguageUpdatedResponse = {
  packResult: string,
  success: true,
};

/**
 * Module: @typo3/install/module/language-packs
 */
class LanguagePacks extends AbstractInteractableModule {
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

  private notifications: Element[] = [];

  private static pluralize(count: number, word: string = 'pack', suffix: string = 's', additionalCount: number = 0): string {
    return count !== 1 && additionalCount !== 1 ? word + suffix : word;
  }

  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    Promise.all([
      this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js'),
      this.loadModuleFrameAgnostic('@typo3/install/renderable/flash-message.js'),
      this.loadModuleFrameAgnostic('@typo3/install/renderable/language-packs.js')
    ]).then((): void => {
      this.getData();
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('languagePacksGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponse & LanguagePacksGetDataResponse = await response.resolve();
          const { success, html, ...state } = data;
          if (success === true) {
            this.activeLanguages = data.activeLanguages;
            this.activeExtensions = data.activeExtensions;
            modalContent.innerHTML = html;
            const contentContainer: HTMLElement = modalContent.parentElement.querySelector(Identifiers.contentContainer);
            contentContainer.innerHTML = '';

            const documentRef = window.location !== window.parent.location ? parent.document : document;

            const languageMatrix = documentRef.createElement('typo3-install-language-matrix');
            languageMatrix.data = state;
            if (this.getModuleContent().dataset.configurationIsWritable === 'true') {
              languageMatrix.setAttribute('configurationIsWritable', '');
            }
            languageMatrix.addEventListener('activate-language', (e: CustomEvent<ActivateLanguageEvent>) => {
              this.activateLanguage(e.detail.iso);
            });
            languageMatrix.addEventListener('deactivate-language', (e: CustomEvent<DeactivateLanguageEvent>) => {
              this.deactivateLanguage(e.detail.iso);
            });
            languageMatrix.addEventListener('download-packs', (e: CustomEvent<DownloadPacksEvent>) => {
              this.updatePacks(e.detail?.iso || undefined, undefined);
            });

            const extensionMatrix = documentRef.createElement('typo3-install-extension-matrix');
            extensionMatrix.data = state;
            extensionMatrix.addEventListener('download-packs', (e: CustomEvent<DownloadPacksEvent>) => {
              this.updatePacks(
                e.detail?.iso || undefined,
                e.detail?.extension || undefined
              );
            });

            contentContainer.append(languageMatrix, extensionMatrix);
          } else {
            this.addNotification(InfoBox.create(Severity.error, 'Something went wrong'));
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
    const outputContainer = this.findInModal(Identifiers.outputContainer);
    this.renderProgressBar(outputContainer);
    this.getNotificationBox().innerHTML = '';
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'languagePacksActivateLanguage',
          token: this.getModuleContent().dataset.languagePacksActivateLanguageToken,
          iso: iso,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: LanguageActivationChangedResponse = await response.resolve();
          outputContainer.innerHTML = '';
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              this.addNotification(InfoBox.create(element.severity, element.title, element.message));
            });
          } else {
            this.addNotification(InfoBox.create(Severity.error, 'Something went wrong'));
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
    const outputContainer = this.findInModal(Identifiers.outputContainer);
    this.renderProgressBar(outputContainer);
    this.getNotificationBox().innerHTML = '';
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'languagePacksDeactivateLanguage',
          token: this.getModuleContent().dataset.languagePacksDeactivateLanguageToken,
          iso: iso,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: LanguageActivationChangedResponse = await response.resolve();
          outputContainer.innerHTML = '';
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              this.addNotification(InfoBox.create(element.severity, element.title, element.message));
            });
          } else {
            this.addNotification(InfoBox.create(Severity.error, 'Something went wrong'));
          }
          this.getData();
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private updatePacks(iso: string | undefined, extension: string | undefined): void {
    const outputContainer = this.findInModal(Identifiers.outputContainer);
    const contentContainer = this.findInModal(Identifiers.contentContainer);
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

    const progressBar = this.renderProgressBar(
      outputContainer,
      this.packsUpdateDetails.toHandle === 1 ? undefined : {
        value: 0,
        max: this.packsUpdateDetails.toHandle,
        label: '0 of ' + this.packsUpdateDetails.toHandle + ' language ' +
          LanguagePacks.pluralize(this.packsUpdateDetails.toHandle) + ' updated'
      }
    );
    contentContainer.innerHTML = '';

    isos.forEach((isoCode: string): void => {
      extensions.forEach((extensionKey: string): void => {
        this.getNotificationBox().innerHTML = '';

        (new AjaxRequest(Router.getUrl()))
          .post({
            install: {
              action: 'languagePacksUpdatePack',
              token: this.getModuleContent().dataset.languagePacksUpdatePackToken,
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
                this.packUpdateDone(updateIsoTimes, isos, progressBar);
              } else {
                this.packsUpdateDetails.handled++;
                this.packsUpdateDetails.failed++;
                this.packUpdateDone(updateIsoTimes, isos, progressBar);
              }
            },
            (): void => {
              this.packsUpdateDetails.handled++;
              this.packsUpdateDetails.failed++;
              this.packUpdateDone(updateIsoTimes, isos, progressBar);
            }
          );
      });
    });
  }

  private packUpdateDone(updateIsoTimes: boolean, isos: string[], progressBar: ProgressBarElement): void {
    const modalContent = this.getModalBody();
    if (this.packsUpdateDetails.handled === this.packsUpdateDetails.toHandle) {
      // All done - create summary, update 'last update' of iso list, render main view
      this.addNotification(InfoBox.create(
        Severity.ok,
        'Language packs updated',
        this.packsUpdateDetails.new + ' new language ' + LanguagePacks.pluralize(this.packsUpdateDetails.new) + ' downloaded, ' +
        this.packsUpdateDetails.updated + ' language ' + LanguagePacks.pluralize(this.packsUpdateDetails.updated) + ' updated, ' +
        this.packsUpdateDetails.skipped + ' language ' + LanguagePacks.pluralize(this.packsUpdateDetails.skipped) + ' skipped, ' +
        this.packsUpdateDetails.failed + ' language ' + LanguagePacks.pluralize(this.packsUpdateDetails.failed) + ' not available'
      ));
      if (updateIsoTimes === true) {
        (new AjaxRequest(Router.getUrl()))
          .post({
            install: {
              action: 'languagePacksUpdateIsoTimes',
              token: this.getModuleContent().dataset.languagePacksUpdateIsoTimesToken,
              isos: isos,
            },
          })
          .then(
            async (response: AjaxResponse): Promise<void> => {
              const data = await response.resolve();
              if (data.success === true) {
                this.getData();
              } else {
                this.addNotification(FlashMessage.create(Severity.error, 'Something went wrong'));
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
      progressBar.value = this.packsUpdateDetails.handled;
      progressBar.label = this.packsUpdateDetails.handled + ' of ' + this.packsUpdateDetails.toHandle + ' language ' +
        LanguagePacks.pluralize(this.packsUpdateDetails.handled, 'pack', 's', this.packsUpdateDetails.toHandle) + ' updated';
    }
  }

  private getNotificationBox(): HTMLElement {
    return this.findInModal(Identifiers.notifications);
  }

  private addNotification(notification: Element): void {
    this.notifications.push(notification);
  }

  private renderNotifications(): void {
    const $notificationBox = this.getNotificationBox();
    for (const notification of this.notifications) {
      $notificationBox.appendChild(notification);
    }
    this.notifications = [];
  }
}

export default new LanguagePacks();
