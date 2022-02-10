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

import $ from 'jquery';
import 'jquery-ui/draggable';
import 'jquery-ui/resizable';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Cropper from 'cropperjs';
import ImagesLoaded from 'imagesloaded';
import Icons from './icons';
import Modal from './modal';

interface Area {
  x: number;
  y: number;
  height: number;
  width: number;
}

interface Ratio {
  id: string;
  title: string;
  value: number;
}

interface CropVariant {
  title: string;
  id: string;
  selectedRatio: string;
  cropArea?: Area;
  focusArea?: Area;
  coverAreas?: Area[];
  allowedAspectRatios: {[key: string]: Ratio};
}

interface Offset {
  left: number;
  top: number;
}

interface CropperEvent extends CustomEvent {
  detail: Cropper.Data;
}

/**
 * Module: @typo3/backend/image-manipulation
 * Contains all logic for the image crop GUI including setting focusAreas
 * @exports @typo3/backend/image-manipulation
 */
class ImageManipulation {
  private initialized: boolean = false;
  private trigger: JQuery;
  private currentModal: JQuery;
  private cropVariantTriggers: JQuery;
  private activeCropVariantTrigger: JQuery;
  private saveButton: JQuery;
  private previewButton: JQuery;
  private dismissButton: JQuery;
  private resetButton: JQuery;
  private aspectRatioTrigger: JQuery;
  private cropInfo: JQuery;
  private cropImageContainerSelector: string = '#t3js-crop-image-container';
  private imageOriginalSizeFactor: number;
  private cropImageSelector: string = '#t3js-crop-image';
  private coverAreaSelector: string = '.t3js-cropper-cover-area';
  private cropInfoSelector: string = '.t3js-cropper-info-crop';
  private focusAreaSelector: string = '#t3js-cropper-focus-area';
  private focusArea: any;
  private cropBox: JQuery;
  private cropper: Cropper;
  private currentCropVariant: CropVariant;
  private data: any;
  private defaultFocusArea: Area = {
    height: 1 / 3,
    width: 1 / 3,
    x: 0,
    y: 0,
  };
  private defaultOpts: Cropper.Options = {
    autoCrop: true,
    autoCropArea: 0.7,
    dragMode: 'crop',
    guides: true,
    responsive: true,
    viewMode: 1,
    zoomable: false,
    checkCrossOrigin: false,
  };

  /**
   * @method isCropAreaEmpty
   * @desc Checks if an area is set or pristine
   * @param {Area} area - The area to check
   * @return {boolean}
   * @static
   */
  public static isEmptyArea(area: Area): boolean {
    return $.isEmptyObject(area);
  }

  /**
   * @method wait
   * @desc window.setTimeout shim
   * @param {Function} fn - The function to execute
   * @param {number} ms - The time in [ms] to wait until execution
   * @return {boolean}
   * @public
   * @static
   */
  public static wait(fn: () => void, ms: number): void {
    window.setTimeout(fn, ms);
  }

  /**
   * @method toCssPercent
   * @desc Takes a number, and converts it to CSS percentage length
   * @param {number} num - The number to convert
   * @return {string}
   * @public
   * @static
   */
  public static toCssPercent(num: number): string {
    return `${num * 100}%`;
  }

  /**
   * @method serializeCropVariants
   * @desc Serializes crop variants for persistence or preview
   * @param {Object} cropVariants
   * @returns string
   */
  private static serializeCropVariants(cropVariants: object): string {
    const omitUnused: any = (key: any, value: any) => {
      return (
        key === 'id'
        || key === 'title'
        || key === 'allowedAspectRatios'
        || key === 'coverAreas'
      ) ? undefined : value;
    };

    return JSON.stringify(cropVariants, omitUnused);
  }

  /**
   * @method initializeTrigger
   * @desc Assign a handler to .t3js-image-manipulation-trigger.
   *       Show the modal and kick-off image manipulation
   * @public
   */
  public initializeTrigger(): void {
    const triggerHandler = (e: JQueryEventObject): void => {
      e.preventDefault();
      this.trigger = $(e.currentTarget);
      this.show();
    };
    $('.t3js-image-manipulation-trigger').off('click').on('click', triggerHandler);
  }

  /**
   * @method initializeCropperModal
   * @desc Initialize the cropper modal and dispatch the cropper init
   * @private
   */
  private initializeCropperModal(): void {
    const image: JQuery = this.currentModal.find(this.cropImageSelector);
    ImagesLoaded(image.get(0), (): void => {
      this.init();
    });
  }

