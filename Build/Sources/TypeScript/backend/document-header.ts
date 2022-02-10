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
import ThrottleEvent from '@typo3/core/event/throttle-event';

/**
 * Module: @typo3/backend/document-header
 * Folds docHeader when scrolling down, and reveals when scrollup up
 */
class DocumentHeader {
  private documentHeader: HTMLElement = null;

  private direction: string = 'down';
  private reactionRange: number = 300;
  private lastPosition: number = 0;
  private currentPosition: number = 0;
  private changedPosition: number = 0;
  private settings: any = {
    margin: 24,
    offset: 100,
    selectors: {
      moduleDocumentHeader: '.t3js-module-docheader',
      moduleSearchBar: '.t3js-module-docheader-bar-search',
    },
  };

  constructor() {
    DocumentService.ready().then((): void => {
      this.documentHeader = document.querySelector(this.settings.selectors.moduleDocumentHeader);
      if (this.documentHeader === null) {
        return;
      }

      const moduleElement = this.documentHeader.parentElement;
      new ThrottleEvent('scroll', this.scroll, 100).bindTo(moduleElement);
    });
  }

  /**
   * Scroll
   *
   * @param {Event} e
   */
  private scroll = (e: Event): void => {
    this.currentPosition = (e.target as HTMLElement).scrollTop;
    if (this.currentPosition > this.lastPosition) {
      if (this.direction !== 'down') {
        this.direction = 'down';
        this.changedPosition = this.currentPosition;
      }
    } else if (this.currentPosition < this.lastPosition) {
      if (this.direction !== 'up') {
        this.direction = 'up';
        this.changedPosition = this.currentPosition;
      }
    }
    if (this.direction === 'up' && (this.changedPosition - this.reactionRange) < this.currentPosition) {
      this.documentHeader.classList.remove('module-docheader-folded');
    }
    if (this.direction === 'down' && (this.changedPosition + this.reactionRange) < this.currentPosition) {
      this.documentHeader.classList.add('module-docheader-folded');
    }
    this.lastPosition = this.currentPosition;
  }
}

export default new DocumentHeader();
