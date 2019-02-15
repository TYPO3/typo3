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

import * as $ from 'jquery';
import InfoBox = require('./Renderable/InfoBox');
import Severity = require('./Renderable/Severity');
import ProgressBar = require('./Renderable/ProgressBar');
import PasswordStrength = require('./Module/PasswordStrength');

/**
 * Walk through the installation process of TYPO3
 */
class Installer {
  private selectorBody: string = '.t3js-body';
  private selectorModuleContent: string = '.t3js-module-content';
  private selectorMainContent: string = '.t3js-installer-content';
  private selectorProgressBar: string = '.t3js-installer-progress';
  private selectorDatabaseConnectOutput: string = '.t3js-installer-databaseConnect-output';
  private selectorDatabaseSelectOutput: string = '.t3js-installer-databaseSelect-output';
  private selectorDatabaseDataOutput: string = '.t3js-installer-databaseData-output';

  constructor() {
    this.initializeEvents();
    $((): void => {
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
    $(document).on('keyup', '.t3-install-form-password-strength', (): void => {
      PasswordStrength.initialize('.t3-install-form-password-strength');
    });

    // Database connect db driver selection
    $(document).on('change', '#t3js-connect-database-driver', (e: JQueryEventObject): void => {
      let driver: string = $(e.currentTarget).val();
      $('.t3-install-driver-data').hide();
      $('.t3-install-driver-data input').attr('disabled', 'disabled');
      $('#' + driver + ' input').attr('disabled', null);
      $('#' + driver).show();
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
    let $progressBar: JQuery = $(this.selectorProgressBar);
    let percent: number = 0;
    if (done !== 0) {
      percent = (done / 5) * 100;
      $progressBar.find('.progress-bar').empty().text(done + ' / 5 - ' + percent + '% Complete');
    }
    $progressBar
      .find('.progress-bar')
      .css('width', percent + '%')
      .attr('aria-valuenow', percent);
  }

  private getMainLayout(): void {
    $.ajax({
      url: this.getUrl('mainLayout'),
      cache: false,
      success: (data: any): void => {
        $(this.selectorBody).empty().append(data.html);
        this.checkInstallerAvailable();
      },
    });
  }

  private checkInstallerAvailable(): void {
    $.ajax({
      url: this.getUrl('checkInstallerAvailable'),
      cache: false,
      success: (data: any): void => {
        data.success
          ? this.checkEnvironmentAndFolders()
          : this.showInstallerNotAvailable();
      },
    });
  }

  private showInstallerNotAvailable(): void {
    let $outputContainer: JQuery = $(this.selectorMainContent);
    $.ajax({
      url: this.getUrl('showInstallerNotAvailable'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          $outputContainer.empty().append(data.html);
        }
      },
    });
  }

  private checkEnvironmentAndFolders(): void {
    this.setProgress(1);
    $.ajax({
      url: this.getUrl('checkEnvironmentAndFolders'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          this.checkTrustedHostsPattern();
        } else {
          this.showEnvironmentAndFolders();
        }
      },
    });
  }

  private showEnvironmentAndFolders(): void {
    let $outputContainer: JQuery = $(this.selectorMainContent);
    $.ajax({
      url: this.getUrl('showEnvironmentAndFolders'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
          let $detailContainer: JQuery = $('.t3js-installer-environment-details');
          let hasMessage: boolean = false;
          if (Array.isArray(data.environmentStatusErrors)) {
            data.environmentStatusErrors.forEach((element: any): void => {
              hasMessage = true;
              let message: any = InfoBox.render(element.severity, element.title, element.message);
              $detailContainer.append(message);
            });
          }
          if (Array.isArray(data.environmentStatusWarnings)) {
            data.environmentStatusWarnings.forEach((element: any): void => {
              hasMessage = true;
              let message: any = InfoBox.render(element.severity, element.title, element.message);
              $detailContainer.append(message);
            });
          }
          if (Array.isArray(data.structureErrors)) {
            data.structureErrors.forEach((element: any): void => {
              hasMessage = true;
              let message: any = InfoBox.render(element.severity, element.title, element.message);
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
      },
    });
  }

  private executeEnvironmentAndFolders(): void {
    $.ajax({
      url: this.getUrl('executeEnvironmentAndFolders'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          this.checkTrustedHostsPattern();
        } else {
          // @todo message output handling
        }
      },
    });
  }

  private checkTrustedHostsPattern(): void {
    $.ajax({
      url: this.getUrl('checkTrustedHostsPattern'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          this.executeSilentConfigurationUpdate();
        } else {
          this.executeAdjustTrustedHostsPattern();
        }
      },
    });
  }

  private executeAdjustTrustedHostsPattern(): void {
    $.ajax({
      url: this.getUrl('executeAdjustTrustedHostsPattern'),
      cache: false,
      success: (): void => {
        this.executeSilentConfigurationUpdate();
      },
    });
  }

  private executeSilentConfigurationUpdate(): void {
    $.ajax({
      url: this.getUrl('executeSilentConfigurationUpdate'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          this.checkDatabaseConnect();
        } else {
          this.executeSilentConfigurationUpdate();
        }
      },
    });
  }

  private checkDatabaseConnect(): void {
    this.setProgress(2);
    $.ajax({
      url: this.getUrl('checkDatabaseConnect'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          this.checkDatabaseSelect();
        } else {
          this.showDatabaseConnect();
        }
      },
    });
  }

  private showDatabaseConnect(): void {
    let $outputContainer: JQuery = $(this.selectorMainContent);
    $.ajax({
      url: this.getUrl('showDatabaseConnect'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
          $('#t3js-connect-database-driver').trigger('change');
        }
      },
    });
  }

