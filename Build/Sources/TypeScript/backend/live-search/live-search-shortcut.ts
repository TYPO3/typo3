import Hotkeys from '@typo3/backend/hotkeys';
import DocumentService from '@typo3/core/document-service';
import { topDocument } from '@typo3/core/utility/top-window';

class LiveSearchShortcut {
  public constructor() {
    DocumentService.ready().then((): void => {
      Hotkeys.register([Hotkeys.normalizedCtrlModifierKey, 'k'], (e: KeyboardEvent): void => {
        e.preventDefault();

        topDocument.dispatchEvent(new CustomEvent('typo3:live-search:trigger-open'));
      }, { allowOnEditables: true /* @todo: bindElement cannot be used at the moment as the suitable element exists twice! */ });
    });
  }
}

export default new LiveSearchShortcut();
