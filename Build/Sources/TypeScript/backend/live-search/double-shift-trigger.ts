import {BroadcastMessage} from '@typo3/backend/broadcast-message';
import BroadcastService from '@typo3/backend/broadcast-service';
import RegularEvent from '@typo3/core/event/regular-event';

class DoubleShiftTrigger {
  private shiftPressCounter: number = 0;

  public constructor() {
    new RegularEvent('keydown', (e: KeyboardEvent): void => {
      if (e.repeat) {
        return;
      }
      if (e.key !== 'Shift') {
        this.shiftPressCounter = 0;
      } else {
        this.shiftPressCounter++;

        if (this.shiftPressCounter === 1) {
          window.setTimeout((): void => {
            this.shiftPressCounter = 0;
          }, 500);
        }

        if (this.shiftPressCounter >= 2) {
          this.shiftPressCounter = 0;
          document.dispatchEvent(new CustomEvent('live-search:trigger-open'));

          BroadcastService.post(new BroadcastMessage(
            'live-search',
            'trigger-open',
            {}
          ))
        }
      }
    }).bindTo(document);
  }
}

export default new DoubleShiftTrigger();
