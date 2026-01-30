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

export class HotkeyRequestedEvent extends Event {
  static readonly eventName = 'typo3:hotkey:requested';

  constructor(
    public readonly keyboardEvent: KeyboardEvent,
  ) {
    super(HotkeyRequestedEvent.eventName, {
      bubbles: true,
      composed: true,
      cancelable: false,
    });
  }
}

export class HotkeyDispatchedEvent extends Event {
  static readonly eventName = 'typo3:hotkey:dispatched';

  constructor(
    public readonly keyboardEvent: KeyboardEvent,
  ) {
    super(HotkeyDispatchedEvent.eventName, {
      bubbles: false,
      composed: true,
      cancelable: true,
    });
  }
}

declare global {
  interface DocumentEventMap {
    [HotkeyRequestedEvent.eventName]: HotkeyRequestedEvent,
    [HotkeyDispatchedEvent.eventName]: HotkeyDispatchedEvent,
  }
}
