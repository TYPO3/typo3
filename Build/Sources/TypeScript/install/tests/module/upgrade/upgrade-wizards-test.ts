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

import UpgradeWizards from '@typo3/install/module/upgrade/upgrade-wizards.js';
import { expect, waitUntil } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/install/module/upgrade/upgrade-wizards', (): void => {
  let fetchStub: SinonStub;
  let defineStub: SinonStub;

  const requestedActions = (): Array<string | null> => fetchStub.getCalls()
    .map((call): string | null => new URL((call.args[0] as Request).url).searchParams.get('install[action]'));

  beforeEach((): void => {
    // The test runner resolves the dynamic import of the renderables to another URL than the static import,
    // so they are evaluated a second time and would define their custom elements again
    const define = customElements.define.bind(customElements);
    defineStub = stub(customElements, 'define').callsFake((name: string, constructor: CustomElementConstructor, options?: ElementDefinitionOptions): void => {
      if (customElements.get(name) === undefined) {
        define(name, constructor, options);
      }
    });
    fetchStub = stub(window, 'fetch').callsFake(async (input: RequestInfo | URL): Promise<Response> => {
      const request = input instanceof Request ? input : new Request(input);
      if (new URL(request.url).searchParams.get('install[action]') === 'upgradeWizardsGetData') {
        return new Response(JSON.stringify({
          success: true,
          html: '<div class="t3js-module-content" data-upgrade-wizards-execute-token="the-token">'
            + '<div class="t3js-upgradeWizards-wizards-messages-output"></div>'
            + '<div class="t3js-upgradeWizards-wizards-output"></div>'
            + '<div class="t3js-upgradeWizards-done-output"></div>'
            + '</div>',
        }), { headers: { 'Content-Type': 'application/json' } });
      }
      return new Promise<Response>((): undefined => undefined);
    });
  });

  afterEach((): void => {
    fetchStub.restore();
    defineStub.restore();
  });

  it('sends the form data of a wizard together with action, token and identifier', async (): Promise<void> => {
    const modal = document.createElement('typo3-backend-modal');
    modal.innerHTML = '<div class="t3js-modal-body"></div><div class="t3js-modal-footer"></div>';
    UpgradeWizards.initialize(modal);
    await waitUntil((): boolean => requestedActions().includes('upgradeWizardsDoneUpgrades'));

    modal.querySelector('.t3js-upgradeWizards-wizards-output')!.innerHTML = '<form>'
      + '<input type="hidden" name="install[values][myWizard][install]" value="0">'
      + '<input type="checkbox" name="install[values][myWizard][install]" value="1" checked>'
      + '<input type="file" name="install[values][upload]">'
      + '<button class="t3js-upgradeWizards-input-perform" data-identifier="myWizard" data-title="My wizard">Perform</button>'
      + '</form>';
    modal.querySelector<HTMLButtonElement>('.t3js-upgradeWizards-input-perform')!.click();
    const executeRequest = (): Request | undefined => fetchStub.getCalls()
      .map((call): Request => call.args[0])
      .find((request: Request): boolean => request.method === 'POST');
    await waitUntil((): boolean => executeRequest() !== undefined);

    const body = await executeRequest()!.formData();
    expect(body.get('install[action]')).to.equal('upgradeWizardsExecute');
    expect(body.get('install[token]')).to.equal('the-token');
    expect(body.get('install[identifier]')).to.equal('myWizard');
    expect(body.getAll('install[values][myWizard][install]')).to.deep.equal(['0', '1']);
    expect(body.get('install[values][upload]')).to.be.instanceOf(File);
  });
});
