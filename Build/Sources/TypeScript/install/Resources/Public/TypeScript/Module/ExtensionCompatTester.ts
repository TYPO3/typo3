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
import ProgressBar = require('../Renderable/ProgressBar');
import InfoBox = require('../Renderable/InfoBox');
import Severity = require('../Renderable/Severity');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/ExtensionCompatTester
 */
class ExtensionCompatTester implements InteractableModuleInterface {
  private selectorModalBody: string = '.t3js-modal-body';
  private selectorModuleContent: string = '.t3js-module-content';
  private selectorCheckTrigger: string = '.t3js-extensionCompatTester-check';
  private selectorUninstallTrigger: string = '.t3js-extensionCompatTester-uninstall';
  private selectorOutputContainer: string = '.t3js-extensionCompatTester-output';
  private currentModal: JQuery;

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getLoadedExtensionList();

    currentModal.on('click', this.selectorCheckTrigger, (e: JQueryEventObject): void => {
      currentModal.find(this.selectorUninstallTrigger).hide();
      currentModal.find(this.selectorOutputContainer).empty();
      this.getLoadedExtensionList();
    });
    currentModal.on('click', this.selectorUninstallTrigger, (e: JQueryEventObject): void => {
      this.uninstallExtension($(e.target).data('extension'));
    });
  }

  private getLoadedExtensionList(): void {
    this.currentModal.find(this.selectorCheckTrigger).prop('disabled', true);
    this.currentModal.find('.modal-loading').hide();
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = this.currentModal.find(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.append(message);

    $.ajax({
      url: Router.getUrl('extensionCompatTesterLoadedExtensionList'),
      cache: false,
      success: (data: any): void => {
        modalContent.empty().append(data.html);
        const $innerOutputContainer: JQuery = this.currentModal.find(this.selectorOutputContainer);
        const progressBar = ProgressBar.render(Severity.loading, 'Loading...', '');
        $innerOutputContainer.append(progressBar);

        if (data.success === true && Array.isArray(data.extensions)) {
          const loadExtLocalconf = (): void => {
            const promises: Array<any> = [];
            data.extensions.forEach((extension: any): void => {
              promises.push(this.loadExtLocalconf(extension));
            });
            return $.when.apply($, promises).done((): void => {
              const aMessage = InfoBox.render(Severity.ok, 'ext_localconf.php of all loaded extensions successfully loaded', '');
              $innerOutputContainer.append(aMessage);
            });
          };

          const loadExtTables = (): void => {
            const promises: Array<any> = [];
            data.extensions.forEach((extension: any): void => {
              promises.push(this.loadExtTables(extension));
            });
            return $.when.apply($, promises).done((): void => {
              const aMessage = InfoBox.render(Severity.ok, 'ext_tables.php of all loaded extensions successfully loaded', '');
              $innerOutputContainer.append(aMessage);
            });
          };

          $.when(loadExtLocalconf(), loadExtTables()).fail((response: any): void => {
            const aMessage = InfoBox.render(
              Severity.error,
              'Loading ' + response.scope + ' of extension "' + response.extension + '" failed',
            );
            $innerOutputContainer.append(aMessage);
            modalContent.find(this.selectorUninstallTrigger)
              .text('Unload extension "' + response.extension + '"')
              .attr('data-extension', response.extension)
              .show();
          }).always((): void => {
            $innerOutputContainer.find('.alert-loading').remove();
            this.currentModal.find(this.selectorCheckTrigger).prop('disabled', false);
          });
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private loadExtLocalconf(extension: string): JQueryPromise<{}> {
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-load-ext_localconf-token');
    const $ajax = $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      cache: false,
      data: {
        'install': {
          'action': 'extensionCompatTesterLoadExtLocalconf',
          'token': executeToken,
          'extension': extension,
        },
      },
    });

    return $ajax.promise().then(null, (): any => {
      throw {
        scope: 'ext_localconf.php',
        extension: extension,
      };
    });
  }

  private loadExtTables(extension: string): JQueryPromise<{}> {
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-load-ext_tables-token');
    const $ajax = $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      cache: false,
      data: {
        'install': {
          'action': 'extensionCompatTesterLoadExtTables',
          'token': executeToken,
          'extension': extension,
        },
      },
    });

    return $ajax.promise().then(null, (): any => {
      throw {
        scope: 'ext_tables.php',
        extension: extension,
      };
    });
  }

  /**
   * Send an ajax request to uninstall an extension (or multiple extensions)
   *
   * @param extension string of extension(s) - may be comma separated
   */
  private uninstallExtension(extension: string): void {
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-compat-tester-uninstall-extension-token');
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $outputContainer = $(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.append(message);
    $.ajax({
      url: Router.getUrl(),
      cache: false,
      method: 'POST',
      data: {
        'install': {
          'action': 'extensionCompatTesterUninstallExtension',
          'token': executeToken,
          'extension': extension,
        },
      },
      success: (data: any): void => {
        if (data.success) {
          if (Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              const aMessage = InfoBox.render(element.severity, element.title, element.message);
              modalContent.find(this.selectorOutputContainer).empty().append(aMessage);
            });
          }
          $(this.selectorUninstallTrigger).hide();
          this.getLoadedExtensionList();
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }
}

export = new ExtensionCompatTester();
