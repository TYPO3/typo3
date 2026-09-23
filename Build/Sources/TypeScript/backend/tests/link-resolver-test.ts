import { needsResolving, resolveLink } from '@typo3/backend/link-resolver.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/link-resolver', () => {
  describe('needsResolving', () => {
    for (const href of ['t3://page?uid=3', 't3://file?uid=12', 't3://record?identifier=news&uid=1', '17', 'fileadmin/foo.pdf']) {
      it(`asks the backend to resolve "${href}"`, () => {
        expect(needsResolving(href)).to.equal(true);
      });
    }
    for (const href of ['https://typo3.org/', 'mailto:info@example.com', 'tel:+4912345', '/relative', '#anchor']) {
      it(`does not resolve "${href}"`, () => {
        expect(needsResolving(href)).to.equal(false);
      });
    }
  });

  describe('resolveLink', () => {
    it('describes external links without a backend request', async () => {
      const resolved = await resolveLink('https://typo3.org/');
      expect(resolved.title).to.equal('https://typo3.org/');
      expect(resolved.url).to.equal('https://typo3.org/');
    });
    it('strips the scheme of mail links for the title', async () => {
      const resolved = await resolveLink('mailto:info@example.com');
      expect(resolved.title).to.equal('info@example.com');
      expect(resolved.url).to.equal('mailto:info@example.com');
      expect(resolved.icon).to.equal('actions-envelope');
    });
  });
});
