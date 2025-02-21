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
  it('nextAll() returns a proper list of all next elements', () => {
    const wrapperElement = document.createElement('div');
    const pElement = document.createElement('p');
    const strongElement = document.createElement('strong');
    const ulElement = document.createElement('ul');
    ulElement.append(document.createElement('li'));
    wrapperElement.append(pElement, strongElement, ulElement);

    const nextAll = DomHelper.nextAll(pElement);
    expect(nextAll).to.length(2);
    expect(nextAll).to.have.same.members([strongElement, ulElement]);
  })
});
