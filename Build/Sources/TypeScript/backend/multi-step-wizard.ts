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

import {SeverityEnum} from './enum/severity';
import $ from 'jquery';
import Modal from './modal';
import Severity from './severity';
import Icons from './icons';


interface MultiStepWizardSettings {
  [key: string]: any;
}

interface MultiStepWizardSetup {
  slides: Array<any>;
  settings: MultiStepWizardSettings;
  forceSelection: boolean;
  $carousel: JQuery;
}

interface Slide {
  identifier: string;
  title: string;
  progressBarTitle: string;
  content: string|JQuery;
  severity: SeverityEnum;
  callback?: Function;
}

/**
 * Module: @typo3/backend/multi-step-wizard
 * Multi step wizard within a modal
 * @exports @typo3/backend/multi-step-wizard
 */
class MultiStepWizard {
  private setup: MultiStepWizardSetup;
  private readonly originalSetup: MultiStepWizardSetup;

  constructor() {
    this.setup = {
      slides: [],
      settings: {},
      forceSelection: true,
      $carousel: null,
    };
    this.originalSetup = $.extend(true, {}, this.setup);
  }

  /**
   * @param {string} key
   * @param {any} value
   * @returns {MultiStepWizard}
   */
  public set(key: string, value: any): MultiStepWizard {
    this.setup.settings[key] = value;
    return this;
  }

  /**
   * Add a slide item to carousel as an additional step
   *
   * @param {string} identifier
   * @param {string} title
   * @param {string} progressBarTitle
   * @param {string} content
   * @param {SeverityEnum} severity
   * @param {Function} callback
   * @returns {MultiStepWizard}
   */
  public addSlide(
    identifier: string,
    title: string,
    content: string = '',
    severity: SeverityEnum = SeverityEnum.info,
    progressBarTitle: string,
    callback?: Function,
  ): MultiStepWizard {
    const slide: Slide = {
      identifier: identifier,
      title: title,
      content: content,
      severity: severity,
      progressBarTitle: progressBarTitle,
      callback: callback,
    };
    this.setup.slides.push(slide);
    return this;
  }

  /**
   * Add the final processing slide as the last step
   *
   * @param {Function} callback
   * @returns {Promise<string>}
   */
  public addFinalProcessingSlide(callback?: Function): Promise<void> {
    if (!callback) {
      callback = (): void => {
        this.dismiss();
      };
    }

    return Icons.getIcon('spinner-circle', Icons.sizes.default, null, null).then((markup: string) => {
      let $processingSlide = $('<div />', {class: 'text-center'}).append(markup);
      this.addSlide(
        'final-processing-slide', top.TYPO3.lang['wizard.processing.title'],
        $processingSlide[0].outerHTML,
        Severity.info,
        null,
        callback,
      );
    });
  }

  /**
   * Create wizard with modal, buttons, progress bar and carousel
   */
  public show(): void {
    let $slides = this.generateSlides();
    let firstSlide = this.setup.slides[0];

    Modal.confirm(
      firstSlide.title,
      $slides,
      firstSlide.severity,
      [{
        text: top.TYPO3.lang['wizard.button.cancel'],
        active: true,
        btnClass: 'btn-default pull-left',
        name: 'cancel',
        trigger: (): void => {
          this.getComponent().trigger('wizard-dismiss');
        },
      }, {
        text: top.TYPO3.lang['wizard.button.prev'],
        btnClass: 'btn-' + Severity.getCssClass(firstSlide.severity),
        name: 'prev',
      }, {
        text: top.TYPO3.lang['wizard.button.next'],
        btnClass: 'btn-' + Severity.getCssClass(firstSlide.severity),
        name: 'next',
      }],
      ['modal-multi-step-wizard'],
    );

    this.addButtonContainer();
    this.addProgressBar();
    this.initializeEvents();

    this.getComponent().on('wizard-visible', (): void => {
      this.runSlideCallback(firstSlide, this.setup.$carousel.find('.carousel-item').first());
    }).on('wizard-dismissed', (): void => {
      this.setup = $.extend(true, {}, this.originalSetup);
    });
  }

