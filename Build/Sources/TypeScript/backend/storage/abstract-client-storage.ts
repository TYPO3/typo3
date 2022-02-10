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

/**
 * Module: @typo3/backend/storage/abstract-client-storage
 * @exports @typo3/backend/storage/abstract-client-storage
 */
export default abstract class AbstractClientStorage {
  protected keyPrefix: string = 't3-';
  protected storage: Storage = null;

  public get(key: string): string {
    if (this.storage === null) {
      return null;
    }
    return this.storage.getItem(this.keyPrefix + key);
  }

  public set(key: string, value: string): void {
    if (this.storage !== null) {
      this.storage.setItem(this.keyPrefix + key, value);
    }
  }

  public unset(key: string): void {
    if (this.storage !== null) {
      this.storage.removeItem(this.keyPrefix + key);
    }
  }

  public unsetByPrefix(prefix: string): void {
    if (this.storage === null) {
      return;
    }
    prefix = this.keyPrefix + prefix;
    Object.keys(this.storage)
      .filter((key: string) => key.startsWith(prefix))
      .forEach((key: string) => this.storage.removeItem(key));
  }

  public clear(): void {
    if (this.storage !== null) {
      this.storage.clear();
    }
  }

  public isset(key: string): boolean {
    if (this.storage === null) {
      return false;
    }
    return this.get(key) !== null;
  }
}
