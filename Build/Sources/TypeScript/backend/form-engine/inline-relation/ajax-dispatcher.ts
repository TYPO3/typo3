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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { JavaScriptItemProcessor } from '@typo3/core/java-script-item-processor';
import Notification from '../../notification';
import Utility from '../../utility';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';

export interface Context {
  config: object;
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

  public send(request: AjaxRequest, params: Array<string>): Promise<AjaxDispatcherResponse> {
    const sentRequest = request.post(this.createRequestBody(params)).then(async (response: AjaxResponse): Promise<AjaxDispatcherResponse> => {
      return this.processResponse(await response.resolve());
    });
    sentRequest.catch((reason: Error): void => {
      Notification.error('Error ' + reason.message);
    });

    return sentRequest;
  }

  private createRequestBody(input: Array<string>): Record<string, string> {
    const body: Record<string, string> = {};
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

    if (json.stylesheetFiles) {
      document.querySelector('head').append(
        ...json.stylesheetFiles.filter(file => file).map(file => {
          const element = document.createElement('link');
          element.rel = 'stylesheet';
          element.type = 'text/css';
          element.href = file;
          return element;
        })
      );
    }

    if (typeof json.inlineData === 'object') {
      TYPO3.settings.FormEngineInline = Utility.mergeDeep(TYPO3.settings.FormEngineInline, json.inlineData) as typeof TYPO3.settings.FormEngineInline;
    }

    if (json.scriptItems instanceof Array && json.scriptItems.length > 0) {
      const processor = new JavaScriptItemProcessor();
      processor.processItems(json.scriptItems);
    }

    return json;
  }
}
