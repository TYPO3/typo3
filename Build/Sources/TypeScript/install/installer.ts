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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import PasswordStrength from './module/password-strength';
import { InfoBox } from './renderable/info-box';
import '@typo3/backend/element/icon-element';
import MessageInterface from '@typo3/install/message-interface';
import { selector } from '@typo3/core/literals';
import '@typo3/backend/element/progress-bar-element';
import type { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';

enum Identifiers {
  body = '.t3js-body',
  moduleContent = '.t3js-module-content',
  mainContent = '.t3js-installer-content',
  progressBar = '.t3js-installer-progress',
  databaseConnectOutput = '.t3js-installer-databaseConnect-output',
  databaseSelectOutput = '.t3js-installer-databaseSelect-output',
  databaseDataOutput = '.t3js-installer-databaseData-output'
}

/**
 * Walk through the installation process of TYPO3
 */
class Installer {
  constructor() {
    this.initializeEvents();
    DocumentService.ready().then((): void => {
      this.initialize();
    });
  }

  private initializeEvents(): void {
    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      this.showEnvironmentAndFolders();
    }).delegateTo(document, '.t3js-installer-environmentFolders-retry');
    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      this.executeEnvironmentAndFolders();
    }).delegateTo(document, '.t3js-installer-environmentFolders-execute');
    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      this.executeDatabaseConnect();
    }).delegateTo(document, '.t3js-installer-databaseConnect-execute');
    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      this.executeDatabaseSelect();
    }).delegateTo(document, '.t3js-installer-databaseSelect-execute');
    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      this.executeDatabaseData();
    }).delegateTo(document, '.t3js-installer-databaseData-execute');
    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      this.executeDefaultConfiguration();
    }).delegateTo(document, '.t3js-installer-defaultConfiguration-execute');
    new RegularEvent('click', (evt: Event, element: HTMLElement): void => {
      evt.preventDefault();
      const toggleTarget = document.querySelector(element.dataset.toggleTarget);
      if (element.dataset.toggleState === 'invisible') {
        element.dataset.toggleState = 'visible';
        toggleTarget.setAttribute('type', 'text');
      } else {
        element.dataset.toggleState = 'invisible';
        toggleTarget.setAttribute('type', 'password');
      }
    }).delegateTo(document, '.t3-install-form-password-toggle');

    // Database connect db driver selection
    new RegularEvent('change', (e: Event, target: HTMLSelectElement): void => {
      const driver: string = target.value;
      document.querySelectorAll('.t3-install-driver-data').forEach(el => el.setAttribute('hidden', ''));
      document.querySelectorAll('.t3-install-driver-data input').forEach(el => el.setAttribute('disabled', 'disabled'));
      document.querySelectorAll(selector`#${driver} input`).forEach(el => el.removeAttribute('disabled'));
      document.querySelector('#' + driver)?.removeAttribute('hidden');
    }).delegateTo(document, '#t3js-connect-database-driver');
  }

  private initialize(): void {
    this.setProgress(0);
    this.getMainLayout();
  }

  private getUrl(action?: string): string {
    let url: string = location.href;
    url = url.replace(location.search, '');
    if (action !== undefined) {
      url = url + '?install[action]=' + action;
    }
    return url;
  }

  private setProgress(done: number): void {
    const progressBar = document.querySelector(Identifiers.progressBar) as ProgressBarElement;
    if (progressBar === null) {
      return;
    }
    if (done !== 0) {
      progressBar.value = done;
      progressBar.label = `Step ${done} of 5 completed`;
    }
  }

  private getMainLayout(): void {
    (new AjaxRequest(this.getUrl('mainLayout')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        document.querySelector(Identifiers.body).innerHTML = data.html;
        this.checkInstallerAvailable();
      });
  }

  private checkInstallerAvailable(): void {
    (new AjaxRequest(this.getUrl('checkInstallerAvailable')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success) {
          this.checkEnvironmentAndFolders();
        } else {
          this.showInstallerNotAvailable();
        }
      });
  }

  private showInstallerNotAvailable(): void {
    const outputContainer = document.querySelector(Identifiers.mainContent);
    (new AjaxRequest(this.getUrl('showInstallerNotAvailable')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          outputContainer.innerHTML = data.html;
        }
      });
  }

  private checkEnvironmentAndFolders(): void {
    this.setProgress(1);
    (new AjaxRequest(this.getUrl('checkEnvironmentAndFolders')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkTrustedHostsPattern();
        } else {
          this.showEnvironmentAndFolders();
        }
      });
  }

  private showEnvironmentAndFolders(): void {
    const outputContainer = document.querySelector(Identifiers.mainContent);
    (new AjaxRequest(this.getUrl('showEnvironmentAndFolders')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          outputContainer.innerHTML = data.html;
          const detailContainer = document.querySelector('.t3js-installer-environment-details');
          let hasMessage: boolean = false;
          if (Array.isArray(data.environmentStatusErrors)) {
            data.environmentStatusErrors.forEach((element: any): void => {
              hasMessage = true;
              detailContainer.append(InfoBox.create(element.severity, element.title, element.message));
            });
          }
          if (Array.isArray(data.environmentStatusWarnings)) {
            data.environmentStatusWarnings.forEach((element: any): void => {
              hasMessage = true;
              detailContainer.append(InfoBox.create(element.severity, element.title, element.message));
            });
          }
          if (Array.isArray(data.structureErrors)) {
            data.structureErrors.forEach((element: any): void => {
              hasMessage = true;
              detailContainer.append(InfoBox.create(element.severity, element.title, element.message));
            });
          }
          if (hasMessage) {
            detailContainer.removeAttribute('hidden');
            document.querySelectorAll('.t3js-installer-environmentFolders-bad')
              .forEach(el => el.removeAttribute('hidden'));
          } else {
            document.querySelectorAll('.t3js-installer-environmentFolders-good')
              .forEach(el => el.removeAttribute('hidden'));
          }
        }
      });
  }

  private executeEnvironmentAndFolders(): void {
    (new AjaxRequest(this.getUrl('executeEnvironmentAndFolders')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkTrustedHostsPattern();
        } else {
          // @todo message output handling
        }
      });
  }

  private checkTrustedHostsPattern(): void {
    (new AjaxRequest(this.getUrl('checkTrustedHostsPattern')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.executeSilentConfigurationUpdate();
        } else {
          this.executeAdjustTrustedHostsPattern();
        }
      });
  }

  private executeAdjustTrustedHostsPattern(): void {
    (new AjaxRequest(this.getUrl('executeAdjustTrustedHostsPattern')))
      .get({ cache: 'no-cache' })
      .then((): void => {
        this.executeSilentConfigurationUpdate();
      });
  }

  private executeSilentConfigurationUpdate(): void {
    (new AjaxRequest(this.getUrl('executeSilentConfigurationUpdate')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.executeSilentTemplateFileUpdate();
        } else {
          this.executeSilentConfigurationUpdate();
        }
      });
  }

  private executeSilentTemplateFileUpdate(): void {
    (new AjaxRequest(this.getUrl('executeSilentTemplateFileUpdate')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseConnect();
        } else {
          this.executeSilentTemplateFileUpdate();
        }
      });
  }

  private checkDatabaseConnect(): void {
    this.setProgress(2);
    (new AjaxRequest(this.getUrl('checkDatabaseConnect')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseSelect();
        } else {
          this.showDatabaseConnect();
        }
      });
  }

  private showDatabaseConnect(): void {
    const outputContainer = document.querySelector(Identifiers.mainContent);
    (new AjaxRequest(this.getUrl('showDatabaseConnect')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          outputContainer.innerHTML = data.html;
          document.querySelector('#t3js-connect-database-driver').dispatchEvent(new Event('change', { bubbles: true }));
          PasswordStrength.initialize(document.querySelector('.t3-install-form-password-strength'));
        }
      });
  }

  private executeDatabaseConnect(): void {
    const outputContainer = document.querySelector(Identifiers.databaseConnectOutput);
    const postData: Record<string, string> = {
      'install[action]': 'executeDatabaseConnect',
      'install[token]': (document.querySelector(Identifiers.moduleContent) as HTMLElement).dataset.installerDatabaseConnectExecuteToken,
    };
    for (const [name, value] of new FormData(document.querySelector(Identifiers.body + ' form'))) {
      postData[name] = value.toString();
    }
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseSelect();
        } else {
          if (Array.isArray(data.status)) {
            outputContainer.replaceChildren();
            data.status.forEach((element: MessageInterface): void => {
              outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
            });
          }
        }
      });
  }

  private checkDatabaseSelect(): void {
    this.setProgress(3);
    (new AjaxRequest(this.getUrl('checkDatabaseSelect')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseData();
        } else {
          this.showDatabaseSelect();
        }
      });
  }

  private showDatabaseSelect(): void {
    const outputContainer = document.querySelector(Identifiers.mainContent);
    (new AjaxRequest(this.getUrl('showDatabaseSelect')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          outputContainer.innerHTML = data.html;
        }
      });
  }

  private executeDatabaseSelect(): void {
    const outputContainer = document.querySelector(Identifiers.databaseSelectOutput);
    const postData: { [id: string]: string } = {
      'install[action]': 'executeDatabaseSelect',
      'install[token]': (document.querySelector(Identifiers.moduleContent) as HTMLElement).dataset.installerDatabaseSelectExecuteToken,
    };
    for (const [name, value] of new FormData(document.querySelector(Identifiers.body + ' form'))) {
      postData[name] = value.toString();
    }
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseRequirements();
        } else {
          if (Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              outputContainer.replaceChildren(InfoBox.create(element.severity, element.title, element.message));
            });
          }
        }
      });
  }

  private checkDatabaseRequirements(): void {
    const outputContainer = document.querySelector(Identifiers.databaseSelectOutput);
    const postData: Record<string, string> = {
      'install[action]': 'checkDatabaseRequirements',
      'install[token]': (document.querySelector(Identifiers.moduleContent) as HTMLElement).dataset.installerDatabaseCheckRequirementsExecuteToken,
    };
    for (const [name, value] of new FormData(document.querySelector(Identifiers.body + ' form'))) {
      postData[name] = value.toString();
    }
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseData();
        } else {
          if (Array.isArray(data.status)) {
            outputContainer.replaceChildren();
            data.status.forEach((element: MessageInterface): void => {
              outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
            });
          }
        }
      });
  }

  private checkDatabaseData(): void {
    this.setProgress(4);
    (new AjaxRequest(this.getUrl('checkDatabaseData')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.showDefaultConfiguration();
        } else {
          this.showDatabaseData();
        }
      });
  }

  private showDatabaseData(): void {
    const outputContainer = document.querySelector(Identifiers.mainContent);
    (new AjaxRequest(this.getUrl('showDatabaseData')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          outputContainer.innerHTML = data.html;
          PasswordStrength.initialize(document.querySelector('.t3-install-form-password-strength'));
        }
      });
  }

  private executeDatabaseData(): void {
    const outputContainer = document.querySelector(Identifiers.databaseDataOutput);
    const postData: Record<string, string> = {
      'install[action]': 'executeDatabaseData',
      'install[token]': (document.querySelector(Identifiers.moduleContent) as HTMLElement).dataset.installerDatabaseDataExecuteToken,
    };
    for (const [name, value] of new FormData(document.querySelector(Identifiers.body + ' form'))) {
      postData[name] = value.toString();
    }
    const progressBar = document.createElement('typo3-backend-progress-bar');
    outputContainer.replaceChildren(progressBar);
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.showDefaultConfiguration();
        } else {
          if (Array.isArray(data.status)) {
            outputContainer.replaceChildren();
            data.status.forEach((element: MessageInterface): void => {
              outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
            });
          }
        }
      });
  }

  private showDefaultConfiguration(): void {
    const outputContainer = document.querySelector(Identifiers.mainContent);
    this.setProgress(5);
    (new AjaxRequest(this.getUrl('showDefaultConfiguration')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          outputContainer.innerHTML = data.html;
        }
      });
  }

  private executeDefaultConfiguration(): void {
    const postData: Record<string, string> = {
      'install[action]': 'executeDefaultConfiguration',
      'install[token]': (document.querySelector(Identifiers.moduleContent) as HTMLElement).dataset.installerDefaultConfigurationExecuteToken,
    };
    for (const [name, value] of new FormData(document.querySelector(Identifiers.body + ' form'))) {
      postData[name] = value.toString();
    }
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        top.location.href = data.redirect;
      });
  }
}

export default new Installer();
