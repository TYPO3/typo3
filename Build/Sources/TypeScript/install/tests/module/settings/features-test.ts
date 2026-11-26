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

import Features from '@typo3/install/module/settings/features.js';
import Modal from '@typo3/backend/modal.js';
import { expect, waitUntil } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/install/module/settings/features', (): void => {
  let fetchStub: SinonStub;
  let setButtonsStub: SinonStub;

  const jsonResponse = (data: object): Response => new Response(JSON.stringify(data), {
    headers: { 'Content-Type': 'application/json' },
  });

  beforeEach((): void => {
    fetchStub = stub(window, 'fetch').callsFake(async (input: RequestInfo | URL): Promise<Response> => {
      const request = input instanceof Request ? input : new Request(input);
      if (new URL(request.url).searchParams.get('install[action]') === 'featuresGetContent') {
        return jsonResponse({
          success: true,
          html: '<div class="t3js-module-content" data-features-save-token="the-token"><form>'
            + '<input type="hidden" name="install[values][feature][enable]" value="0">'
            + '<input type="checkbox" name="install[values][feature][enable]" value="1" checked>'
            + '<input type="file" name="install[values][upload]">'
            + '</form></div>',
          buttons: [],
        });
      }
      return jsonResponse({ success: true, status: [] });
    });
    setButtonsStub = stub(Modal, 'setButtons');
  });

  afterEach((): void => {
    fetchStub.restore();
    setButtonsStub.restore();
  });

  it('sends the form data together with action and token', async (): Promise<void> => {
    const modal = document.createElement('typo3-backend-modal');
    modal.innerHTML = '<div class="t3js-modal-body"></div><div class="t3js-modal-footer"><button class="t3js-features-save">Save</button></div>';
    Features.initialize(modal);
    await waitUntil((): boolean => modal.querySelector('form') !== null);

    modal.querySelector<HTMLButtonElement>('.t3js-features-save')!.click();
    await waitUntil((): boolean => fetchStub.callCount >= 2);

    const request: Request = fetchStub.secondCall.args[0];
    const body = await request.formData();
    expect(body.get('install[action]')).to.equal('featuresSave');
    expect(body.get('install[token]')).to.equal('the-token');
    expect(body.getAll('install[values][feature][enable]')).to.deep.equal(['0', '1']);
    expect(body.get('install[values][upload]')).to.be.instanceOf(File);
  });
});
