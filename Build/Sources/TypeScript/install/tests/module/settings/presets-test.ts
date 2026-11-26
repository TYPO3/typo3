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

import Presets from '@typo3/install/module/settings/presets.js';
import Modal from '@typo3/backend/modal.js';
import { expect, waitUntil } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/install/module/settings/presets', (): void => {
  let fetchStub: SinonStub;
  let setButtonsStub: SinonStub;

  beforeEach((): void => {
    fetchStub = stub(window, 'fetch').callsFake(async (input: RequestInfo | URL): Promise<Response> => {
      const request = input instanceof Request ? input : new Request(input);
      if (new URL(request.url).searchParams.get('install[action]') === 'presetsGetContent') {
        return new Response(JSON.stringify({
          success: true,
          html: '<div class="t3js-module-content" data-presets-activate-token="the-token"><form>'
            + '<input type="radio" name="install[values][Image][enable]" value="custom" checked>'
            + '<input type="hidden" name="install[values][Image][Custom][path]" value="">'
            + '<input type="text" name="install[values][Image][Custom][path]" value="/usr/bin/">'
            + '<input type="file" name="install[values][upload]">'
            + '</form></div>',
          buttons: [],
        }), { headers: { 'Content-Type': 'application/json' } });
      }
      return new Promise<Response>((): undefined => undefined);
    });
    setButtonsStub = stub(Modal, 'setButtons');
  });

  afterEach((): void => {
    fetchStub.restore();
    setButtonsStub.restore();
  });

  it('sends the form data together with action and token', async (): Promise<void> => {
    const modal = document.createElement('typo3-backend-modal');
    modal.innerHTML = '<div class="t3js-modal-body"></div><div class="t3js-modal-footer"><button class="t3js-presets-activate">Activate</button></div>';
    Presets.initialize(modal);
    await waitUntil((): boolean => modal.querySelector('form') !== null);

    modal.querySelector<HTMLButtonElement>('.t3js-presets-activate')!.click();
    await waitUntil((): boolean => fetchStub.callCount >= 2);

    const body = await (fetchStub.secondCall.args[0] as Request).formData();
    expect(body.get('install[action]')).to.equal('presetsActivate');
    expect(body.get('install[token]')).to.equal('the-token');
    expect(body.get('install[values][Image][enable]')).to.equal('custom');
    expect(body.getAll('install[values][Image][Custom][path]')).to.deep.equal(['', '/usr/bin/']);
    expect(body.get('install[values][upload]')).to.be.instanceOf(File);
  });
});
