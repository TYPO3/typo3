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
 * Module: TYPO3/CMS/Filelist/Filelist
 * @exports TYPO3/CMS/Filelist/Filelist
 */
define(['jquery'], function($) {

  $(function() {
    $('a.filelist-file-title').click(function(event) {
      event.preventDefault();

      var url = $(this).attr('data-url');
      window.location.href = url;
    });

    $('a.btn.filelist-file-edit').click(function(event) {
      event.preventDefault();

      var url = $(this).attr('data-url');
      top.list_frame.location.href = url;
    });

    $('a.btn.filelist-file-view').click(function(event) {
      event.preventDefault();

      var url = $(this).attr('data-url');
      top.openUrlInWindow(url, 'WebFile')
    });

    $('a.btn.filelist-file-replace').click(function(event) {
      event.preventDefault();

      var url = $(this).attr('data-url');
      top.list_frame.location.href = url;
    });

    $('a.btn.filelist-file-rename').click(function(event) {
      event.preventDefault();

      var url = $(this).attr('data-url');
      top.list_frame.location.href = url;
    });

    $('a.btn.filelist-file-info').click(function(event) {
      event.preventDefault();

      var identifier = $(this).attr('data-identifier');
      openFileInfoPopup(identifier);
    });

    $('a.filelist-file-references').click(function(event) {
      event.preventDefault();

      var identifier = $(this).attr('data-identifier');
      openFileInfoPopup(identifier);
    });

  });

  /**
   * @param identifier
   */
  function openFileInfoPopup(identifier) {
    top.launchView('_FILE', identifier);
  }

});
