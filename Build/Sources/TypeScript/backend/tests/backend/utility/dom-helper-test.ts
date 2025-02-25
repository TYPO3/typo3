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

import DomHelper from '@typo3/backend/utility/dom-helper.js';
import { expect } from '@open-wc/testing';

describe('@typo3/backend/utility/dom-helper', () => {
  it('parents() returns all parents matching the selector', () => {
    const markup =
      `<div class="dummy-matching-element">
        <ul>
          <li>
            <p class="dummy-matching-element">
              <span class="dummy-matching-element">
                <span id="enter-here"></span>
              </span>
            </p>
          </li>
        </ul>
      </div>`;
    const wrapperElement = document.createElement('div');
    wrapperElement.append(document.createRange().createContextualFragment(markup));

    const parents = DomHelper.parents(wrapperElement.querySelector('#enter-here'), '.dummy-matching-element');
    expect(parents).to.length(3);
    expect(parents.map((el: HTMLElement) => el.tagName)).to.have.ordered.members(['SPAN', 'P', 'DIV']);
  })

  it('nextAll() returns a proper list of all next elements', () => {
    const wrapperElement = document.createElement('div');
    const pElement = document.createElement('p');
    const strongElement = document.createElement('strong');
    const ulElement = document.createElement('ul');
    ulElement.append(document.createElement('li'));
    wrapperElement.append(pElement, strongElement, ulElement);

    const nextAll = DomHelper.nextAll(pElement);
    expect(nextAll).to.length(2);
    expect(nextAll).to.have.ordered.members([strongElement, ulElement]);
  })
});
