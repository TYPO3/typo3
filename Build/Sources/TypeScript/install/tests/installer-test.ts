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

import { expect, waitUntil } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/install/installer', (): void => {
  let fetchStub: SinonStub;
  let container: HTMLElement;

  before(async (): Promise<void> => {
    fetchStub = stub(window, 'fetch').callsFake((): Promise<Response> => new Promise<Response>((): undefined => undefined));
    await import('@typo3/install/installer.js');
  });

  after((): void => {
    fetchStub.restore();
  });

  beforeEach((): void => {
    fetchStub.resetHistory();
    container = document.createElement('div');
    document.body.append(container);
  });

  afterEach((): void => {
    container.remove();
  });

  const steps: Array<[string, string, string]> = [
    ['executeDatabaseConnect', 'databaseConnect', 'installerDatabaseConnectExecuteToken'],
    ['executeDatabaseSelect', 'databaseSelect', 'installerDatabaseSelectExecuteToken'],
    ['executeDatabaseData', 'databaseData', 'installerDatabaseDataExecuteToken'],
    ['executeDefaultConfiguration', 'defaultConfiguration', 'installerDefaultConfigurationExecuteToken'],
  ];

  for (const [action, step, tokenName] of steps) {
    it(`sends the form data together with action and token in ${action}`, async (): Promise<void> => {
      const moduleContent = document.createElement('div');
      moduleContent.className = 't3js-module-content';
      moduleContent.dataset[tokenName] = 'the-token';
      moduleContent.innerHTML = '<div class="t3js-installer-databaseConnect-output"></div>'
        + '<div class="t3js-installer-databaseSelect-output"></div>'
        + '<div class="t3js-installer-databaseData-output"></div>';
      container.className = 't3js-body';
      container.innerHTML = '<form>'
        + '<input type="hidden" name="install[values][flag]" value="0">'
        + '<input type="checkbox" name="install[values][flag]" value="1" checked>'
        + '<input type="file" name="install[values][upload]">'
        + `<button class="t3js-installer-${step}-execute">Continue</button>`
        + '</form>';
      container.append(moduleContent);

      container.querySelector<HTMLButtonElement>(`.t3js-installer-${step}-execute`)!.click();
      const postRequest = (): Request | undefined => fetchStub.getCalls()
        .map((call): Request => call.args[0])
        .find((request: Request): boolean => request.method === 'POST');
      await waitUntil((): boolean => postRequest() !== undefined);

      const body = await postRequest()!.formData();
      expect(body.get('install[action]')).to.equal(action);
      expect(body.get('install[token]')).to.equal('the-token');
      expect(body.getAll('install[values][flag]')).to.deep.equal(['0', '1']);
      expect(body.get('install[values][upload]')).to.be.instanceOf(File);
    });
  }

  it('sends the form data together with action and token in checkDatabaseRequirements', async (): Promise<void> => {
    const postedActions = async (): Promise<Array<{ action: FormDataEntryValue | null, body: FormData }>> => Promise.all(
      fetchStub.getCalls()
        .map((call): Request => call.args[0])
        .filter((request: Request): boolean => request.method === 'POST')
        .map(async (request: Request): Promise<{ action: FormDataEntryValue | null, body: FormData }> => {
          const body = await request.clone().formData();
          return { action: body.get('install[action]'), body };
        })
    );
    fetchStub.callsFake(async (input: RequestInfo | URL): Promise<Response> => {
      const request = input instanceof Request ? input : new Request(input);
      if (request.method === 'POST' && (await request.clone().formData()).get('install[action]') === 'executeDatabaseSelect') {
        return new Response(JSON.stringify({ success: true }), { headers: { 'Content-Type': 'application/json' } });
      }
      return new Promise<Response>((): undefined => undefined);
    });
    const moduleContent = document.createElement('div');
    moduleContent.className = 't3js-module-content';
    moduleContent.dataset.installerDatabaseSelectExecuteToken = 'select-token';
    moduleContent.dataset.installerDatabaseCheckRequirementsExecuteToken = 'the-token';
    moduleContent.innerHTML = '<div class="t3js-installer-databaseSelect-output"></div>';
    container.className = 't3js-body';
    container.innerHTML = '<form>'
      + '<input type="hidden" name="install[values][flag]" value="0">'
      + '<input type="checkbox" name="install[values][flag]" value="1" checked>'
      + '<input type="file" name="install[values][upload]">'
      + '<button class="t3js-installer-databaseSelect-execute">Continue</button>'
      + '</form>';
    container.append(moduleContent);

    try {
      container.querySelector<HTMLButtonElement>('.t3js-installer-databaseSelect-execute')!.click();
      let requirements: FormData | undefined;
      await waitUntil(async (): Promise<boolean> => {
        requirements = (await postedActions()).find((posted): boolean => posted.action === 'checkDatabaseRequirements')?.body;
        return requirements !== undefined;
      });

      expect(requirements!.get('install[token]')).to.equal('the-token');
      expect(requirements!.getAll('install[values][flag]')).to.deep.equal(['0', '1']);
      expect(requirements!.get('install[values][upload]')).to.be.instanceOf(File);
    } finally {
      fetchStub.callsFake((): Promise<Response> => new Promise<Response>((): undefined => undefined));
    }
  });
});
