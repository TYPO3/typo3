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
import $ from 'jquery';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import PasswordStrength from './module/password-strength';
import InfoBox from './renderable/info-box';
import ProgressBar from './renderable/progress-bar';
import Severity from './renderable/severity';
import '@typo3/backend/element/icon-element';
import MessageInterface from '@typo3/install/message-interface';
import { selector } from '@typo3/core/literals';

/**
 * Walk through the installation process of TYPO3
 */
class Installer {
  private readonly selectorBody: string = '.t3js-body';
  private readonly selectorModuleContent: string = '.t3js-module-content';
  private readonly selectorMainContent: string = '.t3js-installer-content';
  private readonly selectorProgressBar: string = '.t3js-installer-progress';
  private readonly selectorProgressBarSteps: string = '.t3js-installer-progress-steps';
  private readonly selectorDatabaseConnectOutput: string = '.t3js-installer-databaseConnect-output';
  private readonly selectorDatabaseSelectOutput: string = '.t3js-installer-databaseSelect-output';
  private readonly selectorDatabaseDataOutput: string = '.t3js-installer-databaseData-output';

  constructor() {
    this.initializeEvents();
    DocumentService.ready().then((): void => {
      this.initialize();
    });
  }

  private initializeEvents(): void {
    $(document).on('click', '.t3js-installer-environmentFolders-retry', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.showEnvironmentAndFolders();
    });
    $(document).on('click', '.t3js-installer-environmentFolders-execute', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.executeEnvironmentAndFolders();
    });
    $(document).on('click', '.t3js-installer-databaseConnect-execute', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.executeDatabaseConnect();
    });
    $(document).on('click', '.t3js-installer-databaseSelect-execute', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.executeDatabaseSelect();
    });
    $(document).on('click', '.t3js-installer-databaseData-execute', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.executeDatabaseData();
    });
    $(document).on('click', '.t3js-installer-defaultConfiguration-execute', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.executeDefaultConfiguration();
    });
    $(document).on('click', '.t3-install-form-password-toggle', (evt: JQueryEventObject): void => {
      evt.preventDefault();
      const $element = $(evt.currentTarget);
      const $toggleTarget = $($element.data('toggleTarget'));
      if ($element.attr('data-toggle-state') === 'invisible') {
        $element.attr('data-toggle-state', 'visible');
        $toggleTarget.attr('type', 'text');
      } else {
        $element.attr('data-toggle-state', 'invisible');
        $toggleTarget.attr('type', 'password');
      }
    });
    $(document).on('keyup', '.t3-install-form-password-strength', (): void => {
      PasswordStrength.initialize('.t3-install-form-password-strength');
    });

    // Database connect db driver selection
    $(document).on('change', '#t3js-connect-database-driver', (e: JQueryEventObject): void => {
      const driver: string = $(e.currentTarget).val();
      $('.t3-install-driver-data').hide();
      $('.t3-install-driver-data input').attr('disabled', 'disabled');
      $(selector`#${driver} input`).attr('disabled', null);
      $(selector`#${driver}`).show();
    });
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
    const progressWrapper = document.querySelector(this.selectorProgressBar);
    if (progressWrapper === null) {
      return;
    }
    const progressBarSteps = document.querySelector(this.selectorProgressBarSteps);
    let percent: number = 0;
    if (done !== 0) {
      percent = (done / 5) * 100;
      progressWrapper.setAttribute('aria-label', done + ' of 5');
      progressWrapper.querySelector('.progress-bar').textContent = percent + '%';
      progressBarSteps.querySelector('.progress-steps').textContent = done + ' of 5';
    }

    progressWrapper.setAttribute('aria-valuenow', percent.toString());

    const bar = progressWrapper.querySelector('.progress-bar') as HTMLElement;
    bar.style.width = percent + '%';
  }

  private getMainLayout(): void {
    (new AjaxRequest(this.getUrl('mainLayout')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        $(this.selectorBody).empty().append(data.html);
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
    const $outputContainer: JQuery = $(this.selectorMainContent);
    (new AjaxRequest(this.getUrl('showInstallerNotAvailable')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          $outputContainer.empty().append(data.html);
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
    const $outputContainer: JQuery = $(this.selectorMainContent);
    (new AjaxRequest(this.getUrl('showEnvironmentAndFolders')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
          const $detailContainer: JQuery = $('.t3js-installer-environment-details');
          let hasMessage: boolean = false;
          if (Array.isArray(data.environmentStatusErrors)) {
            data.environmentStatusErrors.forEach((element: any): void => {
              hasMessage = true;
              const message = InfoBox.render(element.severity, element.title, element.message);
              $detailContainer.append(message);
            });
          }
          if (Array.isArray(data.environmentStatusWarnings)) {
            data.environmentStatusWarnings.forEach((element: any): void => {
              hasMessage = true;
              const message = InfoBox.render(element.severity, element.title, element.message);
              $detailContainer.append(message);
            });
          }
          if (Array.isArray(data.structureErrors)) {
            data.structureErrors.forEach((element: any): void => {
              hasMessage = true;
              const message = InfoBox.render(element.severity, element.title, element.message);
              $detailContainer.append(message);
            });
          }
          if (hasMessage) {
            $detailContainer.show();
            $('.t3js-installer-environmentFolders-bad').show();
          } else {
            $('.t3js-installer-environmentFolders-good').show();
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
    const $outputContainer: JQuery = $(this.selectorMainContent);
    (new AjaxRequest(this.getUrl('showDatabaseConnect')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
          $('#t3js-connect-database-driver').trigger('change');
        }
      });
  }

  private executeDatabaseConnect(): void {
    const $outputContainer: JQuery = $(this.selectorDatabaseConnectOutput);
    const postData: Record<string, string> = {
      'install[action]': 'executeDatabaseConnect',
      'install[token]': $(this.selectorModuleContent).data('installer-database-connect-execute-token'),
    };
    for (const element of $(this.selectorBody + ' form').serializeArray()) {
      postData[element.name] = element.value;
    }
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseSelect();
        } else {
          if (Array.isArray(data.status)) {
            $outputContainer.empty();
            data.status.forEach((element: MessageInterface): void => {
              const message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.append(message);
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
    const $outputContainer: JQuery = $(this.selectorMainContent);
    (new AjaxRequest(this.getUrl('showDatabaseSelect')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
        }
      });
  }

  private executeDatabaseSelect(): void {
    const $outputContainer: JQuery = $(this.selectorDatabaseSelectOutput);
    const postData: { [id: string]: string } = {
      'install[action]': 'executeDatabaseSelect',
      'install[token]': $(this.selectorModuleContent).data('installer-database-select-execute-token'),
    };
    for (const element of $(this.selectorBody + ' form').serializeArray()) {
      postData[element.name] = element.value;
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
              const message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.empty().append(message);
            });
          }
        }
      });
  }

  private checkDatabaseRequirements(): void {
    const $outputContainer: JQuery = $(this.selectorDatabaseSelectOutput);
    const postData: Record<string, string> = {
      'install[action]': 'checkDatabaseRequirements',
      'install[token]': $(this.selectorModuleContent).data('installer-database-check-requirements-execute-token'),
    };
    for (const element of $(this.selectorBody + ' form').serializeArray()) {
      postData[element.name] = element.value;
    }
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.checkDatabaseData();
        } else {
          if (Array.isArray(data.status)) {
            $outputContainer.empty();
            data.status.forEach((element: MessageInterface): void => {
              const message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.append(message);
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
    const $outputContainer: JQuery = $(this.selectorMainContent);
    (new AjaxRequest(this.getUrl('showDatabaseData')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
        }
      });
  }

  private executeDatabaseData(): void {
    const $outputContainer: JQuery = $(this.selectorDatabaseDataOutput);
    const postData: Record<string, string> = {
      'install[action]': 'executeDatabaseData',
      'install[token]': $(this.selectorModuleContent).data('installer-database-data-execute-token'),
    };
    for (const element of $(this.selectorBody + ' form').serializeArray()) {
      postData[element.name] = element.value;
    }
    const message: JQuery = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().append(message);
    (new AjaxRequest(this.getUrl()))
      .post(postData)
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          this.showDefaultConfiguration();
        } else {
          if (Array.isArray(data.status)) {
            $outputContainer.empty();
            data.status.forEach((element: MessageInterface): void => {
              const message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.append(message);
            });
          }
        }
      });
  }

  private showDefaultConfiguration(): void {
    const $outputContainer: JQuery = $(this.selectorMainContent);
    this.setProgress(5);
    (new AjaxRequest(this.getUrl('showDefaultConfiguration')))
      .get({ cache: 'no-cache' })
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
        }
      });
  }

  private executeDefaultConfiguration(): void {
    const postData: Record<string, string> = {
      'install[action]': 'executeDefaultConfiguration',
      'install[token]': $(this.selectorModuleContent).data('installer-default-configuration-execute-token'),
    };
    for (const element of $(this.selectorBody + ' form').serializeArray()) {
      postData[element.name] = element.value;
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
