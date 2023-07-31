import { BroadcastMessage } from '@typo3/backend/broadcast-message';
import BroadcastService from '@typo3/backend/broadcast-service';
import Modal from '../modal';
import Hotkeys from '@typo3/backend/hotkeys';
import DocumentService from '@typo3/core/document-service';

class LiveSearchShortcut {
  public constructor() {
    DocumentService.ready().then((): void => {
      Hotkeys.register([Hotkeys.normalizedCtrlModifierKey, 'k'], (e: KeyboardEvent): void => {
        if (Modal.currentModal) {
          // A modal window is already active, keep default behavior of browser
          return;
        }

        e.preventDefault();

        document.dispatchEvent(new CustomEvent('typo3:live-search:trigger-open'));
        BroadcastService.post(new BroadcastMessage(
          'live-search',
          'trigger-open',
          {}
        ));
      }, { allowOnEditables: true /* @todo: bindElement cannot be used at the moment as the suitable element exists twice! */ });
    });
  }
}

export default new LiveSearchShortcut();
