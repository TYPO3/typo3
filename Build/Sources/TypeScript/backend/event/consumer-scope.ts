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

import type InteractionRequest from './interaction-request';
import type { Consumable } from './consumable';

class ConsumerScope {
  private consumers: Consumable[] = [];

  public getConsumers(): Consumable[] {
    return this.consumers;
  }

  public hasConsumer(consumer: Consumable): boolean {
    return this.consumers.includes(consumer);
  }

  public attach(consumer: Consumable): void {
    if (!this.hasConsumer(consumer)) {
      this.consumers.push(consumer);
    }
  }

  public detach(consumer: Consumable): void {
    this.consumers = this.consumers.filter(
      (currentConsumer: Consumable) => currentConsumer !== consumer,
    );
  }

  public async invoke(request: InteractionRequest): Promise<void> {
    const promises: Promise<void>[] = [];
    this.consumers.forEach(
      (consumer: Consumable) => {
        const promise: Promise<void> = consumer.consume.call(consumer, request);
        if (promise) {
          promises.push(promise);
        }
      },
    );
    await Promise.all(promises);
  }
}

export default new ConsumerScope();
