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
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';

class UploadForm {
  public expandedUploadFormClass: string = 'transformed';

  public initializeEvents(): void {
    // Show upload form
    $(document).on('click', '.t3js-upload', (event: JQueryEventObject): void => {
      const $me = $(event.currentTarget);
      const $uploadForm = $('.extension-upload-form');

      event.preventDefault();
      if ($me.hasClass(this.expandedUploadFormClass)) {
        $uploadForm.stop().slideUp();
        $me.removeClass(this.expandedUploadFormClass);
      } else {
        $me.addClass(this.expandedUploadFormClass);
        $uploadForm.stop().slideDown();

        new AjaxRequest($me.attr('href')).get().then(async (response: AjaxResponse): Promise<void> => {
          $uploadForm.find('.t3js-upload-form-target').html(await response.resolve());
        });
      }
    });
  }
}

export default UploadForm;
