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

import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

class TestPseudoButton extends PseudoButtonLitElement {
  public activations: number = 0;

  protected override buttonActivated(): void {
    this.activations++;
  }
}

customElements.define('typo3-test-pseudo-button', TestPseudoButton);

describe('@typo3/backend/element/pseudo-button', () => {
  let root: HTMLElement;

  beforeEach((): void => {
    root = document.createElement('div');
    document.body.appendChild(root);
  });

  afterEach((): void => {
    root.remove();
    root = null;
  });

  async function createButton(): Promise<TestPseudoButton> {
    const button = document.createElement('typo3-test-pseudo-button') as TestPseudoButton;
    root.appendChild(button);
    await button.updateComplete;
    return button;
  }

  function activate(button: TestPseudoButton): void {
    button.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    button.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
    button.dispatchEvent(new KeyboardEvent('keyup', { key: ' ', bubbles: true, cancelable: true }));
  }

  it('is activated by a click, Enter and Space', async () => {
    const button = await createButton();
    activate(button);
    expect(button.activations).to.equal(3);
  });

  it('behaves as a disabled button while it is disabled', async () => {
    const button = await createButton();
    button.setAttribute('disabled', '');
    await button.updateComplete;
    activate(button);
    expect(button.activations).to.equal(0);
    expect([button.getAttribute('aria-disabled'), button.getAttribute('tabindex')]).to.deep.equal(['true', '-1']);

    button.disabled = false;
    await button.updateComplete;
    activate(button);
    expect(button.activations).to.equal(3);
    expect([button.getAttribute('aria-disabled'), button.getAttribute('tabindex')]).to.deep.equal([null, '0']);
  });

  it('keeps a tab index of the markup while it is not disabled', async () => {
    const button = document.createElement('typo3-test-pseudo-button') as TestPseudoButton;
    button.setAttribute('tabindex', '-1');
    root.appendChild(button);
    await button.updateComplete;
    expect(button.getAttribute('tabindex')).to.equal('-1');
    expect(button.hasAttribute('aria-disabled')).to.be.false;
  });

  it('gets the tab index of the markup back after it was disabled', async () => {
    const button = document.createElement('typo3-test-pseudo-button') as TestPseudoButton;
    button.setAttribute('tabindex', '-1');
    root.appendChild(button);
    await button.updateComplete;
    button.disabled = true;
    await button.updateComplete;
    button.disabled = false;
    await button.updateComplete;
    expect(button.getAttribute('tabindex')).to.equal('-1');
  });
});
