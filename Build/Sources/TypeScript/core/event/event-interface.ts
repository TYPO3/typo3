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

export type Listener<E extends Event = Event, T extends Element = Element> = EventListenerWithTarget<E, T>;

export interface EventListenerWithTarget<E extends Event = Event, T extends Element = Element> {
  (evt: E, target: T): void;
}

export interface EventInterface {
  bindTo(element: EventTarget): void;
  delegateTo(element: EventTarget, selector: string): void;
  release(): void;
}
