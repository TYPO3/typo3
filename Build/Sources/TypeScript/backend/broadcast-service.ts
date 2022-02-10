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

import 'broadcastchannel';
import {BroadcastMessage} from '@typo3/backend/broadcast-message';
import {MessageUtility} from '@typo3/backend/utility/message-utility';

/**
 * @module @typo3/backend/broadcast-service
 */
class BroadcastService {
  private readonly channel: BroadcastChannel;

  public get isListening(): boolean {
    return typeof this.channel.onmessage === 'function';
  }

  private static onMessage(evt: MessageEvent): void {
    if (!MessageUtility.verifyOrigin(evt.origin)) {
      throw 'Denied message sent by ' + evt.origin;
    }
    const message = BroadcastMessage.fromData(evt.data);
    document.dispatchEvent(message.createCustomEvent('typo3'));
  }

  public constructor() {
    this.channel = new BroadcastChannel('typo3');
  }

  public listen(): void {
    if (this.isListening) {
      return;
    }
    // once `this` becomes necessary, use `.bind(this)`
    this.channel.onmessage = BroadcastService.onMessage;
  }

  public post(message: BroadcastMessage): void {
    this.channel.postMessage(message);
  }
}

export default new BroadcastService();
