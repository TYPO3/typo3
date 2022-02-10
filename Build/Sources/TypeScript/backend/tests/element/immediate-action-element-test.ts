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

import {ImmediateActionElement} from '@typo3/backend/element/immediate-action-element';
import moduleMenuApp from '@typo3/backend/module-menu';
import viewportObject from '@typo3/backend/viewport';

describe('TYPO3/CMS/Backend/Element/ImmediateActionElement:', () => {
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
    const backup = viewportObject.Topbar;
    const observer = {
      refresh: (): void => {
        return;
      },
    };
    spyOn(observer, 'refresh').and.callThrough();
    (viewportObject as any).Topbar = observer;
    const element = new ImmediateActionElement;
    element.setAttribute('action', 'TYPO3.Backend.Topbar.refresh');
    expect(observer.refresh).not.toHaveBeenCalled();
    root.appendChild(element);
    await import('@typo3/backend/viewport');
    await new Promise((resolve) => setTimeout(resolve, 100))
    expect(observer.refresh).toHaveBeenCalled();
    (viewportObject as any).Topbar = backup;
  });

  it('dispatches action when created via createElement', async () => {
    const backup = viewportObject.Topbar;
    const observer = {
      refresh: (): void => {
        return;
      },
    };
    spyOn(observer, 'refresh').and.callThrough();
    (viewportObject as any).Topbar = observer;
    const element = <ImmediateActionElement>document.createElement('typo3-immediate-action');
    element.setAttribute('action', 'TYPO3.Backend.Topbar.refresh');
    expect(observer.refresh).not.toHaveBeenCalled();
    root.appendChild(element);
    await import('@typo3/backend/viewport');
    await new Promise((resolve) => setTimeout(resolve, 100))
    expect(observer.refresh).toHaveBeenCalled();
    (viewportObject as any).Topbar = backup;
  });

  it('dispatches action when created from string', async () => {
    const backup = moduleMenuApp.App;
    const observer = {
      refreshMenu: (): void => {
        return;
      },
    };
    spyOn(observer, 'refreshMenu').and.callThrough();
    (moduleMenuApp as any).App = observer;
    const element = document.createRange().createContextualFragment('<typo3-immediate-action action="TYPO3.ModuleMenu.App.refreshMenu"></typo3-immediate-action>').querySelector('typo3-immediate-action');
    expect(observer.refreshMenu).not.toHaveBeenCalled();
    root.appendChild(element);
    await import('@typo3/backend/module-menu');
    await new Promise((resolve) => setTimeout(resolve, 100))
    expect(observer.refreshMenu).toHaveBeenCalled();
    (viewportObject as any).App = backup;
  });

  it('dispatches action when created via innerHTML', async () => {
    const backup = moduleMenuApp.App;
    const observer = {
      refreshMenu: (): void => {
        return;
      },
    };
    spyOn(observer, 'refreshMenu').and.callThrough();
    (moduleMenuApp as any).App = observer;
    root.innerHTML = '<typo3-immediate-action action="TYPO3.ModuleMenu.App.refreshMenu"></typo3-immediate-action>';
    await import('@typo3/backend/module-menu');
    await new Promise((resolve) => setTimeout(resolve, 100))
    expect(observer.refreshMenu).toHaveBeenCalled();
    (moduleMenuApp as any).App = backup;
  });
});
