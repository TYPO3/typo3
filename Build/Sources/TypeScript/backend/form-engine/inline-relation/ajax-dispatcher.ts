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

import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {JavaScriptItemProcessor} from '@typo3/core/java-script-item-processor';
import Notification from '../../notification';
import Utility from '../../utility';

interface Context {
  config: Object;
  hmac: string;
}

interface Message {
  title: string;
  message: string;
}

export interface AjaxDispatcherResponse {
  hasErrors: boolean;
  messages: Message[];
  stylesheetFiles: string[];
  inlineData: object;
  requireJsModules: string[];
  scriptCall: string[];
  scriptItems?: any[];
}

export class AjaxDispatcher {
  private readonly objectGroup: string = null;

  constructor(objectGroup: string) {
    this.objectGroup = objectGroup;
  }

  public newRequest(endpoint: string): AjaxRequest {
    return new AjaxRequest(endpoint);
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

  public send(request: AjaxRequest, params: Array<string>): Promise<any> {
    const sentRequest = request.post(this.createRequestBody(params)).then(async (response: AjaxResponse): Promise<any> => {
      return this.processResponse(await response.resolve());
    });
    sentRequest.catch((reason: Error): void => {
      Notification.error('Error ' + reason.message);
    });

    return sentRequest;
  }

  private createRequestBody(input: Array<string>): { [key: string]: string } {
    const body: { [key: string]: string } = {};
    for (let i = 0; i < input.length; i++) {
      body['ajax[' + i + ']'] = input[i];
    }

    body['ajax[context]'] = JSON.stringify(this.getContext());

    return body;
  }

  private getContext(): Context {
    let context: Context;

    if (typeof TYPO3.settings.FormEngineInline.config[this.objectGroup] !== 'undefined'
      && typeof TYPO3.settings.FormEngineInline.config[this.objectGroup].context !== 'undefined'
    ) {
      context = TYPO3.settings.FormEngineInline.config[this.objectGroup].context;
    }

    return context;
  }

  private processResponse(json: AjaxDispatcherResponse): AjaxDispatcherResponse {
    if (json.hasErrors) {
      for (const message of json.messages) {
        Notification.error(message.title, message.message);
      }
    }

    // If there are elements they should be added to the <HEAD> tag (e.g. for RTEhtmlarea):
    if (json.stylesheetFiles) {
      for (const [index, stylesheetFile] of json.stylesheetFiles.entries()) {
        if (!stylesheetFile) {
          break;
        }
        const element = document.createElement('link');
        element.rel = 'stylesheet';
        element.type = 'text/css';
        element.href = stylesheetFile;
        document.querySelector('head').appendChild(element);
        delete json.stylesheetFiles[index];
      }
    }

    if (typeof json.inlineData === 'object') {
      TYPO3.settings.FormEngineInline = Utility.mergeDeep(TYPO3.settings.FormEngineInline, json.inlineData);
    }

    if (json.scriptItems instanceof Array && json.scriptItems.length > 0) {
      const processor = new JavaScriptItemProcessor();
      processor.processItems(json.scriptItems);
    }

    // @todo deprecate or remove with TYPO3 v12.0
    if (typeof json.requireJsModules === 'object') {
      for (let requireJsModule of json.requireJsModules) {
        new Function(requireJsModule)();
      }
    }
    // TODO: This is subject to be removed
    // @todo deprecate or remove with TYPO3 v12.0
    if (json.scriptCall && json.scriptCall.length > 0) {
      for (const scriptCall of json.scriptCall) {
        // eslint-disable-next-line no-eval
        eval(scriptCall);
      }
    }

    return json;
  }
}
