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

import SimpleResponseInterface from './simple-response-interface';

export class AjaxResponse {
  public readonly response: Response;
  private resolvedBody: string | any;

  constructor(response: Response) {
    this.response = response;
  }

  public async resolve(expectedType?: string): Promise<string | any> {
    // streams can only be read once
    // (otherwise response would have to be cloned)
    if (typeof this.resolvedBody !== 'undefined') {
      return this.resolvedBody;
    }
    const contentType: string = this.response.headers.get('Content-Type') ?? '';
    if (expectedType === 'json' || contentType.startsWith('application/json')) {
      this.resolvedBody = await this.response.json();
    } else {
      this.resolvedBody = await this.response.text();
    }
    return this.resolvedBody;
  }

  public raw(): Response {
    return this.response;
  }

  /**
   * Dereferences response data from current `window` scope. A dereferenced
   * response (`SimpleResponseInterface`) can be used in events or messages
   * for broadcasting to other windows/frames.
   */
  public async dereference(): Promise<SimpleResponseInterface> {
    const headers = new Map<string, string>();
    this.response.headers.forEach((value: string, name: string) => headers.set(name, value));
    return {
      status: this.response.status,
      headers: headers,
      body: await this.resolve()
    } as SimpleResponseInterface;
  }
}
