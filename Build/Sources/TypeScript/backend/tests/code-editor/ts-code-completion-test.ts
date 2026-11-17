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

import { expect } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import type { TsCodeCompletion } from '@typo3/backend/code-editor/autocomplete/ts-code-completion.js';
import type { } from 'mocha';

describe('TypoScript code completion', () => {
  let getStub: SinonStub;
  let completionClass: typeof TsCodeCompletion;

  before(async () => {
    (window as unknown as { TYPO3: unknown }).TYPO3 = {
      settings: { ajaxUrls: { codeeditor_tsref: '/tsref', codeeditor_codecompletion_loadtemplates: '/load-templates' } },
    };
    completionClass = (await import('@typo3/backend/code-editor/autocomplete/ts-code-completion.js')).TsCodeCompletion;
  });

  beforeEach(() => {
    getStub = stub(AjaxRequest.prototype, 'get');
  });

  afterEach(() => {
    getStub.restore();
  });

  it('goes on without the templates of the page when the server refuses them, and leaves no rejection unhandled', async () => {
    getStub.onFirstCall().resolves({ resolve: async () => ({}) } as never);
    getStub.onSecondCall().rejects(new Error('No permission'));
    const unhandled: unknown[] = [];
    const listener = (event: PromiseRejectionEvent): void => {
      unhandled.push(event.reason);
      event.preventDefault();
    };
    window.addEventListener('unhandledrejection', listener);

    const completion = new completionClass(1);
    await new Promise((resolve) => setTimeout(resolve, 50));
    window.removeEventListener('unhandledrejection', listener);

    expect(getStub.callCount).to.equal(2);
    expect(unhandled).to.deep.equal([]);
    expect(completion.extTsObjTree).to.deep.equal({});
  });
});