  /**
   * @method show
   * @desc Load the image and setup the modal UI
   * @private
   */
  private show(): void {
    const modalTitle: string = this.trigger.data('modalTitle');
    const buttonPreviewText: string = this.trigger.data('buttonPreviewText');
    const buttonDismissText: string = this.trigger.data('buttonDismissText');
    const buttonSaveText: string = this.trigger.data('buttonSaveText');
    const imageUri: string = this.trigger.data('url');
    const payload: object = this.trigger.data('payload');

    Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).then((icon: string): void => {
      /**
       * Open modal with image to crop
       */
      this.currentModal = Modal.advanced({
        additionalCssClasses: ['modal-image-manipulation'],
        buttons: [
          {
            btnClass: 'btn-default pull-left',
            dataAttributes: {
              method: 'preview',
            },
            icon: 'actions-view',
            text: buttonPreviewText,
          },
          {
            btnClass: 'btn-default',
            dataAttributes: {
              method: 'dismiss',
            },
            icon: 'actions-close',
            text: buttonDismissText,
          },
          {
            btnClass: 'btn-primary',
            dataAttributes: {
              method: 'save',
            },
            icon: 'actions-document-save',
            text: buttonSaveText,
          },
        ],
        content: $('<div class="modal-loading">').append(icon),
        size: Modal.sizes.full,
        style: Modal.styles.dark,
        title: modalTitle,
      });

      this.currentModal.on('shown.bs.modal', (): void => {
        new AjaxRequest(imageUri).post(payload).then(async (response: AjaxResponse): Promise<void> => {
          this.currentModal.find('.t3js-modal-body').append(await response.resolve()).addClass('cropper');
          this.currentModal.find('.modal-loading').remove();
          this.initializeCropperModal();
        });
      });

      this.currentModal.on('hide.bs.modal', (): void => {
        this.destroy();
      });
      // do not dismiss the modal when clicking beside it to avoid data loss
      this.currentModal.css('pointer-events', 'none');
    });
  }

  /**
   * @method init
   * @desc Initializes the cropper UI and sets up all the event bindings for the UI
   * @private
   */
  private init(): void {
    const image: JQuery = this.currentModal.find(this.cropImageSelector);
    const data: string = this.trigger.attr('data-crop-variants');

    if (!data) {
      throw new TypeError('ImageManipulation: No cropVariants data found for image');
    }

    // if we have data already set we assume an internal reinit eg. after resizing
    this.data = $.isEmptyObject(this.data) ? JSON.parse(data) : this.data;

    this.cropVariantTriggers = this.currentModal.find('.t3js-crop-variant-trigger');
    this.activeCropVariantTrigger = this.currentModal.find('.t3js-crop-variant-trigger.is-active');
    this.cropInfo = this.currentModal.find(this.cropInfoSelector);
    this.saveButton = this.currentModal.find('[data-method=save]');
    this.previewButton = this.currentModal.find('[data-method=preview]');
    this.dismissButton = this.currentModal.find('[data-method=dismiss]');
    this.resetButton = this.currentModal.find('[data-method=reset]');
    this.aspectRatioTrigger = this.currentModal.find('[data-method=setAspectRatio]');
    this.currentCropVariant = this.data[this.activeCropVariantTrigger.attr('data-crop-variant-id')];

    /**
     * Assign EventListener to cropVariantTriggers
     */
    this.cropVariantTriggers.off('click').on('click', (e: JQueryEventObject): void => {

      /**
       * Is the current cropVariantTrigger is active, bail out.
       * Bootstrap doesn't provide this functionality when collapsing the Collapse panels
       */
      if ($(e.currentTarget).hasClass('is-active')) {
        e.stopPropagation();
        e.preventDefault();
        return;
      }

      this.activeCropVariantTrigger.removeClass('is-active');
      $(e.currentTarget).addClass('is-active');
      this.activeCropVariantTrigger = $(e.currentTarget);
      const cropVariant: CropVariant = this.data[this.activeCropVariantTrigger.attr('data-crop-variant-id')];
      const imageData: Cropper.ImageData = this.cropper.getImageData();
      cropVariant.cropArea = this.convertRelativeToAbsoluteCropArea(cropVariant.cropArea, imageData);
      this.currentCropVariant = $.extend(true, {}, cropVariant);
      this.update(cropVariant);
    });

    /**
     * Assign EventListener to aspectRatioTrigger
     */
    this.aspectRatioTrigger.off('click').on('click', (e: JQueryEventObject): void => {
      const ratioId: string = $(e.currentTarget).attr('data-bs-option');
      const temp: CropVariant = $.extend(true, {}, this.currentCropVariant);
      const ratio: Ratio = temp.allowedAspectRatios[ratioId];
      this.setAspectRatio(ratio);
      // set data explicitly or setAspectRatio upscales the crop
      this.setCropArea(temp.cropArea);
      this.currentCropVariant = $.extend(true, {}, temp, {selectedRatio: ratioId});
      this.update(this.currentCropVariant);
    });

    /**
     * Assign EventListener to saveButton
     */
    this.saveButton.off('click').on('click', (): void => {
      this.save(this.data);
    });

    /**
     * Assign EventListener to previewButton if preview url exists
     */
    if (this.trigger.attr('data-preview-url')) {
      this.previewButton.off('click').on('click', (): void => {
        this.openPreview(this.data);
      });
    } else {
      this.previewButton.hide();
    }

    /**
     * Assign EventListener to dismissButton
     */
    this.dismissButton.off('click').on('click', (): void => {
      this.currentModal.modal('hide');
    });

    /**
     * Assign EventListener to resetButton
     */
    this.resetButton.off('click').on('click', (e: JQueryEventObject): void => {
      const imageData: Cropper.ImageData = this.cropper.getImageData();
      const resetCropVariantString: string = $(e.currentTarget).attr('data-crop-variant');
      e.preventDefault();
      e.stopPropagation();
      if (!resetCropVariantString) {
        throw new TypeError('TYPO3 Cropper: No cropVariant data attribute found on reset element.');
      }
      const resetCropVariant: CropVariant = JSON.parse(resetCropVariantString);
      const absoluteCropArea: Area = this.convertRelativeToAbsoluteCropArea(resetCropVariant.cropArea, imageData);
      this.currentCropVariant = $.extend(true, {}, resetCropVariant, {cropArea: absoluteCropArea});
      this.update(this.currentCropVariant);
    });

    // if we start without an cropArea, maximize the cropper
    if (ImageManipulation.isEmptyArea(this.currentCropVariant.cropArea)) {
      this.defaultOpts = $.extend({
        autoCropArea: 1,
      }, this.defaultOpts);
    }

    /**
     * Initialise the cropper
     */
    this.cropper = new Cropper((image.get(0) as HTMLImageElement), $.extend(this.defaultOpts, {
      ready: this.cropBuiltHandler,
      crop: this.cropMoveHandler,
      cropend: this.cropEndHandler,
      cropstart: this.cropStartHandler,
      data: this.currentCropVariant.cropArea,
    }));

    // Initialize current crop variant
    this.update(this.currentCropVariant);
  }

  /**
   * @method cropBuiltHandler
   * @desc Internal cropper handler. Called when the cropper has been instantiated
   * @private
   */
  private cropBuiltHandler = (): void => {
    this.initialized = true;

    const imageData: Cropper.ImageData = this.cropper.getImageData();
    const image: JQuery = this.currentModal.find(this.cropImageSelector);

    // Make the image in the backdrop visible again.
    // TODO: Check why this doesn't happen automatically.
    this.currentModal.find('.cropper-canvas img').removeClass('cropper-hide');

    this.imageOriginalSizeFactor = image.data('originalWidth') / imageData.naturalWidth;

    // iterate over the crop variants and set up their respective preview
    this.cropVariantTriggers.each((index: number, elem: Element): void => {
      const cropVariantId: string = $(elem).attr('data-crop-variant-id');
      const cropArea: Area = this.convertRelativeToAbsoluteCropArea(
        this.data[cropVariantId].cropArea,
        imageData,
      );
      const variant: CropVariant = $.extend(true, {}, this.data[cropVariantId], {cropArea});
      this.updatePreviewThumbnail(variant, $(elem));
    });

    this.currentCropVariant.cropArea = this.convertRelativeToAbsoluteCropArea(
      this.currentCropVariant.cropArea,
      imageData,
    );
    // can't use .t3js-* as selector because it is an extraneous selector
    this.cropBox = this.currentModal.find('.cropper-crop-box');

    this.setCropArea(this.currentCropVariant.cropArea);

    // check if new cropVariant has coverAreas
    if (this.currentCropVariant.coverAreas) {
      // init or reinit focusArea
      this.initCoverAreas(this.cropBox, this.currentCropVariant.coverAreas);
    }
    // check if new cropVariant has focusArea
    if (this.currentCropVariant.focusArea) {
      // init or reinit focusArea
      if (ImageManipulation.isEmptyArea(this.currentCropVariant.focusArea)) {
        // if an empty focusArea is set initialise it with the default
        this.currentCropVariant.focusArea = $.extend(true, {}, this.defaultFocusArea);
      }
      this.initFocusArea(this.cropBox);
      this.scaleAndMoveFocusArea(this.currentCropVariant.focusArea);
    }

    if (this.currentCropVariant.selectedRatio) {
      // set data explicitly or setAspectRatio up-scales the crop
      this.currentModal.find(`[data-bs-option='${this.currentCropVariant.selectedRatio}']`).addClass('active');
    }
  }

  /**
   * @method cropMoveHandler
   * @desc Internal cropper handler. Called when the cropping area is moving
   * @private
   */
  private cropMoveHandler = (e: CropperEvent): void => {
    if (!this.initialized) {
      return;
    }

    this.currentCropVariant.cropArea = $.extend(true, this.currentCropVariant.cropArea, {
      height: Math.floor(e.detail.height),
      width: Math.floor(e.detail.width),
      x: Math.floor(e.detail.x),
      y: Math.floor(e.detail.y),
    });
    this.updatePreviewThumbnail(this.currentCropVariant, this.activeCropVariantTrigger);
    this.updateCropVariantData(this.currentCropVariant);
    const naturalWidth: number = Math.round(this.currentCropVariant.cropArea.width * this.imageOriginalSizeFactor);
    const naturalHeight: number = Math.round(this.currentCropVariant.cropArea.height * this.imageOriginalSizeFactor);
    this.cropInfo.text(`${naturalWidth}×${naturalHeight} px`);
  }

  /**
   * @method cropStartHandler
   * @desc Internal cropper handler. Called when the cropping starts moving
   * @private
   */
  private cropStartHandler = (): void => {
    if (this.currentCropVariant.focusArea) {
      this.focusArea.draggable('option', 'disabled', true);
      this.focusArea.resizable('option', 'disabled', true);
    }
  }

  /**
   * @method cropEndHandler
   * @desc Internal cropper handler. Called when the cropping ends moving
   * @private
   */
  private cropEndHandler = (): void => {
    if (this.currentCropVariant.focusArea) {
      this.focusArea.draggable('option', 'disabled', false);
      this.focusArea.resizable('option', 'disabled', false);
    }
  }

  /**
   * @method update
   * @desc Update current cropArea position and size when changing cropVariants
   * @param {CropVariant} cropVariant - The new cropVariant to update the UI with
   */
  private update(cropVariant: CropVariant): void {
    const temp: CropVariant = $.extend(true, {}, cropVariant);
    const selectedRatio: Ratio = cropVariant.allowedAspectRatios[cropVariant.selectedRatio];
    this.currentModal.find('[data-bs-option]').removeClass('active');
    this.currentModal.find(`[data-bs-option="${cropVariant.selectedRatio}"]`).addClass('active');
    /**
     * Setting the aspect ratio cause a redraw of the crop area so we need to manually reset it to last data
     */
    this.setAspectRatio(selectedRatio);
    this.setCropArea(temp.cropArea);
    this.currentCropVariant = $.extend(true, {}, temp, cropVariant);
    this.cropBox.find(this.coverAreaSelector).remove();

    // if the current container has a focus area element, deregister and cleanup prior to initialization
    if (this.cropBox.has(this.focusAreaSelector).length) {
      this.focusArea.resizable('destroy').draggable('destroy');
      this.focusArea.remove();
    }

    // check if new cropVariant has focusArea
    if (cropVariant.focusArea) {
      // init or reinit focusArea
      if (ImageManipulation.isEmptyArea(cropVariant.focusArea)) {
        this.currentCropVariant.focusArea = $.extend(true, {}, this.defaultFocusArea);
      }
      this.initFocusArea(this.cropBox);
      this.scaleAndMoveFocusArea(this.currentCropVariant.focusArea);
    }

    // check if new cropVariant has coverAreas
    if (cropVariant.coverAreas) {
      // init or reinit focusArea
      this.initCoverAreas(this.cropBox, this.currentCropVariant.coverAreas);
    }
    this.updatePreviewThumbnail(this.currentCropVariant, this.activeCropVariantTrigger);
  }

  /**
   * @method initFocusArea
   * @desc Initializes the focus area inside a container and registers the resizable and draggable interfaces to it
   * @param {JQuery} container
   * @private
   */
  private initFocusArea(container: JQuery): void {
    this.focusArea = $('<div id="t3js-cropper-focus-area" class="cropper-focus-area"></div>');
    container.append(this.focusArea);
    this.focusArea
      .draggable({
        containment: container,
        create: (): void => {
          /**
           * To make the focusarea draggable, cropper must be disabled by register the same events as cropper does.
           *  Copied from https://github.com/fengyuanchen/cropperjs/blob/main/src/js/constants.js
           */
          const IS_BROWSER = typeof window !== 'undefined' && typeof window.document !== 'undefined';
          const IS_TOUCH_DEVICE = IS_BROWSER && window.document.documentElement ? 'ontouchstart' in window.document.documentElement : false;
          const HAS_POINTER_EVENT = IS_BROWSER ? 'PointerEvent' in window : false;
          const EVENT_TOUCH_START = IS_TOUCH_DEVICE ? 'touchstart' : 'mousedown';
          const EVENT_TOUCH_END = IS_TOUCH_DEVICE ? 'touchend touchcancel' : 'mouseup';
          const EVENT_POINTER_DOWN = HAS_POINTER_EVENT ? 'pointerdown' : EVENT_TOUCH_START;
          const EVENT_POINTER_UP = HAS_POINTER_EVENT ? 'pointerup pointercancel' : EVENT_TOUCH_END;

          this.focusArea.on(EVENT_POINTER_DOWN, (): void => {
            this.cropper.disable();
          });
          this.focusArea.on(EVENT_POINTER_UP, (): void => {
            this.cropper.enable();
          });

          this.scaleAndMoveFocusArea(this.currentCropVariant.focusArea);
        },
        drag: (): void => {
          const {left, top}: Offset = container.offset();
          const {left: fLeft, top: fTop}: Offset = this.focusArea.offset();
          const {focusArea, coverAreas}: {focusArea?: Area, coverAreas?: Area[]} = this.currentCropVariant;

          focusArea.x = (fLeft - left) / container.width();
          focusArea.y = (fTop - top) / container.height();
          this.updatePreviewThumbnail(this.currentCropVariant, this.activeCropVariantTrigger);
          if (this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
            this.focusArea.addClass('has-nodrop');
          } else {
            this.focusArea.removeClass('has-nodrop');
          }
        },
        revert: (): boolean => {
          const revertDelay = 250;
          const {left, top}: Offset = container.offset();
          const {left: fLeft, top: fTop}: Offset = this.focusArea.offset();
          const {focusArea, coverAreas}: {focusArea?: Area, coverAreas?: Area[]} = this.currentCropVariant;

          if (this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
            this.focusArea.removeClass('has-nodrop');
            ImageManipulation.wait((): void => {
              focusArea.x = (fLeft - left) / container.width();
              focusArea.y = (fTop - top) / container.height();
              this.updateCropVariantData(this.currentCropVariant);
            }, revertDelay);
            return true;
          }
          return false;
        },
        revertDuration: 200,
        stop: (): void => {
          const {left, top}: Offset = container.offset();
          const {left: fLeft, top: fTop}: Offset = this.focusArea.offset();
          const {focusArea}: {focusArea?: Area} = this.currentCropVariant;

          focusArea.x = (fLeft - left) / container.width();
          focusArea.y = (fTop - top) / container.height();

          this.scaleAndMoveFocusArea(focusArea);
        },
      })
      .resizable({
        containment: container,
        handles: 'all',
        resize: (): void => {
          const {left, top}: Offset = container.offset();
          const {left: fLeft, top: fTop}: Offset = this.focusArea.offset();
          const {focusArea, coverAreas}: {focusArea?: Area, coverAreas?: Area[]} = this.currentCropVariant;

          focusArea.height = this.focusArea.height() / container.height();
          focusArea.width = this.focusArea.width() / container.width();
          focusArea.x = (fLeft - left) / container.width();
          focusArea.y = (fTop - top) / container.height();
          this.updatePreviewThumbnail(this.currentCropVariant, this.activeCropVariantTrigger);

          if (this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
            this.focusArea.addClass('has-nodrop');
          } else {
            this.focusArea.removeClass('has-nodrop');
          }

        },
        stop: (event: any, ui: any): void => {
          const revertDelay = 250;
          const {left, top}: Offset = container.offset();
          const {left: fLeft, top: fTop}: Offset = this.focusArea.offset();
          const {focusArea, coverAreas}: {focusArea?: Area, coverAreas?: Area[]} = this.currentCropVariant;

          if (this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
            ui.element.animate($.extend(ui.originalPosition, ui.originalSize), revertDelay, (): void => {

              focusArea.height = this.focusArea.height() / container.height();
              focusArea.width = this.focusArea.width() / container.width();
              focusArea.x = (fLeft - left) / container.width();
              focusArea.y = (fTop - top) / container.height();

              this.scaleAndMoveFocusArea(focusArea);
              this.focusArea.removeClass('has-nodrop');
            });
          } else {
            this.scaleAndMoveFocusArea(focusArea);
          }
        },
      });
  }

  /**
   * @method initCoverAreas
   * @desc Initialise cover areas inside the cropper container
   * @param {JQuery} container - The container element to append the cover areas
   * @param {Array<Area>} coverAreas - An array of areas to construct the cover area elements from
   */
  private initCoverAreas(container: JQuery, coverAreas: Area[]): void {
    coverAreas.forEach((coverArea: Area): void => {
      const coverAreaCanvas: JQuery = $('<div class="cropper-cover-area t3js-cropper-cover-area"></div>');
      container.append(coverAreaCanvas);
      coverAreaCanvas.css({
        height: ImageManipulation.toCssPercent(coverArea.height),
        left: ImageManipulation.toCssPercent(coverArea.x),
        top: ImageManipulation.toCssPercent(coverArea.y),
        width: ImageManipulation.toCssPercent(coverArea.width),
      });
    });
  }

  /**
   * @method updatePreviewThumbnail
   * @desc Sync the cropping (and focus area) to the preview thumbnail
   * @param {CropVariant} cropVariant - The crop variant to preview in the thumbnail
   * @param {JQuery} cropVariantTrigger - The crop variant element containing the thumbnail
   * @private
   */
  private updatePreviewThumbnail(cropVariant: CropVariant, cropVariantTrigger: JQuery): void {
    let styles: any;
    const cropperPreviewThumbnailCrop: JQuery =
      cropVariantTrigger.find('.t3js-cropper-preview-thumbnail-crop-area');
    const cropperPreviewThumbnailImage: JQuery =
      cropVariantTrigger.find('.t3js-cropper-preview-thumbnail-crop-image');
    const cropperPreviewThumbnailFocus: JQuery =
      cropVariantTrigger.find('.t3js-cropper-preview-thumbnail-focus-area');
    const imageData: Cropper.ImageData = this.cropper.getImageData();

    // update the position/dimension of the crop area in the preview
    cropperPreviewThumbnailCrop.css({
      height: ImageManipulation.toCssPercent(cropVariant.cropArea.height / imageData.naturalHeight),
      left: ImageManipulation.toCssPercent(cropVariant.cropArea.x / imageData.naturalWidth),
      top: ImageManipulation.toCssPercent(cropVariant.cropArea.y / imageData.naturalHeight),
      width: ImageManipulation.toCssPercent(cropVariant.cropArea.width / imageData.naturalWidth),
    });

    // show and update focusArea in the preview only if we really have one configured
    if (cropVariant.focusArea) {
      cropperPreviewThumbnailFocus.css({
        height: ImageManipulation.toCssPercent(cropVariant.focusArea.height),
        left: ImageManipulation.toCssPercent(cropVariant.focusArea.x),
        top: ImageManipulation.toCssPercent(cropVariant.focusArea.y),
        width: ImageManipulation.toCssPercent(cropVariant.focusArea.width),
      });
    }

    // destruct the preview container's CSS properties
    styles = cropperPreviewThumbnailCrop.css([
      'width', 'height', 'left', 'top',
    ]);

    /**
     * Apply negative margins on the previewThumbnailImage to make the illusion of an offset
     */
    cropperPreviewThumbnailImage.css({
      height: `${parseFloat(styles.height) * (1 / (cropVariant.cropArea.height / imageData.naturalHeight))}px`,
      margin: `${-1 * parseFloat(styles.left)}px`,
      marginTop: `${-1 * parseFloat(styles.top)}px`,
      width: `${parseFloat(styles.width) * (1 / (cropVariant.cropArea.width / imageData.naturalWidth))}px`,
    });
  }

  /**
   * @method scaleAndMoveFocusArea
   * @desc Calculation logic for moving the focus area given the
   *       specified constrains of a crop and an optional cover area
   * @param {Area} focusArea - The translation data
   */
  private scaleAndMoveFocusArea(focusArea: Area): void {
    this.focusArea.css({
      height: ImageManipulation.toCssPercent(focusArea.height),
      left: ImageManipulation.toCssPercent(focusArea.x),
      top: ImageManipulation.toCssPercent(focusArea.y),
      width: ImageManipulation.toCssPercent(focusArea.width),
    });
    this.currentCropVariant.focusArea = focusArea;
    this.updatePreviewThumbnail(this.currentCropVariant, this.activeCropVariantTrigger);
    this.updateCropVariantData(this.currentCropVariant);
  }

  /**
   * @method updateCropVariantData
   * @desc Immutably updates the currently selected cropVariant data
   * @param {CropVariant} currentCropVariant - The cropVariant to immutably save
   * @private
   */
  private updateCropVariantData(currentCropVariant: CropVariant): void {
    const imageData: Cropper.ImageData = this.cropper.getImageData();
    const absoluteCropArea: Area = this.convertAbsoluteToRelativeCropArea(currentCropVariant.cropArea, imageData);
    this.data[currentCropVariant.id] = $.extend(true, {}, currentCropVariant, {cropArea: absoluteCropArea});
  }

  /**
   * @method setAspectRatio
   * @desc Sets the cropper to a specific ratio
   * @param {ratio} ratio - The ratio value to apply
   * @private
   */
  private setAspectRatio(ratio: Ratio): void {
    this.cropper.setAspectRatio(ratio.value);
  }

  /**
   * @method setCropArea
   * @desc Sets the cropper to a specific crop area
   * @param {cropArea} cropArea - The crop area to apply
   * @private
   */
  private setCropArea(cropArea: Area): void {
    const currentRatio: Ratio = this.currentCropVariant.allowedAspectRatios[this.currentCropVariant.selectedRatio];
    if (currentRatio.value === 0) {
      this.cropper.setData({
        height: cropArea.height,
        width: cropArea.width,
        x: cropArea.x,
        y: cropArea.y,
      });
    } else {
      this.cropper.setData({
        height: cropArea.height,
        width: cropArea.height * currentRatio.value,
        x: cropArea.x,
        y: cropArea.y,
      });
    }
  }

  /**
   * @method checkFocusAndCoverAreas
   * @desc Checks is one focus area and one or more cover areas overlap
   * @param focusArea
   * @param coverAreas
   * @return {boolean}
   */
  private checkFocusAndCoverAreasCollision(focusArea: Area, coverAreas: Area[]): boolean {
    if (!coverAreas) {
      return false;
    }
    return coverAreas
      .some((coverArea: Area): boolean => {
        // noinspection OverlyComplexBooleanExpressionJS
        return (focusArea.x < coverArea.x + coverArea.width &&
           focusArea.x + focusArea.width > coverArea.x &&
            focusArea.y < coverArea.y + coverArea.height &&
           focusArea.height + focusArea.y > coverArea.y);
      });
  }

  /**
   * @method convertAbsoluteToRelativeCropArea
   * @desc Converts a crop area from absolute pixel-based into relative length values
   * @param {Area} cropArea - The crop area to convert from
   * @param {Cropper.ImageData} imageData - The image data
   * @return {Area}
   */
  private convertAbsoluteToRelativeCropArea(cropArea: Area, imageData: Cropper.ImageData): Area {
    const {height, width, x, y}: Area = cropArea;
    return {
      height: height / imageData.naturalHeight,
      width: width / imageData.naturalWidth,
      x: x / imageData.naturalWidth,
      y: y / imageData.naturalHeight,
    };
  }

  /**
   * @method convertRelativeToAbsoluteCropArea
   * @desc Converts a crop area from relative into absolute pixel-based length values
   * @param {Area} cropArea - The crop area to convert from
   * @param {Cropper.ImageData} imageData - The image data
   * @return {{height: number, width: number, x: number, y: number}}
   */
  private convertRelativeToAbsoluteCropArea(cropArea: Area, imageData: Cropper.ImageData): Area {
    const {height, width, x, y}: Area = cropArea;
    return {
      height: height * imageData.naturalHeight,
      width: width * imageData.naturalWidth,
      x: x * imageData.naturalWidth,
      y: y * imageData.naturalHeight,
    };
  }

  /**
   * @method setPreviewImages
   * @desc Updates the preview images in the editing section with the respective crop variants
   * @param {Object} data - The internal crop variants state
   */
  private setPreviewImages(data: {[key: string]: CropVariant}): void {
    // @ts-ignore .image is not declared
    const image: HTMLImageElement = this.cropper.image;
    const imageData: Cropper.ImageData = this.cropper.getImageData();

    // iterate over the crop variants and set up their respective preview
    Object.keys(data).forEach((cropVariantId: string) => {
      const cropVariant: CropVariant = data[cropVariantId];
      const cropData: Area = this.convertRelativeToAbsoluteCropArea(cropVariant.cropArea, imageData);

      const $preview: JQuery = this.trigger
        .closest('.form-group')
        .find(`.t3js-image-manipulation-preview[data-crop-variant-id="${cropVariantId}"]`);
      const $previewSelectedRatio: JQuery = this.trigger
        .closest('.form-group')
        .find(`.t3js-image-manipulation-selected-ratio[data-crop-variant-id="${cropVariantId}"]`); // tslint:disable-line:max-line-length

      if ($preview.length === 0) {
        return;
      }

      let previewWidth: number = $preview.width();
      let previewHeight: number = $preview.data('preview-height');

      // adjust aspect ratio of preview width/height
      const aspectRatio: number = cropData.width / cropData.height;
      const tmpHeight: number = previewWidth / aspectRatio;
      if (tmpHeight > previewHeight) {
        previewWidth = previewHeight * aspectRatio;
      } else {
        previewHeight = tmpHeight;
      }
      // preview should never be up-scaled
      if (previewWidth > cropData.width) {
        previewWidth = cropData.width;
        previewHeight = cropData.height;
      }

      const ratio: number = previewWidth / cropData.width;
      const $viewBox: JQuery = $('<div />').html('<img src="' + image.src + '">');
      const $ratioTitleText: JQuery = this.currentModal.find(`.t3-js-ratio-title[data-ratio-id="${cropVariant.id}${cropVariant.selectedRatio}"]`); // tslint:disable-line:max-line-length
      $previewSelectedRatio.text($ratioTitleText.text());
      $viewBox.addClass('cropper-preview-container');
      $preview.empty().append($viewBox);
      $viewBox.wrap('<span class="thumbnail thumbnail-status"></span>');

      $viewBox.width(previewWidth).height(previewHeight).find('img').css({
        height: imageData.naturalHeight * ratio,
        left: -cropData.x * ratio,
        top: -cropData.y * ratio,
        width: imageData.naturalWidth * ratio,
      });
    });
  }

  /**
   * @method openPreview
   * @desc Opens a preview view with the crop variants
   * @param {object} data - The whole data object containing all the cropVariants
   * @private
   */
  private openPreview(data: object): void {
    const cropVariants: string = ImageManipulation.serializeCropVariants(data);
    let previewUrl: string = this.trigger.attr('data-preview-url');
    previewUrl = previewUrl + '&cropVariants=' + encodeURIComponent(cropVariants);
    window.open(previewUrl, 'TYPO3ImageManipulationPreview');
  }

  /**
   * @method save
   * @desc Saves the edited cropVariants to a hidden field
   * @param {object} data - The whole data object containing all the cropVariants
   * @private
   */
  private save(data: {[key: string]: CropVariant}): void {
    const cropVariants: string = ImageManipulation.serializeCropVariants(data);
    const hiddenField: JQuery = $(`#${this.trigger.attr('data-field')}`);
    this.trigger.attr('data-crop-variants', JSON.stringify(data));
    this.setPreviewImages(data);
    hiddenField.val(cropVariants);
    FormEngineValidation.markFieldAsChanged(hiddenField);
    this.currentModal.modal('hide');
  }

  /**
   * @method destroy
   * @desc Destroy the ImageManipulation including cropper and alike
   * @private
   */
  private destroy(): void {
    if (this.currentModal) {
      if (this.cropper instanceof Cropper) {
        this.cropper.destroy();
      }
      this.initialized = false;
      this.cropper = null;
      this.currentModal = null;
      this.data = null;
    }
  }
}

export default new ImageManipulation();