  private executeDatabaseConnect(): void {
    let $outputContainer: JQuery = $(this.selectorDatabaseConnectOutput);
    let postData: any = {
      'install[action]': 'executeDatabaseConnect',
      'install[token]': $(this.selectorModuleContent).data('installer-database-connect-execute-token'),
    };
    $($(this.selectorBody + ' form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    $.ajax({
      url: this.getUrl(),
      cache: false,
      method: 'POST',
      data: postData,
      success: (data: any): void => {
        if (data.success === true) {
          this.checkDatabaseSelect();
        } else {
          if (Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              let message: any = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.empty().append(message);
            });
          }
        }
      },
    });
  }

  private checkDatabaseSelect(): void {
    this.setProgress(3);
    $.ajax({
      url: this.getUrl('checkDatabaseSelect'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          this.checkDatabaseData();
        } else {
          this.showDatabaseSelect();
        }
      },
    });
  }

  private showDatabaseSelect(): void {
    let $outputContainer: JQuery = $(this.selectorMainContent);
    $.ajax({
      url: this.getUrl('showDatabaseSelect'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
        }
      },
    });
  }

  private executeDatabaseSelect(): void {
    let $outputContainer: JQuery = $(this.selectorDatabaseSelectOutput);
    let postData: { [id: string]: string } = {
      'install[action]': 'executeDatabaseSelect',
      'install[token]': $(this.selectorModuleContent).data('installer-database-select-execute-token'),
    };
    $($(this.selectorBody + ' form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    $.ajax({
      url: this.getUrl(),
      cache: false,
      method: 'POST',
      data: postData,
      success: (data: any): void => {
        if (data.success === true) {
          this.checkDatabaseData();
        } else {
          if (Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              let message: any = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.empty().append(message);
            });
          }
        }
      },
    });
  }

  private checkDatabaseData(): void {
    this.setProgress(4);
    $.ajax({
      url: this.getUrl('checkDatabaseData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          this.showDefaultConfiguration();
        } else {
          this.showDatabaseData();
        }
      },
    });
  }

  private showDatabaseData(): void {
    let $outputContainer: JQuery = $(this.selectorMainContent);
    $.ajax({
      url: this.getUrl('showDatabaseData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
        }
      },
    });
  }

  private executeDatabaseData(): void {
    let $outputContainer: JQuery = $(this.selectorDatabaseDataOutput);
    let postData: any = {
      'install[action]': 'executeDatabaseData',
      'install[token]': $(this.selectorModuleContent).data('installer-database-data-execute-token'),
    };
    $($(this.selectorBody + ' form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    let message: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(message);
    $.ajax({
      url: this.getUrl(),
      cache: false,
      method: 'POST',
      data: postData,
      success: (data: any): void => {
        if (data.success === true) {
          this.showDefaultConfiguration();
        } else {
          if (Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              let m: any = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.empty().append(m);
            });
          }
        }
      },
    });
  }

  private showDefaultConfiguration(): void {
    let $outputContainer: JQuery = $(this.selectorMainContent);
    this.setProgress(5);
    $.ajax({
      url: this.getUrl('showDefaultConfiguration'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          $outputContainer.empty().html(data.html);
        }
      },
    });
  }

  private executeDefaultConfiguration(): void {
    let postData: any = {
      'install[action]': 'executeDefaultConfiguration',
      'install[token]': $(this.selectorModuleContent).data('installer-default-configuration-execute-token'),
    };
    $($(this.selectorBody + ' form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    $.ajax({
      url: this.getUrl(),
      cache: false,
      method: 'POST',
      data: postData,
      success: (data: any): void => {
        top.location.href = data.redirect;
      },
    });
  }
}

export = new Installer();
