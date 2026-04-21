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

import { StringlistTypeElement } from '@typo3/backend/settings/type/stringlist.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/settings/type/stringlist', () => {
  let root: HTMLElement;

  beforeEach((): void => {
    root = document.createElement('div');
    document.body.appendChild(root);
  });

  afterEach((): void => {
    root.remove();
    root = null;
  });

  it('renders one button when the value list is empty', async () => {
    const element = new StringlistTypeElement;
    element.formid = 'setting-test';
    element.value = [];
    root.appendChild(element);
    await new Promise((resolve) => setTimeout(resolve, 100));

    const inputs = element.querySelectorAll<HTMLInputElement>('input[type="text"]');
    expect(inputs).to.have.lengthOf(0);

    const buttons = element.querySelectorAll<HTMLButtonElement>('button');
    expect(buttons).to.have.lengthOf(1);
  });

  it('renders one empty input when the value list is a single empty string', async () => {
    const element = new StringlistTypeElement;
    element.formid = 'setting-test';
    element.value = [''];
    root.appendChild(element);
    await new Promise((resolve) => setTimeout(resolve, 100));

    const inputs = element.querySelectorAll<HTMLInputElement>('input[type="text"]');
    expect(inputs).to.have.lengthOf(1);
    expect(inputs[0].id).to.equal('setting-test');
  });

  it('hides the remove button when only one row is shown', async () => {
    const element = new StringlistTypeElement;
    element.value = [];
    root.appendChild(element);
    await new Promise((resolve) => setTimeout(resolve, 100));

    expect(element.querySelectorAll('button')).to.have.lengthOf(1);
  });

  it('adds a row when the add button is clicked on an empty list', async () => {
    const element = new StringlistTypeElement;
    element.value = [];
    root.appendChild(element);
    await new Promise((resolve) => setTimeout(resolve, 100));

    const addButton = element.querySelectorAll<HTMLButtonElement>('button:not([disabled])')[0];
    addButton.click();
    await new Promise((resolve) => setTimeout(resolve, 100));

    expect(element.value).to.deep.equal(['']);
    expect(element.querySelectorAll('input[type="text"]')).to.have.lengthOf(1);
  });

  it('renders per-row controls for each entry when value has multiple entries', async () => {
    const element = new StringlistTypeElement;
    element.value = ['/typo3', '/fileadmin'];
    root.appendChild(element);
    await new Promise((resolve) => setTimeout(resolve, 100));

    expect(element.querySelectorAll('input[type="text"]')).to.have.lengthOf(2);
    // Two rows, each with + and - buttons.
    expect(element.querySelectorAll('button')).to.have.lengthOf(4);
  });
});
