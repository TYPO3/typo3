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

import { ImmediateActionElement } from '@typo3/backend/element/immediate-action-element.js';
import moduleMenuApp from '@typo3/backend/module-menu.js';
import viewportObject from '@typo3/backend/viewport.js';
import { expect } from '@open-wc/testing';
import { stub } from 'sinon';
import type { } from 'mocha';

describe('@typo3/backend/element/immediate-action-element', () => {
  let root: HTMLElement; // This will hold the actual element under test.

  beforeEach((): void => {
    root = document.createElement('div');
    document.body.appendChild(root);
  });

  afterEach((): void => {
    root.remove();
    root = null;
  });

  it('dispatches action when created via constructor', async () => {
    const refreshStub = stub(viewportObject.Topbar, 'refresh');

    const element = new ImmediateActionElement;
    element.setAttribute('action', 'TYPO3.Backend.Topbar.refresh');
    expect(refreshStub).not.to.have.been.called;
    root.appendChild(element);
    await import('@typo3/backend/viewport.js');
    await new Promise((resolve) => setTimeout(resolve, 100));
    expect(refreshStub).to.have.been.called;

    refreshStub.restore();
  });

  it('dispatches action when created via createElement', async () => {
    const refreshStub = stub(viewportObject.Topbar, 'refresh');

    const element = <ImmediateActionElement>document.createElement('typo3-immediate-action');
    element.setAttribute('action', 'TYPO3.Backend.Topbar.refresh');
    expect(refreshStub).not.to.have.been.called;
    root.appendChild(element);
    await import('@typo3/backend/viewport.js');
    await new Promise((resolve) => setTimeout(resolve, 100));
    expect(refreshStub).to.have.been.called;

    refreshStub.restore();
  });

  it('dispatches action when created from string', async () => {
    const refreshMenuStub = stub(moduleMenuApp.App, 'refreshMenu');
    const element = document.createRange().createContextualFragment('<typo3-immediate-action action="TYPO3.ModuleMenu.App.refreshMenu"></typo3-immediate-action>').querySelector('typo3-immediate-action');
    expect(refreshMenuStub).not.to.have.been.called;
    root.appendChild(element);
    await import('@typo3/backend/module-menu.js');
    await new Promise((resolve) => setTimeout(resolve, 100));
    expect(refreshMenuStub).to.have.been.called;
    refreshMenuStub.restore();
  });

  it('dispatches action when created via innerHTML', async () => {
    const refreshMenuStub = stub(moduleMenuApp.App, 'refreshMenu');
    root.innerHTML = '<typo3-immediate-action action="TYPO3.ModuleMenu.App.refreshMenu"></typo3-immediate-action>';
    await import('@typo3/backend/module-menu.js');
    await new Promise((resolve) => setTimeout(resolve, 100));
    expect(refreshMenuStub).to.have.been.called;
    refreshMenuStub.restore();
  });
});
