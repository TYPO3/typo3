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

export type BroadcastEvent<Payload extends object> = CustomEvent<{ payload: Payload }>;

/**
 * @module @typo3/backend/broadcast-message
 */
export class BroadcastMessage<Payload = unknown> {
  readonly componentName: string;
  readonly eventName: string;
  readonly payload: Payload;

  constructor(componentName: string, eventName: string, payload: Payload) {
    if (!componentName || !eventName) {
      throw new Error('Properties componentName and eventName have to be defined');
    }
    this.componentName = componentName;
    this.eventName = eventName;
    this.payload = payload || ({} as Payload);
  }

  public static fromData(data: any): BroadcastMessage {
    const payload = Object.assign({}, data);
    delete payload.componentName;
    delete payload.eventName;
    return new BroadcastMessage(
      data.componentName,
      data.eventName,
      payload,
    );
  }

  public createCustomEvent(scope: string = 'typo3'): CustomEvent {
    return new CustomEvent(
      [scope, this.componentName, this.eventName].join(':'),
      { detail: this.payload },
    );
  }
}