  /**
   * @returns {JQuery}
   */
  public getComponent(): JQuery {
    if (this.setup.$carousel === null) {
      this.generateSlides();
    }
    return this.setup.$carousel;
  }

  /**
   * Close the modal / wizard
   */
  public dismiss(): void {
    Modal.dismiss();
  }

  /**
   * Lock the button for the next step
   *
   * @returns {JQuery}
   */
  public lockNextStep(): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="next"]');
    $button.prop('disabled', true);
    return $button;
  }

  /**
   * Unlock the button for the next step
   *
   * @returns {JQuery}
   */
  public unlockNextStep(): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="next"]');
    $button.prop('disabled', false);
    return $button;
  }

  /**
   * Lock the button for the prev step
   *
   * @returns {JQuery}
   */
  public lockPrevStep(): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="prev"]');
    $button.prop('disabled', true);
    return $button;
  }

  /**
   * Unlock the button for the prev step
   *
   * @returns {JQuery}
   */
  public unlockPrevStep(): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="prev"]');
    $button.prop('disabled', false);
    return $button;
  }

  /**
   * Trigger a step button (prev or next)
   *
   * @param {string} direction
   * @returns {JQuery}
   */
  public triggerStepButton(direction: string): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="' + direction + '"]');
    if ($button.length > 0 && $button.prop('disabled') !== true) {
      $button.trigger('click');
    }
    return $button;
  }

  /**
   * Blur the button for the cancel step
   *
   * @returns {JQuery}
   */
  public blurCancelStep(): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="cancel"]');
    $button.trigger('blur');
    return $button;
  }

  /**
   * Register all events
   *
   * @private
   */
  private initializeEvents(): void {
    let $modal = this.setup.$carousel.closest('.modal');
    this.initializeSlideNextEvent($modal);
    this.initializeSlidePrevEvent($modal);

    // Event fires when the slide transition is invoked
    this.setup.$carousel.on('slide.bs.carousel', (evt: any): void => {
      if (evt.direction === 'left') {
        this.nextSlideChanges($modal);
      } else {
        this.prevSlideChanges($modal);
      }
    })
      // Event is fired when the carousel has completed its slide transition
      .on('slid.bs.carousel', (evt: JQueryEventObject): void => {
        let currentIndex = this.setup.$carousel.data('currentIndex');
        let slide = this.setup.slides[currentIndex];

        this.runSlideCallback(slide, $(evt.relatedTarget));

        if (this.setup.forceSelection) {
          this.lockNextStep();
        }
      });

    // Custom event, closes the wizard
    let cmp = this.getComponent();
    cmp.on('wizard-dismiss', this.dismiss);

    Modal.currentModal.on('hidden.bs.modal', (): void => {
      cmp.trigger('wizard-dismissed');
    }).on('shown.bs.modal', (): void => {
      cmp.trigger('wizard-visible');
    });
  }

  private initializeSlideNextEvent($modal: JQuery) {
    let $modalFooter = $modal.find('.modal-footer');
    let $nextButton = $modalFooter.find('button[name="next"]');
    $nextButton.off().on('click', (): void => {
      this.setup.$carousel.carousel('next');
    });
  }

  private initializeSlidePrevEvent($modal: JQuery) {
    let $modalFooter = $modal.find('.modal-footer');
    let $prevButton = $modalFooter.find('button[name="prev"]');
    $prevButton.off().on('click', (): void => {
      this.setup.$carousel.carousel('prev');
    });
  }

  /**
   * All changes after applying the next-button
   *
   * @param {JQuery} $modal
   * @private
   */
  private nextSlideChanges($modal: JQuery): void {
    this.initializeSlideNextEvent($modal);

    const $modalTitle = $modal.find('.modal-title');
    const $modalFooter = $modal.find('.modal-footer');
    const nextSlideNumber = this.setup.$carousel.data('currentSlide') + 1;
    const currentIndex = this.setup.$carousel.data('currentIndex');
    const nextIndex = currentIndex + 1;

    $modalTitle.text(this.setup.slides[nextIndex].title);

    this.setup.$carousel.data('currentSlide', nextSlideNumber);
    this.setup.$carousel.data('currentIndex', nextIndex);

    const progressBars = $modalFooter.find('.progress-bar');

    // Hide current progress bar section
    progressBars
      .eq(currentIndex)
      .width('0%');

    // Increase size of next progress bar section
    progressBars
      .eq(nextIndex)
      .width(this.setup.$carousel.data('initialStep') * nextSlideNumber + '%')
      .removeClass('inactive');

    this.updateCurrentSeverity($modal, currentIndex, nextIndex);
  }

  /**
   * All changes after applying the prev-button
   *
   * @param {JQuery} $modal
   * @private
   */
  private prevSlideChanges($modal: JQuery): void {
    this.initializeSlidePrevEvent($modal);

    const $modalTitle = $modal.find('.modal-title');
    const $modalFooter = $modal.find('.modal-footer');
    const $nextButton = $modalFooter.find('button[name="next"]');
    const nextSlideNumber = this.setup.$carousel.data('currentSlide') - 1;
    const currentIndex = this.setup.$carousel.data('currentIndex');
    const nextIndex = currentIndex - 1;

    this.setup.$carousel.data('currentSlide', nextSlideNumber);
    this.setup.$carousel.data('currentIndex', nextIndex);

    $modalTitle.text(this.setup.slides[nextIndex].title);

    $modalFooter.find('.progress-bar.last-step')
      .width(this.setup.$carousel.data('initialStep') + '%')
      .text(this.getProgressBarTitle(this.setup.$carousel.data('slideCount') - 1));

    $nextButton.text(top.TYPO3.lang['wizard.button.next']);

    const progressBars = $modalFooter.find('.progress-bar');

    // Reset size of current progress bar
    progressBars
      .eq(currentIndex)
      .width(this.setup.$carousel.data('initialStep') + '%')
      .addClass('inactive');

    // Enable next (previous) progress bar again
    progressBars
      .eq(nextIndex)
      .width(this.setup.$carousel.data('initialStep') * nextSlideNumber + '%')
      .removeClass('inactive');

    this.updateCurrentSeverity($modal, currentIndex, nextIndex);
  }

  /**
   * Update severity of modal and buttons when changing slides.
   *
   * @param $modal
   * @param currentIndex
   * @param nextIndex
   * @private
   */
  private updateCurrentSeverity($modal: JQuery, currentIndex: number, nextIndex: number): void {
    const $modalFooter = $modal.find('.modal-footer');
    const $nextButton = $modalFooter.find('button[name="next"]');

    $nextButton
      .removeClass('btn-' + Severity.getCssClass(this.setup.slides[currentIndex].severity))
      .addClass('btn-' + Severity.getCssClass(this.setup.slides[nextIndex].severity));

    $modal
      .removeClass('modal-severity-' + Severity.getCssClass(this.setup.slides[currentIndex].severity))
      .addClass('modal-severity-' + Severity.getCssClass(this.setup.slides[nextIndex].severity));
  }

  /**
   * get custom progress bar label of current slide
   * or fallback label
   *
   * @private
   */
  private getProgressBarTitle(slideIndex: number): string {
    let progessBarTitle;

    if (this.setup.slides[slideIndex].progressBarTitle === null) {
      if (slideIndex === 0) {
        progessBarTitle = top.TYPO3.lang['wizard.progressStep.start'];
      } else if (slideIndex >= this.setup.$carousel.data('slideCount') - 1) {
        progessBarTitle = top.TYPO3.lang['wizard.progressStep.finish'];
      } else {
        progessBarTitle = top.TYPO3.lang['wizard.progressStep'] + String(slideIndex + 1);
      }
    } else {
      progessBarTitle = this.setup.slides[slideIndex].progressBarTitle;
    }

    return progessBarTitle;
  }

  /**
   * @param {Slide} slide
   * @param {JQuery} $slide
   * @private
   */
  private runSlideCallback(slide: Slide, $slide: JQuery): void {
    if (typeof slide.callback === 'function') {
      slide.callback($slide, this.setup.settings, slide.identifier);
    }
  }

  /**
   * Create the progress bar within the modal footer
   *
   * @private
   */
  private addProgressBar(): void {
    let realSlideCount = this.setup.$carousel.find('.carousel-item').length;
    let slideCount = Math.max(1, realSlideCount);
    let initialStep;
    let $modal = this.setup.$carousel.closest('.modal');
    let $modalFooter = $modal.find('.modal-footer');

    initialStep = Math.round(100 / slideCount);

    this.setup.$carousel
      .data('initialStep', initialStep)
      .data('slideCount', slideCount)
      .data('realSlideCount', realSlideCount)
      .data('currentIndex', 0)
      .data('currentSlide', 1);

    // Append progress bar to modal footer
    if (slideCount > 1) {
      $modalFooter.prepend($('<div />', {class: 'progress'}));
      for (let i = 0; i < this.setup.slides.length; ++i) {
        let classes;
        if (i === 0) {
          classes = 'progress-bar first-step';
        } else if (i === this.setup.$carousel.data('slideCount') - 1) {
          classes = 'progress-bar last-step inactive';
        } else {
          classes = 'progress-bar step inactive';
        }
        $modalFooter.find('.progress')
          .append(
            $('<div />', {
              role: 'progressbar',
              class: classes,
              'aria-valuemin': 0,
              'aria-valuenow': initialStep,
              'aria-valuemax': 100,
            })
              .width(initialStep + '%')
              .text(this.getProgressBarTitle(i))
          );
      }
    }
  }

  /**
   * Wrap all the buttons of modal footer
   *
   * @private
   */
  private addButtonContainer(): void {
    let $modal = this.setup.$carousel.closest('.modal');
    let $modalFooterButtons = $modal.find('.modal-footer .btn');

    $modalFooterButtons.wrapAll('<div class="modal-btn-group" />');
  }

  /**
   * Generate slides of carousel
   *
   * @returns {JQuery}
   * @private
   */
  private generateSlides(): JQuery {
    // Check whether the slides were already generated
    if (this.setup.$carousel !== null) {
      return this.setup.$carousel;
    }

    let slides = '<div class="carousel slide" data-bs-ride="carousel" data-bs-interval="false">'
      + '<div class="carousel-inner" role="listbox">';

    for (let i = 0; i < this.setup.slides.length; ++i) {
      let currentSlide: Slide = this.setup.slides[i];
      let slideContent = currentSlide.content;

      if (typeof slideContent === 'object') {
        slideContent = slideContent.html();
      }
      slides += '<div class="carousel-item" data-bs-slide="' + currentSlide.identifier + '" data-step="' + i + '">' + slideContent + '</div>';
    }

    slides += '</div></div>';

    this.setup.$carousel = $(slides);
    this.setup.$carousel.find('.carousel-item').first().addClass('active');

    return this.setup.$carousel;
  }
}

let multistepWizardObject;
try {
  // fetch from opening window
  if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.MultiStepWizard) {
    multistepWizardObject = window.opener.TYPO3.MultiStepWizard;
  }

  // fetch from parent
  if (parent && parent.window.TYPO3 && parent.window.TYPO3.MultiStepWizard) {
    multistepWizardObject = parent.window.TYPO3.MultiStepWizard;
  }

  // fetch object from outer frame
  if (top && top.TYPO3 && top.TYPO3.MultiStepWizard) {
    multistepWizardObject = top.TYPO3.MultiStepWizard;
  }
} catch (e) {
  // This only happens if the opener, parent or top is some other url (eg a local file)
  // which loaded the current window. Then the browser's cross domain policy jumps in
  // and raises an exception.
  // For this case we are safe and we can create our global object below.
}

if (!multistepWizardObject) {
  multistepWizardObject = new MultiStepWizard();

  // attach to global frame
  if (typeof TYPO3 !== 'undefined') {
    TYPO3.MultiStepWizard = multistepWizardObject;
  }
}

export default multistepWizardObject;
