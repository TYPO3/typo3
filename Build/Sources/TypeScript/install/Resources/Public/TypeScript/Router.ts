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

import {InlineModuleInterface} from './Module/InlineModuleInterface';
import {AbstractInteractableModule} from './Module/AbstractInteractableModule';
import * as $ from 'jquery';
import InfoBox = require('./Renderable/InfoBox');
import Severity = require('./Renderable/Severity');
import ProgressBar = require('./Renderable/ProgressBar');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Icons = require('TYPO3/CMS/Backend/Icons');

class Router {
  private selectorBody: string = '.t3js-body';
  private selectorMainContent: string = '.t3js-module-body';

  public initialize(): void {
    this.registerInstallToolRoutes();

    $(document).on('click', '.t3js-login-lockInstallTool', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.logout();
    });
    $(document).on('click', '.t3js-login-login', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.login();
    });
    $(document).on('keydown', '#t3-install-form-password', (e: JQueryEventObject): void => {
      if (e.keyCode === 13) {
        e.preventDefault();
        $('.t3js-login-login').click();
      }
    });

    $(document).on('click', '.card .btn', (e: JQueryEventObject): void => {
      e.preventDefault();

      const $me = $(e.currentTarget);
      const requireModule = $me.data('require');
      const inlineState = $me.data('inline');
      const isInline = typeof inlineState !== 'undefined' && parseInt(inlineState, 10) === 1;
      if (isInline) {
        require([requireModule], (aModule: InlineModuleInterface): void => {
          aModule.initialize($me);
        });
      } else {
        const modalTitle = $me.closest('.card').find('.card-title').html();
        const modalSize = $me.data('modalSize') || Modal.sizes.large;

        Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done((icon: any): void => {
          const configuration = {
            type: Modal.types.default,
            title: modalTitle,
            size: modalSize,
            content: $('<div class="modal-loading">').append(icon),
            additionalCssClasses: ['install-tool-modal'],
            callback: (currentModal: any): void => {
              require([requireModule], (aModule: AbstractInteractableModule): void => {
                aModule.initialize(currentModal);
              });
            },
          };
          Modal.advanced(configuration);
        });
      }
    });

    this.executeSilentConfigurationUpdate();
  }

  public registerInstallToolRoutes(): void {
    if (typeof TYPO3.settings === 'undefined') {
      TYPO3.settings = {
        ajaxUrls: {
          icons: '?install[controller]=icon&install[action]=getIcon',
          icons_cache: '?install[controller]=icon&install[action]=getCacheIdentifier',
        },
      };
    }
  }

  public getUrl(action?: string, controller?: string): string {
    const context = $(this.selectorBody).data('context');
    let url = location.href;
    url = url.replace(location.search, '');
    if (controller === undefined) {
      controller = $(this.selectorBody).data('controller');
    }
    url = url + '?install[controller]=' + controller;
    if (context !== undefined && context !== '') {
      url = url + '&install[context]=' + context;
    }
    if (action !== undefined) {
      url = url + '&install[action]=' + action;
    }
    return url;
  }

  public executeSilentConfigurationUpdate(): void {
    this.updateLoadingInfo('Checking session and executing silent configuration update');
    $.ajax({
      url: this.getUrl('executeSilentConfigurationUpdate', 'layout'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          this.executeSilentExtensionConfigurationSynchronization();
        } else {
          this.executeSilentConfigurationUpdate();
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  /**
   * Extensions which come with new default settings in ext_conf_template.txt extension
   * configuration files get their new defaults written to LocalConfiguration.
   */
  public executeSilentExtensionConfigurationSynchronization(): void {
    const $outputContainer = $(this.selectorBody);
    this.updateLoadingInfo('Executing silent extension configuration synchronization');
    $.ajax({
      url: this.getUrl('executeSilentExtensionConfigurationSynchronization', 'layout'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          this.loadMainLayout();
        } else {
          const message = InfoBox.render(Severity.error, 'Something went wrong', '');
          $outputContainer.empty().append(message);
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public loadMainLayout(): void {
    const $outputContainer = $(this.selectorBody);
    this.updateLoadingInfo('Loading main layout');
    $.ajax({
      url: this.getUrl('mainLayout', 'layout'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
          $outputContainer.empty().append(data.html);
          // Mark main module as active in standalone
          if ($(this.selectorBody).data('context') !== 'backend') {
            const controller = $outputContainer.data('controller');
            $outputContainer.find('.t3js-mainmodule[data-controller="' + controller + '"]').addClass('active');
          }
          this.loadCards();
        } else {
          const message = InfoBox.render(Severity.error, 'Something went wrong', '');
          $outputContainer.empty().append(message);
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public handleAjaxError(xhr: XMLHttpRequest, $outputContainer?: JQuery): void {
    let $message: any;
    if (xhr.status === 403) {
      // Install tool session expired - depending on context render error message or login
      const $context = $(this.selectorBody).data('context');
      if ($context === 'backend') {
        $message = InfoBox.render(Severity.error, 'The install tool session expired. Please reload the backend and try again.');
        $(this.selectorBody).empty().append($message);
      } else {
        this.checkEnableInstallToolFile();
      }
    } else {
      // @todo Recovery tests should be started here
      const url = this.getUrl(undefined, 'upgrade');
      $message = $(
        '<div class="t3js-infobox callout callout-sm callout-danger">'
          + '<div class="callout-body">'
            + '<p>Something went wrong. Please use <b><a href="' + url + '">Check for broken'
            + ' extensions</a></b> to see if a loaded extension breaks this part of the install tool'
            + ' and unload it.</p>'
            + '<p>The box below may additionally reveal further details on what went wrong depending on your debug settings.'
            + ' It may help to temporarily switch to debug mode using <b>Settings > Configuration Presets > Debug settings.</b></p>'
            + '<p>If this error happens at an early state and no full exception back trace is shown, it may also help'
            + ' to manually increase debugging output in <code>typo3conf/LocalConfiguration.php</code>:'
            + '<code>[\'BE\'][\'debug\'] => true</code>, <code>[\'SYS\'][\'devIPmask\'] => \'*\'</code>, '
            + '<code>[\'SYS\'][\'displayErrors\'] => 1</code>,'
            + '<code>[\'SYS\'][\'systemLogLevel\'] => 0</code>, <code>[\'SYS\'][\'exceptionalErrors\'] => 12290</code></p>'
          + '</div>'
        + '</div>'
        + '<div class="panel-group" role="tablist" aria-multiselectable="true">'
          + '<div class="panel panel-default panel-flat searchhit">'
            + '<div class="panel-heading" role="tab" id="heading-error">'
              + '<h3 class="panel-title">'
                + '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-error" aria-expanded="true" '
                    + 'aria-controls="collapse-error" class="collapsed">'
                  + '<span class="caret"></span>'
                  + '<strong>Ajax error</strong>'
                + '</a>'
              + '</h3>'
            + '</div>'
            + '<div id="collapse-error" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-error">'
              + '<div class="panel-body">'
                + xhr.responseText
              + '</div>'
            + '</div>'
          + '</div>'
        + '</div>',
      );

      if (typeof $outputContainer !== 'undefined') {
        // Write to given output container. This is typically a modal if given
        $($outputContainer).empty().html($message);
      } else {
        // Else write to main frame
        $(this.selectorBody).empty().html($message);
      }
    }
  }

  public checkEnableInstallToolFile(): void {
    $.ajax({
      url: this.getUrl('checkEnableInstallToolFile'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          this.checkLogin();
        } else {
          this.showEnableInstallTool();
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public showEnableInstallTool(): void {
    $.ajax({
      url: this.getUrl('showEnableInstallToolFile'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          $(this.selectorBody).empty().append(data.html);
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public checkLogin(): void {
    $.ajax({
      url: this.getUrl('checkLogin'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          this.loadMainLayout();
        } else {
          this.showLogin();
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public showLogin(): void {
    $.ajax({
      url: this.getUrl('showLogin'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          $(this.selectorBody).empty().append(data.html);
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public login(): void {
    const $outputContainer: JQuery = $('.t3js-login-output');
    const message: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(message);
    $.ajax({
      url: this.getUrl(),
      cache: false,
      method: 'POST',
      data: {
        'install': {
          'action': 'login',
          'token': $('[data-login-token]').data('login-token'),
          'password': $('.t3-install-form-input-text').val(),
        },
      },
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          this.executeSilentConfigurationUpdate();
        } else {
          data.status.forEach((element: any): void => {
            const m: any = InfoBox.render(element.severity, element.title, element.message);
            $outputContainer.empty().html(m);
          });
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public logout(): void {
    $.ajax({
      url: this.getUrl('logout'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true) {
          this.showEnableInstallTool();
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public loadCards(): void {
    const outputContainer = $(this.selectorMainContent);
    $.ajax({
      url: this.getUrl('cards'),
      cache: false,
      success: (data: { [key: string]: any }): void => {
        if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
          outputContainer.empty().append(data.html);
        } else {
          const message = InfoBox.render(Severity.error, 'Something went wrong', '');
          outputContainer.empty().append(message);
        }
      },
      error: (xhr: JQueryXHR): void => {
        this.handleAjaxError(xhr);
      },
    });
  }

  public updateLoadingInfo(info: string): void {
    const $outputContainer = $(this.selectorBody);
    $outputContainer.find('#t3js-ui-block-detail').text(info);
  }
}

export = new Router();
