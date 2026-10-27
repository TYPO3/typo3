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

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response.js';
import Icons from '@typo3/backend/icons.js';
import Notification from '@typo3/backend/notification.js';
import EventHandler from '@typo3/redirects/event-handler.js';
import labels from '~labels/redirects.slug_service';
import { expect } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';

describe('@typo3/redirects/event-handler:', () => {
  let infoStub: SinonStub;
  let postStub: SinonStub;
  let getIconStub: SinonStub;
  let labelStub: SinonStub;

  const slugChange = (page: string, autoUpdateSlugs: boolean, autoCreateRedirects: boolean, updatedSubPages: number = 1, createdRedirects: number = 1) => ({
    correlations: {
      correlationIdPageUpdate: page + ':page',
      correlationIdSlugUpdate: page + ':slug',
      correlationIdRedirectCreation: page + ':redirect',
    },
    autoUpdateSlugs,
    autoCreateRedirects,
    page: { uid: 1, title: page, oldSlug: '/' + page, newSlug: '/' + page + '-new' },
    updatedSubPages,
    createdRedirects,
  });

  beforeEach((): void => {
    window.TYPO3 = window.TYPO3 || {} as typeof window.TYPO3;
    window.TYPO3.settings = { ajaxUrls: { redirects_revert_correlation: '/revert' } } as unknown as typeof window.TYPO3.settings;
    infoStub = stub(Notification, 'info');
    postStub = stub(AjaxRequest.prototype, 'post').resolves({ resolve: async () => ({}) } as unknown as AjaxResponse);
    getIconStub = stub(Icons, 'getIcon').resolves('');
    labelStub = stub(labels, 'get').callsFake((key: string, args: Record<string, unknown> = {}): string => [key, ...Object.values(args)].join('|'));
  });

  afterEach((): void => {
    infoStub.restore();
    postStub.restore();
    getIconStub.restore();
    labelStub.restore();
  });

  it('shows one notification reverting the slug changes of all pages, latest first', async (): Promise<void> => {
    EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange('a', true, true));
    EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange('b', true, false));
    await Promise.resolve();

    expect(infoStub.callCount).to.equal(1);
    const actions = infoStub.firstCall.args[3];
    expect(actions).to.have.length(2);

    await actions[0].action.execute(document.createElement('button'));
    expect(postStub.lastCall.args[0]).to.deep.equal({
      correlation_ids: ['b:page', 'b:slug', 'b:redirect', 'a:page', 'a:slug', 'a:redirect'],
    });
    await actions[1].action.execute(document.createElement('button'));
    expect(postStub.lastCall.args[0]).to.deep.equal({
      correlation_ids: ['a:redirect'],
    });
  });

  it('names the changed page and what was changed along with it', async (): Promise<void> => {
    EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange('a', true, true, 3, 4));
    await Promise.resolve();

    expect(infoStub.firstCall.args[0]).to.equal('notification.slug_and_redirects.title');
    expect(infoStub.firstCall.args[1]).to.equal(
      'notification.message.changed_pages|notification.page|a|/a|/a-new|1'
      + ' notification.message.updated_sub_pages|3'
      + ' notification.message.created_redirects|4'
    );
  });

  it('names all changed pages and sums up what was changed along with them', async (): Promise<void> => {
    EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange('a', true, false, 3, 0));
    EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange('b', true, false, 2, 0));
    await Promise.resolve();

    expect(infoStub.firstCall.args[0]).to.equal('notification.slug_only.title');
    expect(infoStub.firstCall.args[1]).to.equal(
      'notification.message.changed_pages|notification.page|a|/a|/a-new, notification.page|b|/b|/b-new|2'
      + ' notification.message.updated_sub_pages|5'
    );
  });

  it('names the first changed pages only when many pages were changed', async (): Promise<void> => {
    ['a', 'b', 'c', 'd', 'e'].forEach((page: string): void => {
      EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange(page, false, true, 0, 1));
    });
    await Promise.resolve();

    expect(infoStub.firstCall.args[1]).to.equal(
      'notification.message.changed_pages_and_more|notification.page|a|/a|/a-new, notification.page|b|/b|/b-new, notification.page|c|/c|/c-new|2'
      + ' notification.message.created_redirects|5'
    );
  });

  it('shows separate notifications for slug changes of separate batches', async (): Promise<void> => {
    EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange('a', true, false));
    await Promise.resolve();
    EventHandler.dispatchCustomEvent('typo3:redirects:slugChanged', slugChange('b', true, false));
    await Promise.resolve();

    expect(infoStub.callCount).to.equal(2);
  });
});
