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

import Client = require('./Storage/Client');
import Persistent = require('./Storage/Persistent');

/**
 * Module: TYPO3/CMS/Backend/Storage
 * Adds a public API for the browsers' localStorage called
 * TYPO3.Storage.Client and the Backend Users "uc",
 * available via TYPO3.Storage.Persistent
 * @exports TYPO3/CMS/Backend/Storage
 * @deprecated
 */
class Storage {
  public Client: any;
  public Persistent: any;

  constructor() {
    if (console) {
      console.warn(
        'TYPO3/CMS/Backend/Storage and TYPO3.Storage are deprecated since TYPO3 v9 and will be removed in TYPO3 v10.',
      );
    }
    this.Client = Client;
    this.Persistent = Persistent;
  }
}

let storageObject;
try {
  // fetch from opening window
  if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.Storage) {
    storageObject = window.opener.TYPO3.Storage;
  }

  // fetch from parent
  if (parent && parent.window.TYPO3 && parent.window.TYPO3.Storage) {
    storageObject = parent.window.TYPO3.Storage;
  }

  // fetch object from outer frame
  if (top && top.TYPO3.Storage) {
    storageObject = top.TYPO3.Storage;
  }
} catch (e) {
  // This only happens if the opener, parent or top is some other url (eg a local file)
  // which loaded the current window. Then the browser's cross domain policy jumps in
  // and raises an exception.
  // For this case we are safe and we can create our global object below.
}

if (!storageObject) {
  storageObject = new Storage();
}

TYPO3.Storage = storageObject;
export = storageObject;
