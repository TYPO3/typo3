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
    requestCount: 0,
    threshold: 10,
    queue: [],

    add: function(payload) {
      var oldComplete = payload.complete;
      var that = this;
      payload.complete = function(jqXHR, textStatus) {
        if (that.queue.length > 0 && that.requestCount <= that.threshold) {
          $.ajax(that.queue.shift()).always(function() {
            that.decrementRequestCount();
          });
        } else {
          that.decrementRequestCount();
        }

        if (oldComplete) {
          oldComplete(jqXHR, textStatus);
        }
      };

      if (this.requestCount >= this.threshold) {
        this.queue.push(payload);
      } else {
        this.incrementRequestCount();
        $.ajax(payload);
      }
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
