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
 * Module: TYPO3/CMS/Install/CardLayout
 */
define(['jquery', 'bootstrap'], function($) {
  'use strict';

  /**
   *
   * @type {{transitionInProgress: boolean}}
   */
  var CardLayout = {
    transitionInProgress: false
  };

  /**
   * Initialize the CardLayout, bind events
   */
  CardLayout.initialize = function() {
    $(document).on('click', '.gridder-list', function(e) {
      e.preventDefault();
      var $element = $(this);
      if (!$element.hasClass('selectedItem')) {
        CardLayout.openCard($element);
      } else {
        CardLayout.closeCard($element);
      }
    });

    // Close current and open previous card
    $(document).on('click', '.gridder-nav-prev', function(e) {
      e.preventDefault();
      CardLayout.openPrevCard();
    });

    // Close current and open next card
    $(document).on('click', '.gridder-nav-next', function(e) {
      e.preventDefault();
      CardLayout.openNextCard();
    });

    // Close current open card
    $(document).on('click', '.gridder-close', function(e) {
      e.preventDefault();
      CardLayout.closeCurrentCard();
    });

    CardLayout.checkNavigationButtons();
  };

  /**
   * Find and return the current open card
   *
   * @returns {jQuery}
   */
  CardLayout.getCurrentOpenCard = function() {
    return $('.gridder-content.gridder-show').prev();
  };

  /**
   * Find and close the current open card
   */
  CardLayout.closeCurrentCard = function() {
    CardLayout.closeCard(CardLayout.getCurrentOpenCard());
    CardLayout.checkNavigationButtons();
  };

  /**
   * Open the given card and call the callback function
   *
   * @param {jQuery} $element
   * @param {function} callback
   * @returns {boolean}
   */
  CardLayout.openCard = function($element, callback) {
    if (CardLayout.transitionInProgress) {
      return false;
    }
    CardLayout.transitionInProgress = true;
    $('.gridder-list').removeClass('selectedItem');
    $('.gridder-content.gridder-show').slideUp(function() {
      $(this).removeClass('gridder-show');
    });
    $element.addClass('selectedItem');
    $element.next().addClass('gridder-show').slideDown(function() {
      CardLayout.transitionInProgress = false;
      if (typeof callback === 'function') {
        callback();
      }
      CardLayout.checkNavigationButtons();
    });
    $(document).trigger('cardlayout:card-opened', [$element]);
  };

  /**
   * Close the given card and call the callback function
   *
   * @param {jQuery} $element
   * @param {function} callback
   * @returns {boolean}
   */
  CardLayout.closeCard = function($element, callback) {
    if (CardLayout.transitionInProgress) {
      return false;
    }
    CardLayout.transitionInProgress = true;
    var $contentContainer = $element.next();
    $element.removeClass('selectedItem');
    $contentContainer.slideUp(function() {
      $contentContainer.removeClass('gridder-show');
      CardLayout.transitionInProgress = false;
      if (typeof callback === 'function') {
        callback();
      }
      CardLayout.checkNavigationButtons();
    });
    $(document).trigger('cardlayout:card-closed', [$element]);
  };

  /**
   * Find the next card and open it, if it exists
   */
  CardLayout.openNextCard = function() {
    var $currentOpenCard = CardLayout.getCurrentOpenCard();
    var $nextCard = $currentOpenCard.next().next();
    if ($nextCard.length) {
      CardLayout.closeCard($currentOpenCard, function() {
        CardLayout.openCard($nextCard);
      });
    }
  };

  /**
   * Find the previous card and open it, if it exists
   */
  CardLayout.openPrevCard = function() {
    var $currentOpenCard = CardLayout.getCurrentOpenCard();
    var $nextCard = $currentOpenCard.prev().prev();
    if ($nextCard.length) {
      CardLayout.closeCard($currentOpenCard, function() {
        CardLayout.openCard($nextCard);
      });
    }
  };

  /**
   * Check the navigation icons and enable/disable the buttons
   */
  CardLayout.checkNavigationButtons = function() {
    var $currentOpenCard = CardLayout.getCurrentOpenCard();
    if ($currentOpenCard.length === 0) {
      $('.gridder-close').addClass('disabled');
    } else {
      $('.gridder-close').removeClass('disabled');
    }
    if ($currentOpenCard.prev().prev().length === 0) {
      $('.gridder-nav-prev').addClass('disabled');
    } else {
      $('.gridder-nav-prev').removeClass('disabled');
    }
    if ($currentOpenCard.next().next().length === 0) {
      $('.gridder-nav-next').addClass('disabled');
    } else {
      $('.gridder-nav-next').removeClass('disabled');
    }
  };

  return CardLayout;
});
