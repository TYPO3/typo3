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
 * Module: TYPO3/CMS/Backend/Wizard
 * API for wizard windows.
 */
define(['jquery',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Backend/Icons',
  'bootstrap'
], function($, Modal, Severity, Icons) {
  'use strict';

  try {
    // fetch from parent
    if (parent && parent.window.TYPO3 && parent.window.TYPO3.Wizard) {
      return parent.window.TYPO3.Wizard;
    }

    // fetch object from outer frame
    if (top && top.TYPO3 && top.TYPO3.Wizard) {
      return top.TYPO3.Wizard;
    }
  } catch (e) {
    // This only happens if the opener, parent or top is some other url (eg a local file)
    // which loaded the current window. Then the browser's cross domain policy jumps in
    // and raises an exception.
    // For this case we are safe and we can create our global object below.
  }

  /**
   * @type {{setup: {slides: Array, settings: {}, forceSelection: boolean, $carousel: null}, originalSetup: {}}}
   * @exports TYPO3/CMS/Backend/Wizard
   */
  var Wizard = {
    setup: {
      slides: [],
      settings: {},
      forceSelection: true,
      $carousel: null
    },
    originalSetup: {}
  };

  /**
   * Initializes the events after building the wizards
   *
   * @private
   */
  Wizard.initializeEvents = function() {
    var $modal = Wizard.setup.$carousel.closest('.modal'),
      $modalTitle = $modal.find('.modal-title'),
      $modalFooter = $modal.find('.modal-footer'),
      $nextButton = $modalFooter.find('button[name="next"]');

    $nextButton.on('click', function() {
      Wizard.setup.$carousel.carousel('next');
    });

    Wizard.setup.$carousel.on('slide.bs.carousel', function() {
      var nextSlideNumber = Wizard.setup.$carousel.data('currentSlide') + 1,
        currentIndex = Wizard.setup.$carousel.data('currentIndex') + 1;

      $modalTitle.text(Wizard.setup.slides[currentIndex].title);

      Wizard.setup.$carousel.data('currentSlide', nextSlideNumber);
      Wizard.setup.$carousel.data('currentIndex', currentIndex);

      if (nextSlideNumber >= Wizard.setup.$carousel.data('realSlideCount')) {
        // Point of no return - hide modal footer disable any closing ability
        $modal.find('.modal-header .close').remove();
        $modalFooter.slideUp();
      } else {
        $modalFooter.find('.progress-bar')
          .width(Wizard.setup.$carousel.data('initialStep') * nextSlideNumber + '%')
          .text(top.TYPO3.lang['wizard.progress']
            .replace('{0}', nextSlideNumber)
            .replace('{1}', Wizard.setup.$carousel.data('slideCount')));
      }

      $nextButton
        .removeClass('btn-' + Severity.getCssClass(Wizard.setup.slides[currentIndex - 1].severity))
        .addClass('btn-' + Severity.getCssClass(Wizard.setup.slides[currentIndex].severity));

      $modal
        .removeClass('modal-severity-' + Severity.getCssClass(Wizard.setup.slides[currentIndex - 1].severity))
        .addClass('modal-severity-' + Severity.getCssClass(Wizard.setup.slides[currentIndex].severity));
    }).on('slid.bs.carousel', function(e) {
      var currentIndex = Wizard.setup.$carousel.data('currentIndex'),
        slide = Wizard.setup.slides[currentIndex];

      Wizard.runSlideCallback(slide, $(e.relatedTarget));

      if (Wizard.setup.forceSelection) {
        Wizard.lockNextStep();
      }
    });

    /**
     * Custom event, closes the wizard
     */
    var cmp = Wizard.getComponent();
    cmp.on('wizard-dismiss', Wizard.dismiss);

    Modal.currentModal.on('hidden.bs.modal', function() {
      cmp.trigger('wizard-dismissed');
    }).on('shown.bs.modal', function() {
      cmp.trigger('wizard-visible');
    });
  };

  /**
   * @param {String} key
   * @param {*} value
   * @returns {Object}
   */
  Wizard.set = function(key, value) {
    Wizard.setup.settings[key] = value;
    return Wizard;
  };

  /**
   * Adds a new slide to the wizard
   *
   * @param {String} identifier
   * @param {String} title
   * @param {String} content
   * @param {String} severity
   * @param {Function} callback
   * @returns {Object}
   */
  Wizard.addSlide = function(identifier, title, content, severity, callback) {
    Wizard.setup.slides.push({
      identifier: identifier,
      title: title,
      content: content || '',
      severity: (typeof severity !== 'undefined' ? severity : Severity.info),
      callback: callback
    });

    return Wizard;
  };

  /**
   * Adds a final processing slide
   *
   * @param {Function} callback
   * @returns {Object}
   */
  Wizard.addFinalProcessingSlide = function(callback) {
    if (typeof callback !== 'function') {
      callback = function() {
        Wizard.dismiss();
      }
    }

    return Icons.getIcon('spinner-circle-dark', Icons.sizes.large, null, null).done(function(markup) {
      var $processingSlide = $('<div />', {class: 'text-center'}).append(markup);
      Wizard.addSlide(
        'final-processing-slide', top.TYPO3.lang['wizard.processing.title'],
        $processingSlide[0].outerHTML,
        Severity.info,
        callback
      );
    });
  };

  /**
   * Processes the footer of the modal
   *
   * @private
   */
  Wizard.addProgressBar = function() {
    var realSlideCount = Wizard.setup.$carousel.find('.item').length,
      slideCount = Math.max(1, realSlideCount),
      initialStep,
      $modal = Wizard.setup.$carousel.closest('.modal'),
      $modalFooter = $modal.find('.modal-footer');

    initialStep = Math.round(100 / slideCount);

    Wizard.setup.$carousel
      .data('initialStep', initialStep)
      .data('slideCount', slideCount)
      .data('realSlideCount', realSlideCount)
      .data('currentIndex', 0)
      .data('currentSlide', 1);

    // Append progress bar to modal footer
    if (slideCount > 1) {
      $modalFooter.prepend(
        $('<div />', {class: 'progress'}).append(
          $('<div />', {
            role: 'progressbar',
            class: 'progress-bar',
            'aria-valuemin': 0,
            'aria-valuenow': initialStep,
            'aria-valuemax': 100
          }).width(initialStep + '%').text(
            top.TYPO3.lang['wizard.progress']
              .replace('{0}', '1')
              .replace('{1}', slideCount)
          )
        )
      );
    }
  };

  /**
   * Generates the markup of slides added by addSlide()
   *
   * @returns {$}
   * @private
   */
  Wizard.generateSlides = function() {
    // Check whether the slides were already generated
    if (Wizard.setup.$carousel !== null) {
      return Wizard.setup.$carousel;
    }

    var slides =
      '<div class="carousel slide" data-ride="carousel" data-interval="false">'
      + '<div class="carousel-inner" role="listbox">';

    for (var i = 0; i < Wizard.setup.slides.length; ++i) {
      var currentSlide = Wizard.setup.slides[i],
        slideContent = currentSlide.content;

      if (typeof slideContent === 'object') {
        slideContent = slideContent.html();
      }
      slides += '<div class="item" data-slide="' + currentSlide.identifier + '">' + slideContent + '</div>';
    }

    slides += '</div></div>';

    Wizard.setup.$carousel = $(slides);
    Wizard.setup.$carousel.find('.item').first().addClass('active');

    return Wizard.setup.$carousel;
  };

  /**
   * Renders the wizard
   *
   * @returns {$}
   */
  Wizard.show = function() {
    var $slides = Wizard.generateSlides(),
      firstSlide = Wizard.setup.slides[0];

    var $modal = Modal.confirm(
      firstSlide.title,
      $slides,
      firstSlide.severity,
      [{
        text: top.TYPO3.lang['wizard.button.cancel'],
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        trigger: function() {
          Wizard.getComponent().trigger('wizard-dismiss');
        }
      }, {
        text: top.TYPO3.lang['wizard.button.next'],
        btnClass: 'btn-' + Severity.getCssClass(firstSlide.severity),
        name: 'next'
      }]
    );

    if (Wizard.setup.forceSelection) {
      Wizard.lockNextStep();
    }

    Wizard.addProgressBar($modal);
    Wizard.initializeEvents();

    Wizard.getComponent().on('wizard-visible', function() {
      Wizard.runSlideCallback(firstSlide, Wizard.setup.$carousel.find('.item').first());
    }).on('wizard-dismissed', function() {
      Wizard.setup = $.extend(true, {}, Wizard.originalSetup);
    });
  };

  /**
   * Runs the callback for the given slide
   *
   * @param {Object} slide
   * @param {$} $slide
   * @private
   */
  Wizard.runSlideCallback = function(slide, $slide) {
    if (typeof slide.callback === 'function') {
      slide.callback($slide, Wizard.setup.settings, slide.identifier);
    }
  };

  /**
   * Get the wizard component
   *
   * @returns {$}
   */
  Wizard.getComponent = function() {
    if (Wizard.setup.$carousel === null) {
      Wizard.generateSlides();
    }
    return Wizard.setup.$carousel;
  };

  /**
   * Closes the wizard window
   */
  Wizard.dismiss = function() {
    Modal.dismiss();
  };

  /**
   * Locks the button for continuing to the next step
   *
   * @returns {$}
   */
  Wizard.lockNextStep = function() {
    var $button = Wizard.setup.$carousel.closest('.modal').find('button[name="next"]');
    $button.prop('disabled', true);

    return $button;
  };

  /**
   * Unlocks the button for continuing to the next step
   *
   * @returns {$}
   */
  Wizard.unlockNextStep = function() {
    var $button = Wizard.setup.$carousel.closest('.modal').find('button[name="next"]');
    $button.prop('disabled', false);

    return $button;
  };

  // Store the initial setup
  Wizard.originalSetup = $.extend(true, {}, Wizard.setup);

  // expose as global object
  TYPO3.Wizard = Wizard;

  return Wizard;
});
