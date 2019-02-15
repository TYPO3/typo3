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

import {InlineModuleInterface} from './InlineModuleInterface';
import * as $ from 'jquery';
import Router = require('../Router');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/ResetBackendUserUc
 */
class ResetBackendUserUc implements InlineModuleInterface {
  public initialize($trigger: JQuery): void {
    $.ajax({
      url: Router.getUrl('resetBackendUserUc'),
      cache: false,
      beforeSend: (): void => {
        $trigger.addClass('disabled');
      },
      success: (data: any): void => {
        if (data.success === true && Array.isArray(data.status)) {
          if (data.status.length > 0) {
            data.status.forEach((element: any): void => {
              Notification.success(element.message);
            });
          }
        } else {
          Notification.error('Something went wrong ...');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        // If reset fails on server side (typically a 500), do not crash entire install tool
        // but render an error notification instead.
        Notification.error('Resetting backend user uc failed. Please check the system for missing database fields and try again.');
      },
      complete: (): void => {
        $trigger.removeClass('disabled');
      },
    });
  }
}

export = new ResetBackendUserUc();
