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

/**
 * Module: TYPO3/CMS/Backend/DocumentHeader
 * Calculates the height of the docHeader and hides it upon scrolling
 */
define(['jquery'], function($) {
  'use strict';

  /**
   *
   * @type {{$documentHeader: null, $documentHeaderBars: null, $documentHeaderNavigationBar: null, $documentHeaderSearchBar: null, $moduleBody: null, direction: string, reactionRange: number, lastPosition: number, currentPosition: number, changedPosition: number, settings: {margin: number, offset: number, selectors: {moduleDocumentHeader: string, moduleDocheaderBar: string, moduleNavigationBar: string, moduleButtonBar: string, moduleSearchBar: string, moduleBody: string}}}}
   * @exports TYPO3/CMS/Backend/DocumentHeader
   */
  var DocumentHeader = {
    $documentHeader: null,
    $documentHeaderBars: null,
    $documentHeaderNavigationBar: null,
    $documentHeaderSearchBar: null,
    $moduleBody: null,
    direction: 'down',
    reactionRange: 300,
    lastPosition: 0,
    currentPosition: 0,
    changedPosition: 0,
    settings: {
      margin: 24,
      offset: 100,
      selectors: {
        moduleDocumentHeader: '.t3js-module-docheader',
        moduleDocheaderBar: '.t3js-module-docheader-bar',
        moduleNavigationBar: '.t3js-module-docheader-bar-navigation',
        moduleButtonBar: '.t3js-module-docheader-bar-buttons',
        moduleSearchBar: '.t3js-module-docheader-bar-search',
        moduleBody: '.t3js-module-body'

      }
    }
  };

  /**
   * Reposition
   */
  DocumentHeader.reposition = function() {
    DocumentHeader.$documentHeader.css('height', 'auto');
    DocumentHeader.$documentHeaderBars.css('height', 'auto');
    DocumentHeader.$moduleBody.css('padding-top', DocumentHeader.$documentHeader.outerHeight() + DocumentHeader.settings.margin);
  };

  /**
   * Scroll
   */
  DocumentHeader.scroll = function() {
    DocumentHeader.currentPosition = $(this).scrollTop();
    if (DocumentHeader.currentPosition > DocumentHeader.lastPosition) {
      if (DocumentHeader.direction !== 'down') {
        DocumentHeader.direction = 'down';
        DocumentHeader.changedPosition = DocumentHeader.currentPosition;
      }
    } else if (DocumentHeader.currentPosition < DocumentHeader.lastPosition) {
      if (DocumentHeader.direction !== 'up') {
        DocumentHeader.direction = 'up';
        DocumentHeader.changedPosition = DocumentHeader.currentPosition;
      }
    }
    if (DocumentHeader.direction === 'up' && (DocumentHeader.changedPosition - DocumentHeader.reactionRange) < DocumentHeader.currentPosition) {
      DocumentHeader.$documentHeader.css('margin-top', 0);
    }
    if (DocumentHeader.direction === 'down' && (DocumentHeader.changedPosition + DocumentHeader.reactionRange) < DocumentHeader.currentPosition) {
      DocumentHeader.$documentHeader.css('margin-top', (DocumentHeader.$documentHeaderNavigationBar.outerHeight() + 4) * -1);
    }
    DocumentHeader.lastPosition = DocumentHeader.currentPosition;
  };

  /**
   * Start
   */
  DocumentHeader.start = function() {
    DocumentHeader.reposition();
    $(window).on('resize', DocumentHeader.reposition);
    $('.t3js-module-docheader + .t3js-module-body').on('scroll', DocumentHeader.scroll);
  };

  /**
   * Initialize
   */
  DocumentHeader.initialize = function() {
    DocumentHeader.$documentHeader = $(DocumentHeader.settings.selectors.moduleDocumentHeader);
    if (DocumentHeader.$documentHeader.length > 0) {
      DocumentHeader.$documentHeaderBars = $(DocumentHeader.settings.selectors.moduleDocheaderBar);
      DocumentHeader.$documentHeaderNavigationBar = $(DocumentHeader.settings.selectors.moduleNavigationBar);
      DocumentHeader.$documentHeaderSearchBar = $(DocumentHeader.settings.selectors.moduleSearchBar).remove();
      if (DocumentHeader.$documentHeaderSearchBar.length > 0) {
        DocumentHeader.$documentHeader.append(DocumentHeader.$documentHeaderSearchBar);
      }
      DocumentHeader.$moduleBody = $(DocumentHeader.settings.selectors.moduleBody);
      DocumentHeader.start();
    }
  };

  $(DocumentHeader.initialize);

  return DocumentHeader;
});
