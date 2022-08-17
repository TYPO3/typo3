import {Engine} from '@typo3/ckeditor5-bundle';

export class DoubleClickObserver extends Engine.DomEventObserver {
  protected readonly domEventType = 'dblclick';

  onDomEvent(domEvent: any) {
    this.fire(domEvent.type, domEvent);
  }
}
