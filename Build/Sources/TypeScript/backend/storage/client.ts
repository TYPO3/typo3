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

import AbstractClientStorage from './abstract-client-storage';

/**
 * Module: @typo3/backend/storage/client
 * Wrapper for localStorage
 * @exports @typo3/backend/storage/client
 */
class Client extends AbstractClientStorage {
  public constructor() {
    super();
    this.storage = localStorage;
  }
}

export default new Client();
