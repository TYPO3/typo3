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

import '@typo3/backend/element/icon-element';

export default class IconHelper {
  /**
   * Gets a specific icon. A specific "switch" is added due to the integrity
   * flags that are added in the IntegrityService.
   */
  public static getIcon(identifier: string, overlay: string = ''): string {
    identifier = IconHelper.getIconIdentifier(identifier);

    return '<typo3-backend-icon ' + Object.entries({
      'identifier': identifier,
      'overlay': overlay,
      'size': 'small'
    })
      .filter(([key, value]) => key && value !== '')
      .map(([key, value]) => `${key}="${value}"`)
      .join(' ') + '></typo3-backend-icon>';
  }

  public static getIconIdentifier(identifier: string): string {
    switch (identifier) {
      case 'language':
        identifier = 'flags-multiple';
        break;
      case 'integrity':
      case 'info':
        identifier = 'status-dialog-information';
        break;
      case 'success':
        identifier = 'status-dialog-ok';
        break;
      case 'warning':
        identifier = 'status-dialog-warning';
        break;
      case 'error':
        identifier = 'status-dialog-error';
        break;
      default:
    }

    return identifier;
  }
}
