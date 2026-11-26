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

import ExtensionConfiguration from '@typo3/install/module/settings/extension-configuration.js';
import { expect, waitUntil } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/install/module/settings/extension-configuration', (): void => {
  let fetchStub: SinonStub;

  beforeEach((): void => {
    fetchStub = stub(window, 'fetch').callsFake(async (input: RequestInfo | URL): Promise<Response> => {
      const request = input instanceof Request ? input : new Request(input);
      if (new URL(request.url).searchParams.get('install[action]') === 'extensionConfigurationGetContent') {
        return new Response(JSON.stringify({
          success: true,
          html: '<div class="t3js-module-content" data-extension-configuration-write-token="the-token">'
            + '<form class="t3js-extensionConfiguration-form" data-extension-key="my_extension">'
            + '<input type="text" name="basic.title" value="Title">'
            + '<input type="hidden" name="basic.enabled" value="0">'
            + '<input type="checkbox" name="basic.enabled" value="1" checked>'
            + '<input type="file" name="basic.upload">'
            + '</form></div>',
        }), { headers: { 'Content-Type': 'application/json' } });
      }
      return new Promise<Response>((): undefined => undefined);
    });
  });

  afterEach((): void => {
    fetchStub.restore();
  });

  it('sends the form data below the extension configuration key together with action, token and extension key', async (): Promise<void> => {
    const modal = document.createElement('typo3-backend-modal');
    modal.innerHTML = '<div class="t3js-modal-body"></div><div class="t3js-modal-footer"></div>';
    ExtensionConfiguration.initialize(modal);
    await waitUntil((): boolean => modal.querySelector('form') !== null);

    modal.querySelector('form')!.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));
    await waitUntil((): boolean => fetchStub.callCount >= 2);

    const body = await (fetchStub.secondCall.args[0] as Request).formData();
    expect(body.get('install[action]')).to.equal('extensionConfigurationWrite');
    expect(body.get('install[token]')).to.equal('the-token');
    expect(body.get('install[extensionKey]')).to.equal('my_extension');
    expect(body.get('install[extensionConfiguration][basic.title]')).to.equal('Title');
    expect(body.getAll('install[extensionConfiguration][basic.enabled]')).to.deep.equal(['0', '1']);
    expect(body.get('install[extensionConfiguration][basic.upload]')).to.be.instanceOf(File);
  });
});
