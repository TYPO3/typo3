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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';

interface LinkAttributes {
  [s: string]: any;
}

/**
 * Module: @typo3/backend/link-browser
 * @exports @typo3/backend/link-browser
 */
class LinkBrowser {
  private urlParameters: Object = {};
  private parameters: Object = {};
  private linkAttributeFields: LinkAttributes;

  constructor() {
    DocumentService.ready().then((): void => {
      this.urlParameters = JSON.parse(document.body.dataset.urlParameters || '{}');
      this.parameters = JSON.parse(document.body.dataset.parameters || '{}');
      this.linkAttributeFields = JSON.parse(document.body.dataset.linkAttributeFields || '{}');

      new RegularEvent('change', this.loadTarget)
        .delegateTo(document, '.t3js-targetPreselect');
      new RegularEvent('submit', (event: SubmitEvent): void => event.preventDefault())
        .delegateTo(document, 'form.t3js-dummyform');
    });
  }

  public getLinkAttributeValues(): Object {
    const linkAttributeValues: LinkAttributes = {};
    for (const fieldName of this.linkAttributeFields.values()) {
      const linkAttribute: HTMLInputElement = document.querySelector('[name="l' + fieldName + '"]');
      if (linkAttribute !== null && linkAttribute.value !== '') {
        linkAttributeValues[fieldName] = linkAttribute.value;
      }
    }

    return linkAttributeValues;
  }

  public loadTarget(this: HTMLSelectElement): void {
    const linkTarget: HTMLInputElement = document.querySelector('.t3js-linkTarget');
    if (linkTarget !== null) {
      linkTarget.value = this.value;
      this.selectedIndex = 0;
    }
  }

  public finalizeFunction(link: string): void {
    throw 'The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug.';
  }
}

export default new LinkBrowser();
