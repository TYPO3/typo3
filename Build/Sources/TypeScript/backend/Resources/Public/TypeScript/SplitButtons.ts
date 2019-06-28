/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with DocumentHeader source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import DocumentSaveActions = require('./DocumentSaveActions');

class SplitButtons {
  constructor() {
    console.warn(
      'TYPO3/CMS/Backend/SplitButtons has been marked as deprecated, consider using TYPO3/CMS/Backend/DocumentSaveActions instead',
    );
  }

  /**
   * Adds a callback being executed before submit
   *
   * @param {Function} callback
   */
  public addPreSubmitCallback(callback: Function): void {
    DocumentSaveActions.getInstance().addPreSubmitCallback(callback);
  }
}

export = new SplitButtons();
