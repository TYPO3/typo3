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

import {AjaxRequest} from './AjaxRequest';
import * as $ from 'jquery';
import Notification = require('../../Notification');

export class AjaxDispatcher {
  private readonly objectGroup: string = null;

  constructor(objectGroup: string) {
    this.objectGroup = objectGroup;
  }

  public newRequest(endpoint: string): AjaxRequest {
    return new AjaxRequest(endpoint, this.objectGroup);
  }

  /**
   * @param {String} routeName
   */
  public getEndpoint(routeName: string): string {
    if (typeof TYPO3.settings.ajaxUrls[routeName] !== 'undefined') {
      return TYPO3.settings.ajaxUrls[routeName];
    }

    throw 'Undefined endpoint for route "' + routeName + '"';
  }

  public send(request: AjaxRequest): JQueryXHR {
    const xhr = $.ajax(request.getEndpoint(), request.getOptions());

    xhr.done((): void => {
      this.processResponse(xhr);
    }).fail((): void => {
      Notification.error('Error ' + xhr.status, xhr.statusText);
    });

    return xhr;
  }

  private processResponse(xhr: JQueryXHR): void {
    const json = xhr.responseJSON;

    if (json.hasErrors) {
      $.each(json.messages, (position: number, message: { [key: string]: string }): void => {
        Notification.error(message.title, message.message);
      });
    }

    // If there are elements they should be added to the <HEAD> tag (e.g. for RTEhtmlarea):
    if (json.stylesheetFiles) {
      $.each(json.stylesheetFiles, (index: number, stylesheetFile: string): void => {
        if (!stylesheetFile) {
          return;
        }
        const element = document.createElement('link');
        element.rel = 'stylesheet';
        element.type = 'text/css';
        element.href = stylesheetFile;
        document.querySelector('head').appendChild(element);
        delete json.stylesheetFiles[index];
      });
    }

    if (typeof json.inlineData === 'object') {
      TYPO3.settings.FormEngineInline = $.extend(true, TYPO3.settings.FormEngineInline, json.inlineData);
    }

    if (typeof json.requireJsModules === 'object') {
      for (let requireJsModule of json.requireJsModules) {
        new Function(requireJsModule)();
      }
    }

    // TODO: This is subject to be removed
    if (json.scriptCall && json.scriptCall.length > 0) {
      $.each(json.scriptCall, (index: number, value: string): void => {
        // eslint-disable-next-line no-eval
        eval(value);
      });
    }
  }
}
