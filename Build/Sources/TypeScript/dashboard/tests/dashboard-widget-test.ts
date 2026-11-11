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

import { expect, fixture, html } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import type { DashboardWidget } from '@typo3/dashboard/dashboard.js';
import type { } from 'mocha';

describe('dashboard widget move button', () => {
  let getStub: SinonStub;

  before(async () => {
    (window as unknown as { TYPO3: unknown }).TYPO3 = { settings: { ajaxUrls: { dashboard_widget_get: '/widget' } } };
    await import('@typo3/dashboard/dashboard.js');
  });

  beforeEach(() => {
    getStub = stub(AjaxRequest.prototype, 'get').resolves({
      resolve: async () => ({ status: 'ok', widget: { label: 'Widget', content: '', configurable: false, refreshable: false, eventdata: {} } }),
    } as never);
  });

  afterEach(() => {
    getStub.restore();
  });

  const renderWidget = async (): Promise<DashboardWidget> => {
    const widget = await fixture<DashboardWidget>(html`<typo3-dashboard-widget identifier="test"></typo3-dashboard-widget>`);
    await widget.updateComplete;
    await new Promise((resolve) => setTimeout(resolve, 50));
    await widget.updateComplete;
    return widget;
  };

  it('exposes the pinned state to assistive technology', async () => {
    const widget = await renderWidget();
    const button = widget.querySelector<HTMLButtonElement>('.widget-action-move');

    expect(button.getAttribute('aria-pressed')).to.equal('false');

    widget.moving = true;
    await widget.updateComplete;

    expect(button.getAttribute('aria-pressed')).to.equal('true');
  });

  it('adapts the title of the move button while the widget is pinned', async () => {
    const widget = await renderWidget();
    const button = widget.querySelector<HTMLButtonElement>('.widget-action-move');
    const titleBeforePinning = button.title;

    widget.moving = true;
    await widget.updateComplete;

    expect(button.title).to.not.equal(titleBeforePinning);
  });
});
