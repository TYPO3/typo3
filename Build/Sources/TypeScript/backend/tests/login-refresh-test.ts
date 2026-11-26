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

import Modal, { type ModalElement } from '@typo3/backend/modal.js';
import { expect, waitUntil } from '@open-wc/testing';
import { render, type TemplateResult } from 'lit';
import { stub, type SinonStub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/backend/login-refresh', (): void => {
  let originalTypo3: typeof window.TYPO3;
  let fetchStub: SinonStub;
  let advancedStub: SinonStub;
  let modal: ModalElement | null;

  beforeEach((): void => {
    originalTypo3 = window.TYPO3;
    window.TYPO3 = {
      settings: { ajaxUrls: { logout: '/logout', login: '/login', login_timedout: '/login-timedout' } },
      configuration: { username: 'editor', showRefreshLoginPopup: false },
    } as unknown as typeof window.TYPO3;
    fetchStub = stub(window, 'fetch').callsFake(async (input: RequestInfo | URL): Promise<Response> => {
      const request = input instanceof Request ? input : new Request(input);
      const path = new URL(request.url).pathname;
      if (path === '/logout') {
        return new Response('{}', { headers: { 'Content-Type': 'application/json' } });
      }
      if (path === '/token') {
        return new Response(JSON.stringify({ headerName: 'X-Request-Token', requestToken: 'the-token' }), {
          headers: { 'Content-Type': 'application/json' },
        });
      }
      return new Promise<Response>((): undefined => undefined);
    });
    modal = null;
    advancedStub = stub(Modal, 'advanced').callsFake((configuration): ModalElement => {
      modal = document.createElement('typo3-backend-modal');
      const content = document.createElement('div');
      render(configuration.content as TemplateResult, content);
      modal.append(content);
      return modal;
    });
  });

  afterEach((): void => {
    fetchStub.restore();
    advancedStub.restore();
    window.TYPO3 = originalTypo3;
  });

  it('sends the login form data together with the login status', async (): Promise<void> => {
    const { default: LoginRefresh } = await import('@typo3/backend/login-refresh.js');
    LoginRefresh.initialize({ requestTokenUrl: '/token', intervalTime: 3600 });
    LoginRefresh.stopTask();

    LoginRefresh.showLoginForm();
    await waitUntil((): boolean => modal?.querySelector('form') instanceof HTMLFormElement);
    const form = modal!.querySelector('form')!;
    form.querySelector<HTMLInputElement>('input[name=p_field]')!.value = 'secret';
    form.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));

    const loginRequest = (): Request | undefined => fetchStub.getCalls()
      .map((call): Request => call.args[0])
      .find((request: Request): boolean => new URL(request.url).pathname === '/login');
    await waitUntil((): boolean => loginRequest() !== undefined);

    const request = loginRequest()!;
    const body = await request.formData();
    expect(request.headers.get('X-Request-Token')).to.equal('the-token');
    expect(body.get('login_status')).to.equal('login');
    expect(body.get('username')).to.equal('editor');
    expect(body.get('userident')).to.equal('secret');
    expect(body.get('p_field')).to.equal('');
  });
});
