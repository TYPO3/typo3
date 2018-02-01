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
 * Module: TYPO3/CMS/Viewpage/Main
 * Main logic for resizing the view of the frame
 */
define([
  'jquery',
  'TYPO3/CMS/Backend/Storage/Persistent',
  'jquery-ui/resizable'
], function($, PersistentStorage) {
  'use strict';

  /**
   * @type {{<resizableContainerIdentifier: string, sizeIdentifier: string, moduleBodySelector: string, storagePrefix: string, $iframe: null, $resizableContainer: null, $sizeSelector: null}}
   * @exports TYPO3/CMS/Viewpage/Main
   */
  var ViewPage = {

    resizableContainerIdentifier: '.t3js-viewpage-resizeable',
    sizeIdentifier: ' .t3js-viewpage-size',
    moduleBodySelector: '.t3js-module-body',

    defaultLabel: $('.t3js-preset-custom-label').html().trim(),
    minimalHeight: 300,
    minimalWidth: 300,

    storagePrefix: 'moduleData.web_view.States.',
    $iframe: null,
    $resizableContainer: null,
    $sizeSelector: null,

    customSelector: '.t3js-preset-custom',
    customWidthSelector: '.t3js-preset-custom-width',
    customHeightSelector: '.t3js-preset-custom-height',

    changeOrientationSelector: '.t3js-change-orientation',
    changePresetSelector: '.t3js-change-preset',

    inputWidthSelector: '.t3js-viewpage-input-width',
    inputHeightSelector: '.t3js-viewpage-input-height',

    currentLabelSelector: '.t3js-viewpage-current-label',
    topbarContainerSelector: '.t3js-viewpage-topbar',

    queue: [],
    queueIsRunning: false,
    queueDelayTimer: null

  };

  ViewPage.persistQueue = function() {
    if (ViewPage.queueIsRunning === false && ViewPage.queue.length >= 1) {
      ViewPage.queueIsRunning = true;
      var item = ViewPage.queue.shift();
      PersistentStorage.set(item.storageIdentifier, item.data).done(function() {
        ViewPage.queueIsRunning = false;
        ViewPage.persistQueue();
      });
    }
  }

  ViewPage.addToQueue = function(storageIdentifier, data) {
    var item = {
      'storageIdentifier': storageIdentifier,
      'data': data
    };
    ViewPage.queue.push(item);
    if (ViewPage.queue.length >= 1) {
      ViewPage.persistQueue();
    }
  }

  ViewPage.setSize = function(width, height) {
    if (isNaN(height)) {
      height = ViewPage.calculateContainerMaxHeight();
    }
    if (height < ViewPage.minimalHeight) {
      height = ViewPage.minimalHeight;
    }
    if (isNaN(width)) {
      width = ViewPage.calculateContainerMaxWidth();
    }
    if (width < ViewPage.minimalWidth) {
      width = ViewPage.minimalWidth;
    }

    $(ViewPage.inputWidthSelector).val(width);
    $(ViewPage.inputHeightSelector).val(height);

    ViewPage.$resizableContainer.css({
      width: width,
      height: height,
      left: 0
    });
  }

  ViewPage.getCurrentWidth = function() {
    return $(ViewPage.inputWidthSelector).val();
  }

  ViewPage.getCurrentHeight = function() {
    return $(ViewPage.inputHeightSelector).val();
  }

  ViewPage.setLabel = function(label) {
    $(ViewPage.currentLabelSelector).html(label);
  }

  ViewPage.getCurrentLabel = function() {
    return $(ViewPage.currentLabelSelector).html().trim();
  }

  ViewPage.persistCurrentPreset = function() {
    var data = {
      width: ViewPage.getCurrentWidth(),
      height: ViewPage.getCurrentHeight(),
      label: ViewPage.getCurrentLabel()
    }
    ViewPage.addToQueue(ViewPage.storagePrefix + 'current', data);
  }

  ViewPage.persistCustomPreset = function() {
    var data = {
      width: ViewPage.getCurrentWidth(),
      height: ViewPage.getCurrentHeight()
    }
    $(ViewPage.customSelector).data("width", data.width);
    $(ViewPage.customSelector).data("height", data.height);
    $(ViewPage.customWidthSelector).html(data.width);
    $(ViewPage.customHeightSelector).html(data.height);
    ViewPage.addToQueue(ViewPage.storagePrefix + 'custom', data);
  }

  ViewPage.persistCustomPresetAfterChange = function() {
    clearTimeout(ViewPage.queueDelayTimer);
    ViewPage.queueDelayTimer = setTimeout(function() {
      ViewPage.persistCustomPreset();
    }, 1000);
  };

  /**
   * Initialize
   */
  ViewPage.initialize = function() {

    ViewPage.$iframe = $('#tx_viewpage_iframe');
    ViewPage.$resizableContainer = $(ViewPage.resizableContainerIdentifier);
    ViewPage.$sizeSelector = $(ViewPage.sizeIdentifier);

    // Change orientation
    $(document).on('click', ViewPage.changeOrientationSelector, function() {
      var width = $(ViewPage.inputHeightSelector).val();
      var height = $(ViewPage.inputWidthSelector).val();
      ViewPage.setSize(width, height);
      ViewPage.persistCurrentPreset();
    });

    // On change
    $(document).on('change', ViewPage.inputWidthSelector, function() {
      var width = $(ViewPage.inputWidthSelector).val();
      var height = $(ViewPage.inputHeightSelector).val();
      ViewPage.setSize(width, height);
      ViewPage.setLabel(ViewPage.defaultLabel);
      ViewPage.persistCustomPresetAfterChange();
    });
    $(document).on('change', ViewPage.inputHeightSelector, function() {
      var width = $(ViewPage.inputWidthSelector).val();
      var height = $(ViewPage.inputHeightSelector).val();
      ViewPage.setSize(width, height);
      ViewPage.setLabel(ViewPage.defaultLabel);
      ViewPage.persistCustomPresetAfterChange();
    });

    // Add event to width selector so the container is resized
    $(document).on('click', ViewPage.changePresetSelector, function() {
      var data = $(this).data();
      ViewPage.setSize(parseInt(data.width), parseInt(data.height));
      ViewPage.setLabel(data.label);
      ViewPage.persistCurrentPreset();
    });

    // Initialize the jQuery UI Resizable plugin
    ViewPage.$resizableContainer.resizable({
      handles: 'w, sw, s, se, e'
    });

    ViewPage.$resizableContainer.on('resizestart', function() {
      // Add iframe overlay to prevent losing the mouse focus to the iframe while resizing fast
      $(this).append('<div id="viewpage-iframe-cover" style="z-index:99;position:absolute;width:100%;top:0;left:0;height:100%;"></div>');
    });

    ViewPage.$resizableContainer.on('resize', function(evt, ui) {
      ui.size.width = ui.originalSize.width + ((ui.size.width - ui.originalSize.width) * 2);
      if (ui.size.height < ViewPage.minimalHeight) {
        ui.size.height = ViewPage.minimalHeight;
      }
      if (ui.size.width < ViewPage.minimalWidth) {
        ui.size.width = ViewPage.minimalWidth;
      }
      $(ViewPage.inputWidthSelector).val(ui.size.width);
      $(ViewPage.inputHeightSelector).val(ui.size.height);
      ViewPage.$resizableContainer.css({
        left: 0
      });
      ViewPage.setLabel(ViewPage.defaultLabel);
    });

    ViewPage.$resizableContainer.on('resizestop', function() {
      $('#viewpage-iframe-cover').remove();
      ViewPage.persistCurrentPreset();
      ViewPage.persistCustomPreset();
    });
  };

  /**
   * @returns {Number}
   */
  ViewPage.calculateContainerMaxHeight = function() {
    ViewPage.$resizableContainer.hide();
    var $moduleBody = $(ViewPage.moduleBodySelector);
    var padding = $moduleBody.outerHeight() - $moduleBody.height(),
      documentHeight = $(document).height(),
      topbarHeight = $(ViewPage.topbarContainerSelector).outerHeight();
    ViewPage.$resizableContainer.show();
    return documentHeight - padding - topbarHeight - 8;
  };

  /**
   * @returns {Number}
   */
  ViewPage.calculateContainerMaxWidth = function() {
    ViewPage.$resizableContainer.hide();
    var $moduleBody = $(ViewPage.moduleBodySelector);
    var padding = $moduleBody.outerWidth() - $moduleBody.width(),
      documentWidth = $(document).width();
    ViewPage.$resizableContainer.show();
    return parseInt(documentWidth - padding);
  };

  /**
   * @param {String} url
   * @returns {{}}
   */
  ViewPage.getUrlVars = function(url) {
    var vars = {};
    var hash;
    var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      vars[hash[0]] = hash[1];
    }
    return vars;
  };

  $(ViewPage.initialize);

  return ViewPage;
});
