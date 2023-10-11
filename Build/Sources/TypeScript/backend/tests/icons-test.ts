import Icons from '@typo3/backend/icons.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/icons-test', () => {
  describe('tests for Icons object', () => {
    it('has all sizes', () => {
      expect(Icons.sizes.small).to.equal('small');
      expect(Icons.sizes.default).to.equal('default');
      expect(Icons.sizes.large).to.equal('large');
      expect(Icons.sizes.overlay).to.equal('overlay');
    });
    it('has all states', () => {
      expect(Icons.states.default).to.equal('default');
      expect(Icons.states.disabled).to.equal('disabled');
    });
    it('has all markupIdentifiers', () => {
      expect(Icons.markupIdentifiers.default).to.equal('default');
      expect(Icons.markupIdentifiers.inline).to.equal('inline');
    });
  });
});

