import { AspectRatioUtility } from '@typo3/backend/utility/aspect-ratio-utility.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/utility/aspect-ratio-utility', () => {
  const exactCases: Array<[number, number, string]> = [
    [1600, 900, '16:9'],
    [800, 600, '4:3'],
    [500, 500, '1:1'],
    [900, 1600, '9:16'],
    [750, 1000, '3:4'],
  ];
  for (const [width, height, label] of exactCases) {
    it(`matches ${width}×${height} exactly as ${label}`, () => {
      expect(AspectRatioUtility.find(width, height)).to.deep.equal({ label, exact: true });
    });
  }

  it('flags a close match as not exact', () => {
    expect(AspectRatioUtility.find(635, 849)).to.deep.equal({ label: '3:4', exact: false });
  });

  it('returns null if no common ratio is within the tolerance', () => {
    expect(AspectRatioUtility.find(1000, 830)).to.equal(null);
  });

  it('respects a custom tolerance', () => {
    expect(AspectRatioUtility.find(635, 849, 0.001)).to.equal(null);
  });

  it('returns null for empty sizes', () => {
    expect(AspectRatioUtility.find(0, 100)).to.equal(null);
    expect(AspectRatioUtility.find(100, 0)).to.equal(null);
  });
});
