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

import { HotkeyDispatchedEvent, HotkeyRequestedEvent } from '@typo3/backend/hotkeys/events';

/**
 * Global handler that receives the `typo3:hotkey-requested` event dispatched by any `hotkeys` instance.
 * When receiving such event, all available documents are fetched to dispatch `typo3:hotkey-dispatched` to each document
 * until a responsible handler was found.
 */
class Negotiator {
  constructor() {
    this.registerEventHandler();
  }

  private registerEventHandler() {
    document.addEventListener(HotkeyRequestedEvent.eventName, (e): void => {
      const hotkeyDispatchedEvent = new HotkeyDispatchedEvent(e.keyboardEvent);

      for (const windowDocument of this.collectDocuments()) {
        if (windowDocument.dispatchEvent(hotkeyDispatchedEvent) === false) {
          // Event has been canceled (= hotkey was found)
          break;
        }
      }
    });
  }

  private collectDocuments(): Document[] {
    const documents: Document[] = [document];
    for (let i = 0; i < window.frames.length; i++) {
      try {
        documents.push(window.frames[i].document);
      } catch {
        // Cross-origin frame, skip
      }
    }

    return documents;
  }
}

export default new Negotiator();
