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
 * Module: TYPO3/CMS/Backend/Popover
 * API for popover windows powered by Twitter Bootstrap.
 */
define(['jquery', 'bootstrap'], function($) {

  /**
   * the main popover object
   *
   * @type {{}}
   * @exports TYPO3/CMS/Backend/Popover
   */
  var Popover = {};

  /**
   * Initialize
   */
  Popover.initialize = function(selector) {
    selector = selector || '[data-toggle="popover"]';
    $(selector).popover();
  };

  /**
   * Popover wrapper function
   *
   * @param {Object} $element
   */
  Popover.popover = function($element) {
    $element.popover();
  };

  /**
   * Set popover options on $element
   *
   * @param {Object} $element
   * @param {Object} options
   */
  Popover.setOptions = function($element, options) {
    options = options || {};
    var title = options.title || $element.data('title') || '';
    var content = options.content || $element.data('content') || '';
    $element
      .attr('data-original-title', title)
      .attr('data-content', content)
      .attr('data-placement', 'auto')
      .popover(options);
  };

  /**
   * Set popover option on $element
   *
   * @param {Object} $element
   * @param {String} key
   * @param {String} value
   */
  Popover.setOption = function($element, key, value) {
    $element.data('bs.popover').options[key] = value;
  };

  /**
   * Show popover with title and content on $element
   *
   * @param {Object} $element
   */
  Popover.show = function($element) {
    $element.popover('show');
  };

  /**
   * Hide popover on $element
   *
   * @param {Object} $element
   */
  Popover.hide = function($element) {
    $element.popover('hide');
  };

  /**
   * Destroy popover on $element
   *
   * @param {Object} $element
   */
  Popover.destroy = function($element) {
    $element.popover('destroy');
  };

  /**
   * Toggle popover on $element
   *
   * @param {Object} $element
   */
  Popover.toggle = function($element) {
    $element.popover('toggle');
  };

  Popover.initialize();
  TYPO3.Popover = Popover;
  return Popover;
});
