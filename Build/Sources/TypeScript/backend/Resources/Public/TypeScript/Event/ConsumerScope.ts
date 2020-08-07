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
import Consumable = require('./Consumable');
import InteractionRequest = require('./InteractionRequest');

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

  public invoke(request: InteractionRequest): any {
    const deferreds: any[] = [];
    this.consumers.forEach(
      (consumer: Consumable) => {
        const deferred: any = consumer.consume.call(consumer, request);
        if (deferred) {
          deferreds.push(deferred);
        }
      },
    );
    return ($ as any).when.apply($, deferreds);
  }
}

export = new ConsumerScope();
