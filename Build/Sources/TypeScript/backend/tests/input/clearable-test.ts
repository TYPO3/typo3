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

import '@typo3/backend/input/clearable.js';
import { expect, waitUntil } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/input/clearable', () => {
  let root: HTMLElement;

  beforeEach((): void => {
    root = document.createElement('div');
    document.body.appendChild(root);
  });

  afterEach((): void => {
    root.remove();
    root = null!;
  });

  async function createClearable(type: string): Promise<{ input: HTMLInputElement, clearButton: HTMLButtonElement }> {
    const input = document.createElement('input');
    input.type = type;
    root.appendChild(input);
    input.clearable();
    await waitUntil(() => input.isClearable);
    const clearButton = input.parentElement!.querySelector('button.close') as HTMLButtonElement;
    return { input, clearButton };
  }

  function typeIntoFocusedField(text: string): void {
    // A script cannot assign input the browser fails to parse to `value`, only user input reaches that state
    document.execCommand('insertText', false, text);
    document.activeElement!.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
  }

  it('hides the clear button of an empty field', async () => {
    const { clearButton } = await createClearable('text');
    expect(clearButton.style.visibility).to.equal('hidden');
  });

  it('shows the clear button once the field has a value', async () => {
    const { input, clearButton } = await createClearable('text');
    input.focus();
    typeIntoFocusedField('abc');
    expect(clearButton.style.visibility).to.equal('visible');
  });

  it('shows the clear button of a number field holding input the browser cannot parse', async () => {
    const { input, clearButton } = await createClearable('number');
    input.focus();
    typeIntoFocusedField('1-2');
    expect(input.value).to.equal('');
    expect(input.validity.badInput).to.be.true;
    expect(clearButton.style.visibility).to.equal('visible');
  });

  it('clears input the browser cannot parse and hides the clear button again', async () => {
    const { input, clearButton } = await createClearable('number');
    input.focus();
    typeIntoFocusedField('1-2');
    clearButton.click();
    expect(input.validity.badInput).to.be.false;
    expect(clearButton.style.visibility).to.equal('hidden');
  });
});
