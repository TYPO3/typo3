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


interface WizardSettings {
  [key: string]: any;
}

interface WizardSetup {
  slides: Array<any>;
  settings: WizardSettings;
  forceSelection: boolean;
  $carousel: JQuery;
}

interface Slide {
  identifier: string;
  title: string;
  content: string|JQuery;
  severity: SeverityEnum;
  callback?: Function;
}

/**
 * Module: @typo3/backend/wizard
 * @exports @typo3/backend/wizard
 */
class Wizard {
  private setup: WizardSetup;
  private readonly originalSetup: WizardSetup;

  constructor() {
    this.setup = {
      slides: [],
      settings: {},
      forceSelection: true,
      $carousel: null,
    };
    this.originalSetup = $.extend(true, {}, this.setup);
  }

  public set(key: string, value: any): Wizard {
    this.setup.settings[key] = value;
    return this;
  }

  public addSlide(
    identifier: string,
    title: string,
    content: string = '',
    severity: SeverityEnum = SeverityEnum.info,
    callback?: Function,
  ): Wizard {
    const slide: Slide = {
      identifier: identifier,
      title: title,
      content: content,
      severity: severity,
      callback: callback,
    };
    this.setup.slides.push(slide);
    return this;
  }

  public addFinalProcessingSlide(callback?: Function): Promise<void> {
    if (!callback) {
      callback = (): void => {
        this.dismiss();
      };
    }

    return Icons.getIcon('spinner-circle-dark', Icons.sizes.large, null, null).then((markup: string) => {
      let $processingSlide = $('<div />', {class: 'text-center'}).append(markup);
      this.addSlide(
        'final-processing-slide', top.TYPO3.lang['wizard.processing.title'],
        $processingSlide[0].outerHTML,
        Severity.info,
        callback,
      );
    });
  }

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
        btnClass: 'btn-default',
        name: 'cancel',
        trigger: (): void => {
          this.getComponent().trigger('wizard-dismiss');
        },
      }, {
        text: top.TYPO3.lang['wizard.button.next'],
        btnClass: 'btn-' + Severity.getCssClass(firstSlide.severity),
        name: 'next',
      }],
    );

    if (this.setup.forceSelection) {
      this.lockNextStep();
    }

    this.addProgressBar();
    this.initializeEvents();

    this.getComponent().on('wizard-visible', (): void => {
      this.runSlideCallback(firstSlide, this.setup.$carousel.find('.carousel-item').first());
    }).on('wizard-dismissed', (): void => {
      this.setup = $.extend(true, {}, this.originalSetup);
    });
  }

  public getComponent(): JQuery {
    if (this.setup.$carousel === null) {
      this.generateSlides();
    }
    return this.setup.$carousel;
  }

  public dismiss(): void {
    Modal.dismiss();
  }

  public lockNextStep(): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="next"]');
    $button.prop('disabled', true);
    return $button;
  }

  public unlockNextStep(): JQuery {
    let $button = this.setup.$carousel.closest('.modal').find('button[name="next"]');
    $button.prop('disabled', false);
    return $button;
  }

  public setForceSelection(force: boolean): void {
    this.setup.forceSelection = force;
  }

  private initializeEvents(): void {
    let $modal = this.setup.$carousel.closest('.modal');
    let $modalTitle = $modal.find('.modal-title');
    let $modalFooter = $modal.find('.modal-footer');
    let $nextButton = $modalFooter.find('button[name="next"]');

    $nextButton.on('click', (): void => {
      this.setup.$carousel.carousel('next');
    });

    this.setup.$carousel.on('slide.bs.carousel', (): void => {
      let nextSlideNumber = this.setup.$carousel.data('currentSlide') + 1;
      let currentIndex = this.setup.$carousel.data('currentIndex') + 1;

      $modalTitle.text(this.setup.slides[currentIndex].title);

      this.setup.$carousel.data('currentSlide', nextSlideNumber);
      this.setup.$carousel.data('currentIndex', currentIndex);

      if (nextSlideNumber >= this.setup.$carousel.data('realSlideCount')) {
        // Point of no return - hide modal footer disable any closing ability
        $modal.find('.modal-header .close').remove();
        $modalFooter.slideUp();
      } else {
        $modalFooter.find('.progress-bar')
          .width(this.setup.$carousel.data('initialStep') * nextSlideNumber + '%')
          .text(top.TYPO3.lang['wizard.progress']
            .replace('{0}', nextSlideNumber)
            .replace('{1}', this.setup.$carousel.data('slideCount')));
      }

      $nextButton
        .removeClass('btn-' + Severity.getCssClass(this.setup.slides[currentIndex - 1].severity))
        .addClass('btn-' + Severity.getCssClass(this.setup.slides[currentIndex].severity));

      $modal
        .removeClass('modal-severity-' + Severity.getCssClass(this.setup.slides[currentIndex - 1].severity))
        .addClass('modal-severity-' + Severity.getCssClass(this.setup.slides[currentIndex].severity));
    }).on('slid.bs.carousel', (evt: JQueryEventObject): void => {
      let currentIndex = this.setup.$carousel.data('currentIndex');
      let slide = this.setup.slides[currentIndex];

      this.runSlideCallback(slide, $(evt.relatedTarget));

      if (this.setup.forceSelection) {
        this.lockNextStep();
      }
    });

    /**
     * Custom event, closes the wizard
     */
    let cmp = this.getComponent();
    cmp.on('wizard-dismiss', this.dismiss);

    Modal.currentModal.on('hidden.bs.modal', (): void => {
      cmp.trigger('wizard-dismissed');
    }).on('shown.bs.modal', (): void => {
      cmp.trigger('wizard-visible');
    });
  }

  private runSlideCallback(slide: Slide, $slide: JQuery): void {
    if (typeof slide.callback === 'function') {
      slide.callback($slide, this.setup.settings, slide.identifier);
    }
  }

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
      $modalFooter.prepend(
        $('<div />', {class: 'progress'}).append(
          $('<div />', {
            role: 'progressbar',
            class: 'progress-bar',
            'aria-valuemin': 0,
            'aria-valuenow': initialStep,
            'aria-valuemax': 100,
          }).width(initialStep + '%').text(
            top.TYPO3.lang['wizard.progress']
              .replace('{0}', '1')
              .replace('{1}', slideCount),
          ),
        ),
      );
    }
  }

  private generateSlides(): JQuery {
    // Check whether the slides were already generated
    if (this.setup.$carousel !== null) {
      return this.setup.$carousel;
    }

    let slides = '<div class="carousel slide" data-bs-ride="carousel" data-bs-interval="false">'
      + '<div class="carousel-inner" role="listbox">';

    for (let currentSlide of Object.values(this.setup.slides)) {
      let slideContent = currentSlide.content;

      if (typeof slideContent === 'object') {
        slideContent = slideContent.html();
      }
      slides += '<div class="carousel-item" data-bs-slide="' + currentSlide.identifier + '">' + slideContent + '</div>';
    }

    slides += '</div></div>';

    this.setup.$carousel = $(slides);
    this.setup.$carousel.find('.carousel-item').first().addClass('active');

    return this.setup.$carousel;
  }
}

let wizardObject: any;
try {
  // fetch from opening window
  if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.Wizard) {
    wizardObject = window.opener.TYPO3.Wizard;
  }

  // fetch from parent
  if (parent && parent.window.TYPO3 && parent.window.TYPO3.Wizard) {
    wizardObject = parent.window.TYPO3.Wizard;
  }

  // fetch object from outer frame
  if (top && top.TYPO3 && top.TYPO3.Wizard) {
    wizardObject = top.TYPO3.Wizard;
  }
} catch {
  // This only happens if the opener, parent or top is some other url (eg a local file)
  // which loaded the current window. Then the browser's cross domain policy jumps in
  // and raises an exception.
  // For this case we are safe and we can create our global object below.
}

if (!wizardObject) {
  wizardObject = new Wizard();

  // attach to global frame
  if (typeof TYPO3 !== 'undefined') {
    TYPO3.Wizard = wizardObject;
  }
}

export default wizardObject;
