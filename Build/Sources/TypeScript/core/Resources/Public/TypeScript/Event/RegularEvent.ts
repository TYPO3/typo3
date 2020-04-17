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

import {EventInterface, Listener} from './EventInterface';

class RegularEvent implements EventInterface {
  protected eventName: string;
  protected callback: Listener;
  private boundElement: EventTarget;

  constructor(eventName: string, callback: Listener) {
    this.eventName = eventName;
    this.callback = callback;
  }

  public bindTo(element: EventTarget) {
    this.boundElement = element;
    element.addEventListener(this.eventName, this.callback);
  }

  public delegateTo(element: EventTarget, selector: string): void {
    this.boundElement = element;
    element.addEventListener(this.eventName, (e: Event): void => {
      for (let targetElement: Node = <Element>e.target; targetElement && targetElement !== this.boundElement; targetElement = targetElement.parentNode) {
        if ((<HTMLElement>targetElement).matches(selector)) {
          this.callback.call(targetElement, e, targetElement);
          break;
        }
      }
    }, false);
  }

  public release(): void {
    this.boundElement.removeEventListener(this.eventName, this.callback);
  }
}

export = RegularEvent;
