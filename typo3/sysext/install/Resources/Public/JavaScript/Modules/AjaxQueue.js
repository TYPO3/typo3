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
 * Module: TYPO3/CMS/Install/AjaxQueue
 */
define(['jquery'], function($) {
  'use strict';

  return {
    requests: [],
    requestCount: 0,
    threshold: 5,
    queue: [],

    add: function(payload) {
      this.queue.push(payload);
      this.handleNext();
    },

    flush: function() {
      this.queue = [];
      this.requests.map(function(request) {
        request.abort();
      });
      this.requests = [];
    },

    handleNext: function() {
      if (this.queue.length > 0 && this.requestCount < this.threshold) {
        var that = this;
        this.incrementRequestCount();
        this.sendRequest(that.queue.shift()).always(function() {
          that.decrementRequestCount();
          that.handleNext();
        });
      }
    },

    sendRequest: function(payload) {
      var that = this;
      var xhr = $.ajax(payload);
      this.requests.push(xhr);
      xhr.always(function() {
        const idx = that.requests.indexOf(xhr);
        delete that.requests[idx];
      });

      return xhr;
    },

    incrementRequestCount: function() {
      this.requestCount++;
    },

    decrementRequestCount: function() {
      if (this.requestCount > 0) {
        this.requestCount--;
      }
    },
  };
});
