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

import Viewport from '@typo3/backend/viewport.js';
import Persistent from '@typo3/backend/storage/persistent.js';
import { expect, fixture, html } from '@open-wc/testing';
import { stub, useFakeTimers, type SinonStub } from 'sinon';
import type { ProgressBarElement } from '@typo3/backend/element/progress-bar-element.js';

describe('@typo3/backend/viewport:', () => {
  let contentArea: HTMLElement;
  let persistentGetStub: SinonStub;

  beforeEach(async (): Promise<void> => {
    persistentGetStub = stub(Persistent, 'get').returns(null);
    const contentNavigation = await fixture(html`
      <typo3-backend-content-navigation identifier="backend">
        <div slot="content"></div>
      </typo3-backend-content-navigation>
    `);
    contentArea = contentNavigation.querySelector('[slot="content"]');
  });

  afterEach((): void => {
    persistentGetStub.restore();
  });

  it('Loader.start() shows a running progress bar in the content area', (): void => {
    Viewport.Loader.start();

    const progressBar = contentArea.querySelector<ProgressBarElement>('typo3-backend-progress-bar');
    expect(progressBar).to.not.equal(null);
    expect(progressBar.isRunning()).to.equal(true);
  });

  it('Loader.finish() completes the progress bar', async (): Promise<void> => {
    Viewport.Loader.start();
    const progressBar = contentArea.querySelector<ProgressBarElement>('typo3-backend-progress-bar');

    const clock = useFakeTimers();
    try {
      const finished = Viewport.Loader.finish();
      await clock.tickAsync(100);
      await finished;
    } finally {
      clock.restore();
    }

    expect(progressBar.value).to.equal(progressBar.max);
  });
});
