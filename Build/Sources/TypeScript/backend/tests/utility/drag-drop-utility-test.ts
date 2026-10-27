import DragDropUtility from '@typo3/backend/utility/drag-drop-utility.js';
import { expect } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import type { } from 'mocha';

type DragEventState = {
  dropEffect: DataTransfer['dropEffect'],
  effectAllowed?: DataTransfer['effectAllowed'],
  ctrlKey?: boolean,
  altKey?: boolean,
};

function createDragEvent(state: DragEventState): DragEvent {
  return {
    ctrlKey: state.ctrlKey ?? false,
    altKey: state.altKey ?? false,
    dataTransfer: {
      dropEffect: state.dropEffect,
      effectAllowed: state.effectAllowed ?? 'copyMove',
    },
  } as unknown as DragEvent;
}

describe('@typo3/backend/utility/drag-drop-utility', () => {
  let userAgentStub: SinonStub;

  afterEach(() => {
    userAgentStub?.restore();
  });

  function emulatePlatform(userAgent: string): void {
    userAgentStub = stub(navigator, 'userAgent').get(() => userAgent);
  }

  describe('on Windows', () => {
    beforeEach(() => {
      emulatePlatform('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36');
    });

    it('detects a copy intention when Ctrl is held although the browser preset dropEffect to "move"', () => {
      const event = createDragEvent({ dropEffect: 'move', ctrlKey: true });
      expect(DragDropUtility.isCopyModifierFromEvent(event)).to.equal(true);
    });

    it('detects a move intention without modifier', () => {
      const event = createDragEvent({ dropEffect: 'move' });
      expect(DragDropUtility.isCopyModifierFromEvent(event)).to.equal(false);
    });

    it('keeps the copy intention propagated by dropEffect', () => {
      const event = createDragEvent({ dropEffect: 'copy' });
      expect(DragDropUtility.isCopyModifierFromEvent(event)).to.equal(true);
    });

    it('reflects the copy intention in dropEffect', () => {
      const event = createDragEvent({ dropEffect: 'move', ctrlKey: true });
      DragDropUtility.updateEventAndTooltipToReflectCopyMoveIntention(event);
      expect(event.dataTransfer.dropEffect).to.equal('copy');
    });
  });

  describe('on macOS', () => {
    beforeEach(() => {
      emulatePlatform('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36');
    });

    it('detects a copy intention when Option is held although the browser preset dropEffect to "move"', () => {
      const event = createDragEvent({ dropEffect: 'move', altKey: true });
      expect(DragDropUtility.isCopyModifierFromEvent(event)).to.equal(true);
    });

    it('detects a copy intention when the platform restricted the operation to copy', () => {
      const event = createDragEvent({ dropEffect: 'none', effectAllowed: 'copy' });
      expect(DragDropUtility.isCopyModifierFromEvent(event)).to.equal(true);
    });

    it('ignores Ctrl', () => {
      const event = createDragEvent({ dropEffect: 'move', ctrlKey: true });
      expect(DragDropUtility.isCopyModifierFromEvent(event)).to.equal(false);
    });
  });
});
