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

class InteractionRequest {
  public readonly type: string;
  public readonly parentRequest: InteractionRequest;
  protected processed: boolean = false;
  protected processedData: any = null;

  constructor(type: string, parentRequest: InteractionRequest = null) {
    this.type = type;
    this.parentRequest = parentRequest;
  }

  public get outerMostRequest(): InteractionRequest {
    // eslint-disable-next-line @typescript-eslint/no-this-alias
    let request: InteractionRequest = this;
    while (request.parentRequest instanceof InteractionRequest) {
      request = request.parentRequest;
    }
    return request;
  }

  public isProcessed(): boolean {
    return this.processed;
  }

  public getProcessedData(): any {
    return this.processedData;
  }

  public setProcessedData(processedData: any = null): void {
    this.processed = true;
    this.processedData = processedData;
  }
}

export default InteractionRequest;
