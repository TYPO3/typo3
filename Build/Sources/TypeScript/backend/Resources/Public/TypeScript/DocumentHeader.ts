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

import $ from 'jquery';
import DebounceEvent = require('TYPO3/CMS/Core/Event/DebounceEvent');
import ThrottleEvent = require('TYPO3/CMS/Core/Event/ThrottleEvent');

/**
 * Module: TYPO3/CMS/Backend/DocumentHeader
 * Calculates the height of the docHeader and hides it upon scrolling
 */
class DocumentHeader {
  private $documentHeader: JQuery = null;
  private $documentHeaderBars: JQuery = null;
  private $documentHeaderNavigationBar: JQuery = null;
  private $documentHeaderSearchBar: JQuery = null;
  private $moduleBody: JQuery = null;
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
      moduleDocheaderBar: '.t3js-module-docheader-bar',
      moduleNavigationBar: '.t3js-module-docheader-bar-navigation',
      moduleButtonBar: '.t3js-module-docheader-bar-buttons',
      moduleSearchBar: '.t3js-module-docheader-bar-search',
      moduleBody: '.t3js-module-body',

    },
  };

  constructor() {
    $((): void => {
      this.initialize();
    });
  }

  /**
   * Reposition
   */
  public reposition = (): void => {
    this.$documentHeader.css('height', 'auto');
    this.$documentHeaderBars.css('height', 'auto');
    this.$moduleBody.css('padding-top', this.$documentHeader.outerHeight() + this.settings.margin);
  }

  /**
   * Initialize
   */
  private initialize(): void {
    this.$documentHeader = $(this.settings.selectors.moduleDocumentHeader);
    if (this.$documentHeader.length > 0) {
      this.$documentHeaderBars = $(this.settings.selectors.moduleDocheaderBar);
      this.$documentHeaderNavigationBar = $(this.settings.selectors.moduleNavigationBar);
      this.$documentHeaderSearchBar = $(this.settings.selectors.moduleSearchBar).remove();
      if (this.$documentHeaderSearchBar.length > 0) {
        this.$documentHeader.append(this.$documentHeaderSearchBar);
      }
      this.$moduleBody = $(this.settings.selectors.moduleBody);
      this.start();
    }
  }

  /**
   * Start
   */
  private start(): void {
    this.reposition();
    new DebounceEvent('resize', this.reposition).bindTo(window);
    new ThrottleEvent('scroll', this.scroll, 100).bindTo(document.querySelector('.t3js-module-docheader + .t3js-module-body'));
  }

  /**
   * Scroll
   *
   * @param {Event} e
   */
  private scroll = (e: Event): void => {
    this.currentPosition = $(e.target).scrollTop();
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
      this.$documentHeader.css('margin-top', 0);
    }
    if (this.direction === 'down' && (this.changedPosition + this.reactionRange) < this.currentPosition) {
      this.$documentHeader.css('margin-top', (this.$documentHeaderNavigationBar.outerHeight() + 4) * -1);
    }
    this.lastPosition = this.currentPosition;
  }
}

export = new DocumentHeader();
