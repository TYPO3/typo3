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

import DocumentService from '@typo3/core/document-service';
import FormEngine from '@typo3/backend/form-engine';

/**
 * Handles the "Add record" field control that renders a new FormEngine instance
 */
class AddRecord {
  private controlElement: HTMLElement = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = <HTMLElement>document.querySelector(controlElementId);
      this.controlElement.addEventListener('click', this.registerClickHandler);
    });
  }

  /**
   * @param {Event} e
   */
  private readonly registerClickHandler = (e: Event): void => {
    e.preventDefault();

    const href = this.controlElement.getAttribute('href');
    const url = new URL(href, window.location.origin);
    const ownerUid = url.searchParams.get('P[uid]') || '';
    if (ownerUid.startsWith('NEW')) {
      // The record this field belongs to is itself still unsaved:
      // Wizard/AddController can only link a newly created record back into
      // an already-persisted uid, so a plain "leave without saving?" prompt
      // would both discard this record AND silently fail to link anything.
      // Deliberately not passing along this href's own P[returnUrl] here: for
      // a field belonging to an inline/IRRE child that was itself added via
      // AJAX (record_inline_create), that value is whatever URL rendered the
      // child's markup at ajax-creation time. The ajax endpoint's own URL,
      // not a real, navigable page. EditDocumentController computes the
      // correct return-to-this-document URL itself once the save completes.
      FormEngine.preventFollowAddRecordLinkIfNotSaved({
        originalUid: ownerUid,
        ownerTable: url.searchParams.get('P[table]') || '',
        ownerField: url.searchParams.get('P[field]') || '',
        table: url.searchParams.get('P[params][table]') || '',
        pid: url.searchParams.get('P[params][pid]') || '',
        setValue: url.searchParams.get('P[params][setValue]') || 'append',
        flexFormPath: url.searchParams.get('P[flexFormPath]') || '',
      });
      return;
    }

    FormEngine.preventFollowLinkIfNotSaved(href);
  };
}

export default AddRecord;
