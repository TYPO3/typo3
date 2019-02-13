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

import * as $ from 'jquery';
import 'TYPO3/CMS/Backend/jquery.clearable';

/**
 * Module: TYPO3/CMS/Tstemplate/TypoScriptObjectBrowser
 * JavaScript for TypoScript Object Browser
 * @exports TYPO3/CMS/Tstemplate/TypoScriptObjectBrowser
 */
class TypoScriptObjectBrowser {
  private $searchFields: JQuery;
  private readonly searchResultShown: boolean;

  constructor() {
    this.$searchFields = $('input[name="search_field"]');
    this.searchResultShown = ('' !== this.$searchFields.first().val());

    this.$searchFields.clearable({
      onClear: (evt: JQueryEventObject): void => {
        if (this.searchResultShown) {
          $(evt.currentTarget).closest('form').submit();
        }
      },
    });

    if (self.location.hash) {
      window.scrollTo(window.pageXOffset, window.pageYOffset - 40);
    }
  }
}

export = new TypoScriptObjectBrowser();
