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
import InfoWindow = require('TYPO3/CMS/Backend/InfoWindow');

/**
 * Module: TYPO3/CMS/Filelist/Filelist
 * @exports TYPO3/CMS/Filelist/Filelist
 */
class Filelist {
  /**
   * @param identifier
   */
  private static openFileInfoPopup(identifier: string): void {
    InfoWindow.showItem('_FILE', identifier);
  }

  constructor() {
    $((): void => {
      $('a.btn.filelist-file-info').click((event: JQueryEventObject): void => {
        event.preventDefault();
        Filelist.openFileInfoPopup($(event.currentTarget).attr('data-identifier'));
      });

      $('a.filelist-file-references').click((event: JQueryEventObject): void => {
        event.preventDefault();
        Filelist.openFileInfoPopup($(event.currentTarget).attr('data-identifier'));
      });

      $('a.btn.filelist-file-copy').click((event: JQueryEventObject): void => {
        event.preventDefault();
        const $element = $(event.currentTarget);
        const url = $element.attr('href');
        let redirectUrl = (url)
          ? top.rawurlencode(url)
          : top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search);

        top.list_frame.location.href = url + '&redirect=' + redirectUrl;
      });
    });
  }
}

export = new Filelist();
