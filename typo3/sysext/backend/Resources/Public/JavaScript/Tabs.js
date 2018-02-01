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
 * Module: TYPO3/CMS/Backend/Tabs
 * This class handle the tabs in the TYPO3 backend.
 * It stores the last active tab and open it again after a reload,
 */
define(['jquery', 'TYPO3/CMS/Backend/Storage', 'bootstrap'], function($, Storage) {
  'use strict';

  /**
   * Tabs helper
   *
   * @type {{storage: (Storage.Client|*), cacheTimeInSeconds: number, storeLastActiveTab: bool}}
   * @exports TYPO3/CMS/Backend/Tabs
   */
  var Tabs = {
    storage: Storage.Client,
    // cache lifetime in seconds
    cacheTimeInSeconds: 1800,
    storeLastActiveTab: true
  };

  /**
   * Receive active tab from storage
   *
   * @param {String} id
   * @returns {String}
   */
  Tabs.receiveActiveTab = function(id) {
    var target = Tabs.storage.get(id) || '';
    var expire = Tabs.storage.get(id + '.expire') || 0;
    if (expire > Tabs.getTimestamp()) {
      return target;
    }
    return '';
  };

  /**
   * Store active tab in storage
   *
   * @param {String} id
   * @param {String} target
   */
  Tabs.storeActiveTab = function(id, target) {
    Tabs.storage.set(id, target);
    Tabs.storage.set(id + '.expire', Tabs.getTimestamp() + Tabs.cacheTimeInSeconds);
  };

  /**
   * Get unixtimestamp
   *
   * @returns {Number}
   */
  Tabs.getTimestamp = function() {
    return Math.round((new Date()).getTime() / 1000);
  };

  $(function() {
    $('.t3js-tabs').each(function() {
      var $tabContainer = $(this);
      Tabs.storeLastActiveTab = $tabContainer.data('storeLastTab') === 1;
      var currentActiveTab = Tabs.receiveActiveTab($tabContainer.attr('id'));
      if (currentActiveTab) {
        $tabContainer.find('a[href="' + currentActiveTab + '"]').tab('show');
      }
      $tabContainer.on('show.bs.tab', function(e) {
        if (Tabs.storeLastActiveTab) {
          var id = e.currentTarget.id;
          var target = e.target.hash;
          Tabs.storeActiveTab(id, target);
        }
      });
    });
  });

  return Tabs;
});
