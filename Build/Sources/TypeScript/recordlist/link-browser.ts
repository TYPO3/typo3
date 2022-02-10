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

import $ from 'jquery';

interface LinkAttributes {
  [s: string]: any;
}

/**
 * Module: @typo3/recordlist/link-browser
 * @exports @typo3/recordlist/link-browser
 */
class LinkBrowser {
  private urlParameters: Object = {};
  private parameters: Object = {};
  private linkAttributeFields: Array<any>;
  private additionalLinkAttributes: LinkAttributes = {};

  constructor() {
    $((): void => {
      const data = $('body').data();

      this.urlParameters = data.urlParameters;
      this.parameters = data.parameters;
      this.linkAttributeFields = data.linkAttributeFields;

      $('.t3js-targetPreselect').on('change', this.loadTarget);
      $('form.t3js-dummyform').on('submit', (evt: JQueryEventObject): void => {
        evt.preventDefault();
      });
    });
  }

  public getLinkAttributeValues(): Object {
    const attributeValues: LinkAttributes = {};
    $.each(this.linkAttributeFields, (index: number, fieldName: string) => {
      const val: string = $('[name="l' + fieldName + '"]').val();
      if (val) {
        attributeValues[fieldName] = val;
      }
    });
    $.extend(attributeValues, this.additionalLinkAttributes);
    return attributeValues;
  }

  public loadTarget = (evt: JQueryEventObject): void => {
    const $element = $(evt.currentTarget);
    $('.t3js-linkTarget').val($element.val());
    (<HTMLSelectElement>$element.get(0)).selectedIndex = 0;
  }

  /**
   * Encode objects to GET parameter arrays in PHP notation
   */
  public encodeGetParameters(obj: LinkAttributes, prefix: string, url: string): string {
    const str = [];
    for (const entry of Object.entries(obj)) {
      const [p, v] = entry;
      const k: string = prefix ? prefix + '[' + p + ']' : p;
      if (!url.includes(k + '=')) {
        str.push(
          typeof v === 'object'
            ? this.encodeGetParameters(v, k, url)
            : encodeURIComponent(k) + '=' + encodeURIComponent(v),
        );
      }
    }
    return '&' + str.join('&');
  }

  /**
   * Set an additional attribute for the link
   */
  public setAdditionalLinkAttribute(name: string, value: any): void {
    this.additionalLinkAttributes[name] = value;
  }

  /**
   * Stores the final link
   *
   * This method MUST be overridden in the actual implementation of the link browser.
   * The function is responsible for encoding the link (and possible link attributes) and
   * returning it to the caller (e.g. FormEngine, RTE, etc)
   *
   * @param {String} link The select element or anything else which identifies the link (e.g. "page:<pageUid>" or "file:<uid>")
   */
  public finalizeFunction(link: string): void {
    throw 'The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug.';
  }
}

export default new LinkBrowser();
