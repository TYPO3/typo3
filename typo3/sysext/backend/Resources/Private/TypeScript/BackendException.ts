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

export class BackendException {
  public readonly message: string;
  public readonly code: number;

  constructor(message: string = '', code: number = 0) {
    this.message = message;
    this.code = code;
  }
}
