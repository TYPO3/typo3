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

import $ from 'jquery';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {AbstractInteractableModule} from './module/abstract-interactable-module';
import {AbstractInlineModule} from './module/abstract-inline-module';
import Icons from '@typo3/backend/icons';
import Modal from '@typo3/backend/modal';
import InfoBox from './renderable/info-box';
import ProgressBar from './renderable/progress-bar';
import Severity from './renderable/severity';

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
      if (e.key === 'Enter') {
        e.preventDefault();
        $('.t3js-login-login').trigger('click');
      }
    });

    $(document).on('click', '.card .btn', (e: JQueryEventObject): void => {
      e.preventDefault();

      const $me = $(e.currentTarget);
      const importModule = $me.data('import');
      const inlineState = $me.data('inline');
      const isInline = typeof inlineState !== 'undefined' && parseInt(inlineState, 10) === 1;
      if (isInline) {
        import(importModule).then(({default: aModule}: {default: AbstractInlineModule}): void => {
          aModule.initialize($me);
        });
      } else {
        const modalTitle = $me.closest('.card').find('.card-title').html();
        const modalSize = $me.data('modalSize') || Modal.sizes.large;
        const $modal = Modal.advanced({
          type: Modal.types.default,
          title: modalTitle,
          size: modalSize,
          content: $('<div class="modal-loading">'),
          additionalCssClasses: ['install-tool-modal'],
          callback: (currentModal: any): void => {
            import(importModule).then(({default: aModule}: {default: AbstractInteractableModule}): void => {
              aModule.initialize(currentModal);
            });
          },
        });
        Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).then((icon: any): void => {
          $modal.find('.modal-loading').append(icon);
        });
      }
    });

    const $context = $(this.selectorBody).data('context');
    if ($context === 'backend') {
      this.executeSilentConfigurationUpdate();
    } else {
      this.preAccessCheck();
    }
  }

  public registerInstallToolRoutes(): void {
    if (typeof TYPO3.settings === 'undefined') {
      TYPO3.settings = {
        ajaxUrls: {
          icons: window.location.origin + window.location.pathname + '?install[controller]=icon&install[action]=getIcon',
          icons_cache: window.location.origin + window.location.pathname + '?install[controller]=icon&install[action]=getCacheIdentifier',
        },
      };
    }
  }

  public getUrl(action?: string, controller?: string, query?: string): string {
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
    if (query !== undefined) {
      url = url + '&' + query;
    }
    return url;
  }

  public executeSilentConfigurationUpdate(): void {
    this.updateLoadingInfo('Checking session and executing silent configuration update');
    (new AjaxRequest(this.getUrl('executeSilentConfigurationUpdate', 'layout')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.executeSilentTemplateFileUpdate();
          } else {
            this.executeSilentConfigurationUpdate();
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public executeSilentTemplateFileUpdate(): void {
    this.updateLoadingInfo('Checking session and executing silent template file update');
    (new AjaxRequest(this.getUrl('executeSilentTemplateFileUpdate', 'layout')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.executeSilentExtensionConfigurationSynchronization();
          } else {
            this.executeSilentTemplateFileUpdate();
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  /**
   * Extensions which come with new default settings in ext_conf_template.txt extension
   * configuration files get their new defaults written to LocalConfiguration.
   */
  public executeSilentExtensionConfigurationSynchronization(): void {
    const $outputContainer = $(this.selectorBody);
    this.updateLoadingInfo('Executing silent extension configuration synchronization');
    (new AjaxRequest(this.getUrl('executeSilentExtensionConfigurationSynchronization', 'layout')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.loadMainLayout();
          } else {
            const message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().append(message);
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public loadMainLayout(): void {
    const $outputContainer = $(this.selectorBody);
    const controller = $outputContainer.data('controller');
    this.updateLoadingInfo('Loading main layout');
    (new AjaxRequest(this.getUrl('mainLayout', 'layout', 'install[module]=' + controller)))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            $outputContainer.empty().append(data.html);
            // Mark main module as active in standalone
            if ($(this.selectorBody).data('context') !== 'backend') {
              $outputContainer.find('.t3js-modulemenu-action[data-controller="' + controller + '"]').addClass('modulemenu-action-active');
            }
            this.loadCards();
          } else {
            const message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().append(message);
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public async handleAjaxError(error: AjaxResponse, $outputContainer?: JQuery): Promise<any> {
    let $message: any;
    if (error.response.status === 403) {
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
        + '<code>[\'SYS\'][\'exceptionalErrors\'] => 12290</code></p>'
        + '</div>'
        + '</div>'
        + '<div class="panel-group" role="tablist" aria-multiselectable="true">'
        + '<div class="panel panel-default panel-flat searchhit">'
        + '<div class="panel-heading" role="tab" id="heading-error">'
        + '<h3 class="panel-title">'
        + '<a role="button" data-bs-toggle="collapse" data-bs-parent="#accordion" href="#collapse-error" aria-expanded="true" '
        + 'aria-controls="collapse-error" class="collapsed">'
        + '<span class="caret"></span>'
        + '<strong>Ajax error</strong>'
        + '</a>'
        + '</h3>'
        + '</div>'
        + '<div id="collapse-error" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-error">'
        + '<div class="panel-body">'
        + (await error.response.text())
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
    (new AjaxRequest(this.getUrl('checkEnableInstallToolFile')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.checkLogin();
          } else {
            this.showEnableInstallTool();
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public showEnableInstallTool(): void {
    (new AjaxRequest(this.getUrl('showEnableInstallToolFile')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            $(this.selectorBody).empty().append(data.html);
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public checkLogin(): void {
    (new AjaxRequest(this.getUrl('checkLogin')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.loadMainLayout();
          } else {
            this.showLogin();
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public showLogin(): void {
    (new AjaxRequest(this.getUrl('showLogin')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            $(this.selectorBody).empty().append(data.html);
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public login(): void {
    const $outputContainer: JQuery = $('.t3js-login-output');
    const message: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(message);
    (new AjaxRequest(this.getUrl()))
      .post({
        install: {
          action: 'login',
          token: $('[data-login-token]').data('login-token'),
          password: $('.t3-install-form-input-text').val(),
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.executeSilentConfigurationUpdate();
          } else {
            data.status.forEach((element: any): void => {
              const m: any = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.empty().html(m);
            });
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public logout(): void {
    (new AjaxRequest(this.getUrl('logout')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            this.showEnableInstallTool();
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public loadCards(): void {
    const outputContainer = $(this.selectorMainContent);
    (new AjaxRequest(this.getUrl('cards')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            outputContainer.empty().append(data.html);
          } else {
            const message = InfoBox.render(Severity.error, 'Something went wrong', '');
            outputContainer.empty().append(message);
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }

  public updateLoadingInfo(info: string): void {
    const $outputContainer = $(this.selectorBody);
    $outputContainer.find('#t3js-ui-block-detail').text(info);
  }

  private preAccessCheck(): void {
    this.updateLoadingInfo('Execute pre access check');
    (new AjaxRequest(this.getUrl('preAccessCheck', 'layout')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.installToolLocked) {
            this.checkEnableInstallToolFile();
          } else if (!data.isAuthorized) {
            this.showLogin();
          } else {
            this.executeSilentConfigurationUpdate();
          }
        },
        (error: AjaxResponse): void => {
          this.handleAjaxError(error)
        }
      );
  }
}

export default new Router();
