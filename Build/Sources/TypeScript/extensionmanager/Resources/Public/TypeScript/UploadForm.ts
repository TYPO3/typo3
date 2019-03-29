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
import 'datatables';
import 'TYPO3/CMS/Backend/jquery.clearable';

class UploadForm {
  public expandedUploadFormClass: string = 'transformed';

  public initializeEvents(): void {
    // Show upload form
    $(document).on('click', '.t3js-upload', (event: JQueryEventObject): void => {
      const $me = $(event.currentTarget);
      const $uploadForm = $('.uploadForm');

      event.preventDefault();
      if ($me.hasClass(this.expandedUploadFormClass)) {
        $uploadForm.stop().slideUp();
        $me.removeClass(this.expandedUploadFormClass);
      } else {
        $me.addClass(this.expandedUploadFormClass);
        $uploadForm.stop().slideDown();

        $.ajax({
          url: $me.attr('href'),
          dataType: 'html',
          success: (data: any): void => {
            $uploadForm.html(data);
          },
        });
      }
    });
  }
}

export = UploadForm;
