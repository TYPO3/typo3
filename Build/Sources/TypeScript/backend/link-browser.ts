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

import RegularEvent from '@typo3/core/event/regular-event';

export interface LinkAttributes {
  [s: string]: any;
}

export interface LinkBrowserFieldChangeFunc {
  name: string;
  data: Record<string, string>;
}

export interface LinkBrowserParameters {
  table: string;
  uid: string;
  pid: string;
  field?: string;
  fieldName?: string;
  formName?: string;
  itemName?: string;
  hmac?: string;
  recordType?: string;
  richtextConfigurationName?: string;
  fieldChangeFunc?:LinkBrowserFieldChangeFunc[];
  fieldChangeFuncHash?: string;
  curUrl?: Record<string, string>,
  currentValue?: string;
  currentSelectedValues?: string;
}

/**
 * Module: @typo3/backend/link-browser
 * @exports @typo3/backend/link-browser
 */
class LinkBrowser {
  public parameters: LinkBrowserParameters;
  private linkAttributeFields: LinkAttributes;

  constructor() {
    this.parameters = JSON.parse(document.body.dataset.linkbrowserParameters || '{}');
    this.linkAttributeFields = JSON.parse(document.body.dataset.linkbrowserAttributeFields || '{}');

    new RegularEvent('click', (event: PointerEvent): void => {
      event.preventDefault();
      this.finalizeFunction(document.body.dataset.linkbrowserCurrentLink);
    }).delegateTo(document, 'button.t3js-linkCurrent');

    new RegularEvent('change', this.loadTarget)
      .delegateTo(document, '.t3js-targetPreselect');
  }

  public getLinkAttributeValues(): LinkAttributes {
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
