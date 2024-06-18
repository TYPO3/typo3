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
 * Module: @typo3/core/document-service
 * @exports @typo3/core/document-service
 */
class DocumentService {
  private promise: Promise<Document> = null;

  public ready(): Promise<Document> {
    return this.promise ?? (this.promise = this.createPromise());
  }

  private async createPromise(): Promise<Document> {
    if (document.readyState !== 'loading') {
      return document;
    }
    await new Promise<void>(resolve => document.addEventListener('DOMContentLoaded', () => resolve(), { once: true }));
    return document;
  }
}

const documentService = new DocumentService();
export default documentService;
