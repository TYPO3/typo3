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
import CapslockWarning from '@typo3/backend/login/capslock-warning.js';

describe('@typo3/backend/login/capslock-warning', () => {
  let container: HTMLElement;
  let field: HTMLInputElement;

  const press = (type: string, key: string, capsLock: boolean, shiftKey: boolean = false): void => {
    field.dispatchEvent(new KeyboardEvent(type, { key, shiftKey, modifierCapsLock: capsLock, bubbles: true }));
  };
  const warning = (): Element | null => field.closest('.input-group').querySelector('.input-group-text-warning-capslock');

  beforeEach(() => {
    container = document.createElement('div');
    container.classList.add('input-group');
    container.innerHTML = '<input type="password" class="t3js-capslockwarning" data-capslockwarning-title="Capslock" data-capslockwarning-message="Capslock is enabled">';
    document.body.append(container);
    field = container.querySelector('input');
    CapslockWarning.bindTo(field);
  });

  afterEach(() => {
    container.remove();
  });

  it('shows the warning for a lower case key while capslock is active', () => {
    press('keydown', 'A', true);
    expect(warning()).to.not.be.null;
    expect(warning().getAttribute('title')).to.equal('Capslock');
    expect(warning().textContent).to.contain('Capslock is enabled');
  });

  it('shows the warning while capslock and shift are active', () => {
    // macOS produces upper case characters in this case
    press('keydown', 'A', true, true);
    expect(warning()).to.not.be.null;
  });

  it('shows the warning for non-letter keys while capslock is active', () => {
    press('keydown', '1', true);
    expect(warning()).to.not.be.null;
  });

  it('shows the warning when capslock is switched on', () => {
    press('keydown', 'CapsLock', true);
    expect(warning()).to.not.be.null;
  });

  it('removes the warning when capslock is switched off', () => {
    press('keydown', 'a', true);
    press('keyup', 'CapsLock', false);
    expect(warning()).to.be.null;
  });

  it('does not show the warning for an upper case key typed with shift', () => {
    press('keydown', 'A', false, true);
    expect(warning()).to.be.null;
  });

  it('adds the warning only once', () => {
    press('keydown', 'A', true);
    press('keydown', 'B', true);
    expect(field.closest('.input-group').querySelectorAll('.input-group-text-warning-capslock').length).to.equal(1);
  });
});
