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

interface Context {
  config: Object;
  hmac: string;
}

export class AjaxRequest {
  private readonly endpoint: string = null;
  private readonly objectGroup: string = null;
  private params: string = '';
  private context: Context = null;

  constructor(endpoint: string, objectGroup: string) {
    this.endpoint = endpoint;
    this.objectGroup = objectGroup;
  }

  public withContext(): AjaxRequest {
    let context: Context;

    if (typeof TYPO3.settings.FormEngineInline.config[this.objectGroup] !== 'undefined'
      && typeof TYPO3.settings.FormEngineInline.config[this.objectGroup].context !== 'undefined'
    ) {
      context = TYPO3.settings.FormEngineInline.config[this.objectGroup].context;
    }

    this.context = context;

    return this;
  }

  public withParams(params: Array<string>): AjaxRequest {
    for (let i = 0; i < params.length; i++) {
      this.params += '&ajax[' + i + ']=' + encodeURIComponent(params[i]);
    }

    return this;
  }

  public getEndpoint(): string {
    return this.endpoint;
  }

  public getOptions(): { [key: string]: string} {
    let urlParams = this.params;
    if (this.context) {
      urlParams += '&ajax[context]=' + encodeURIComponent(JSON.stringify(this.context));
    }

    return {
      type: 'POST',
      data: urlParams,
    };
  }
}
