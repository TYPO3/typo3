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
define(["require", "exports", "TYPO3/CMS/Core/Contrib/imagesloaded.pkgd.min", "TYPO3/CMS/Backend/Modal", "jquery", "jquery-ui/draggable", "jquery-ui/resizable"], function (require, exports, ImagesLoaded, Modal, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/ImageManipulation
     * Contains all logic for the image crop GUI including setting focusAreas
     * @exports TYPO3/CMS/Backend/ImageManipulation
     */
    var ImageManipulation = (function () {
        function ImageManipulation() {
            var _this = this;
            this.cropImageContainerSelector = '#t3js-crop-image-container';
            this.cropImageSelector = '#t3js-crop-image';
            this.coverAreaSelector = '.t3js-cropper-cover-area';
            this.cropInfoSelector = '.t3js-cropper-info-crop';
            this.focusAreaSelector = '#t3js-cropper-focus-area';
            this.defaultFocusArea = {
                height: 1 / 3,
                width: 1 / 3,
                x: 0,
                y: 0,
            };
            this.defaultOpts = {
                autoCrop: true,
                autoCropArea: '0.7',
                dragMode: 'crop',
                guides: true,
                responsive: true,
                viewMode: 1,
                zoomable: false,
            };
            this.resizeTimeout = 450;
            /**
             * @method cropBuiltHandler
             * @desc Internal cropper handler. Called when the cropper has been instantiated
             * @private
             */
            this.cropBuiltHandler = function () {
                var imageData = _this.cropper.cropper('getImageData');
                var image = _this.currentModal.find(_this.cropImageSelector);
                _this.imageOriginalSizeFactor = image.data('originalWidth') / imageData.naturalWidth;
                // Iterate over the crop variants and set up their respective preview
                _this.cropVariantTriggers.each(function (index, elem) {
                    var cropVariantId = $(elem).attr('data-crop-variant-id');
                    var cropArea = _this.convertRelativeToAbsoluteCropArea(_this.data[cropVariantId].cropArea, imageData);
                    var variant = $.extend(true, {}, _this.data[cropVariantId], { cropArea: cropArea });
                    _this.updatePreviewThumbnail(variant, $(elem));
                });
                _this.currentCropVariant.cropArea = _this.convertRelativeToAbsoluteCropArea(_this.currentCropVariant.cropArea, imageData);
                // Can't use .t3js-* as selector because it is an extraneous selector
                _this.cropBox = _this.currentModal.find('.cropper-crop-box');
                _this.setCropArea(_this.currentCropVariant.cropArea);
                // Check if new cropVariant has coverAreas
                if (_this.currentCropVariant.coverAreas) {
                    // Init or reinit focusArea
                    _this.initCoverAreas(_this.cropBox, _this.currentCropVariant.coverAreas);
                }
                // Check if new cropVariant has focusArea
                if (_this.currentCropVariant.focusArea) {
                    // Init or reinit focusArea
                    if (ImageManipulation.isEmptyArea(_this.currentCropVariant.focusArea)) {
                        // If an empty focusArea is set initialise it with the default
                        _this.currentCropVariant.focusArea = $.extend(true, {}, _this.defaultFocusArea);
                    }
                    _this.initFocusArea(_this.cropBox);
                    _this.scaleAndMoveFocusArea(_this.currentCropVariant.focusArea);
                }
                if (_this.currentCropVariant.selectedRatio) {
                    _this.setAspectRatio(_this.currentCropVariant.allowedAspectRatios[_this.currentCropVariant.selectedRatio]);
                    // Set data explicitly or setAspectRatio up-scales the crop
                    _this.setCropArea(_this.currentCropVariant.cropArea);
                    _this.currentModal.find("[data-option='" + _this.currentCropVariant.selectedRatio + "']").addClass('active');
                }
                _this.cropperCanvas.addClass('is-visible');
            };
            /**
             * @method cropMoveHandler
             * @desc Internal cropper handler. Called when the cropping area is moving
             * @private
             */
            this.cropMoveHandler = function (e) {
                _this.currentCropVariant.cropArea = $.extend(true, _this.currentCropVariant.cropArea, {
                    height: Math.floor(e.height),
                    width: Math.floor(e.width),
                    x: Math.floor(e.x),
                    y: Math.floor(e.y),
                });
                _this.updatePreviewThumbnail(_this.currentCropVariant, _this.activeCropVariantTrigger);
                _this.updateCropVariantData(_this.currentCropVariant);
                var naturalWidth = Math.round(_this.currentCropVariant.cropArea.width * _this.imageOriginalSizeFactor);
                var naturalHeight = Math.round(_this.currentCropVariant.cropArea.height * _this.imageOriginalSizeFactor);
                _this.cropInfo.text(naturalWidth + "\u00D7" + naturalHeight + " px");
            };
            /**
             * @method cropStartHandler
             * @desc Internal cropper handler. Called when the cropping starts moving
             * @private
             */
            this.cropStartHandler = function () {
                if (_this.currentCropVariant.focusArea) {
                    _this.focusArea.draggable('option', 'disabled', true);
                    _this.focusArea.resizable('option', 'disabled', true);
                }
            };
            /**
             * @method cropEndHandler
             * @desc Internal cropper handler. Called when the cropping ends moving
             * @private
             */
            this.cropEndHandler = function () {
                if (_this.currentCropVariant.focusArea) {
                    _this.focusArea.draggable('option', 'disabled', false);
                    _this.focusArea.resizable('option', 'disabled', false);
                }
            };
            // Silence is golden
            $(window).resize(function () {
                if (_this.cropper) {
                    _this.cropper.cropper('destroy');
                }
            });
            this.resizeEnd(function () {
                if (_this.cropper) {
                    _this.init();
                }
            });
        }
        /**
         * @method isCropAreaEmpty
         * @desc Checks if an area is set or pristine
         * @param {Area} area - The area to check
         * @return {boolean}
         * @static
         */
        ImageManipulation.isEmptyArea = function (area) {
            return $.isEmptyObject(area);
        };
        /**
         * @method wait
         * @desc window.setTimeout shim
         * @param {Function} fn - The function to execute
         * @param {number} ms - The time in [ms] to wait until execution
         * @return {boolean}
         * @public
         * @static
         */
        ImageManipulation.wait = function (fn, ms) {
            window.setTimeout(fn, ms);
        };
        /**
         * @method toCssPercent
         * @desc Takes a number, and converts it to CSS percentage length
         * @param {number} num - The number to convert
         * @return {string}
         * @public
         * @static
         */
        ImageManipulation.toCssPercent = function (num) {
            return num * 100 + "%";
        };
        /**
         * @method serializeCropVariants
         * @desc Serializes crop variants for persistence or preview
         * @param {Object} cropVariants
         * @returns string
         */
        ImageManipulation.serializeCropVariants = function (cropVariants) {
            var omitUnused = function (key, value) {
                return (key === 'id'
                    || key === 'title'
                    || key === 'allowedAspectRatios'
                    || key === 'coverAreas') ? undefined : value;
            };
            return JSON.stringify(cropVariants, omitUnused);
        };
        /**
         * @method initializeTrigger
         * @desc Assign a handler to .t3js-image-manipulation-trigger.
         *       Show the modal and kick-off image manipulation
         * @public
         */
        ImageManipulation.prototype.initializeTrigger = function () {
            var _this = this;
            var triggerHandler = function (e) {
                e.preventDefault();
                _this.trigger = $(e.currentTarget);
                _this.show();
            };
            $('.t3js-image-manipulation-trigger').off('click').click(triggerHandler);
        };
        /**
         * @method initializeCropperModal
         * @desc Initialize the cropper modal and dispatch the cropper init
         * @private
         */
        ImageManipulation.prototype.initializeCropperModal = function () {
            var _this = this;
            var image = this.currentModal.find(this.cropImageSelector);
            ImagesLoaded(image, function () {
                setTimeout(function () {
                    _this.init();
                }, 100);
            });
        };
        /**
         * @method show
         * @desc Load the image and setup the modal UI
         * @private
         */
        ImageManipulation.prototype.show = function () {
            var _this = this;
            var modalTitle = this.trigger.data('modalTitle');
            var buttonPreviewText = this.trigger.data('buttonPreviewText');
            var buttonDismissText = this.trigger.data('buttonDismissText');
            var buttonSaveText = this.trigger.data('buttonSaveText');
            var imageUri = this.trigger.data('url');
            var initCropperModal = this.initializeCropperModal.bind(this);
            /**
             * Open modal with image to crop
             */
            this.currentModal = Modal.advanced({
                additionalCssClasses: ['modal-image-manipulation'],
                ajaxCallback: initCropperModal,
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
                callback: function (currentModal) {
                    currentModal.find('.t3js-modal-body')
                        .addClass('cropper');
                },
                content: imageUri,
                size: Modal.sizes.full,
                style: Modal.styles.dark,
                title: modalTitle,
                type: 'ajax',
            });
            this.currentModal.on('hide.bs.modal', function (e) {
                _this.destroy();
            });
            // Do not dismiss the modal when clicking beside it to avoid data loss
            this.currentModal.data('bs.modal').options.backdrop = 'static';
        };
        /**
         * @method init
         * @desc Initializes the cropper UI and sets up all the event indings for the UI
         * @private
         */
        ImageManipulation.prototype.init = function () {
            var _this = this;
            var image = this.currentModal.find(this.cropImageSelector);
            var imageHeight = $(image).height();
            var imageWidth = $(image).width();
            var data = this.trigger.attr('data-crop-variants');
            if (!data) {
                throw new TypeError('ImageManipulation: No cropVariants data found for image');
            }
            // If we have data already set we assume an internal reinit eg. after resizing
            this.data = $.isEmptyObject(this.data) ? JSON.parse(data) : this.data;
            // Initialize our class members
            this.currentModal.find(this.cropImageContainerSelector).css({ height: imageHeight, width: imageWidth });
            this.cropVariantTriggers = this.currentModal.find('.t3js-crop-variant-trigger');
            this.activeCropVariantTrigger = this.currentModal.find('.t3js-crop-variant-trigger.is-active');
            this.cropInfo = this.currentModal.find(this.cropInfoSelector);
            this.saveButton = this.currentModal.find('[data-method=save]');
            this.previewButton = this.currentModal.find('[data-method=preview]');
            this.dismissButton = this.currentModal.find('[data-method=dismiss]');
            this.resetButton = this.currentModal.find('[data-method=reset]');
            this.cropperCanvas = this.currentModal.find('#js-crop-canvas');
            this.aspectRatioTrigger = this.currentModal.find('[data-method=setAspectRatio]');
            this.currentCropVariant = this.data[this.activeCropVariantTrigger.attr('data-crop-variant-id')];
            /**
             * Assign EventListener to cropVariantTriggers
             */
            this.cropVariantTriggers.off('click').on('click', function (e) {
                /**
                 * Is the current cropVariantTrigger is active, bail out.
                 * Bootstrap doesn't provide this functionality when collapsing the Collaps panels
                 */
                if ($(e.currentTarget).hasClass('is-active')) {
                    e.stopPropagation();
                    e.preventDefault();
                    return;
                }
                _this.activeCropVariantTrigger.removeClass('is-active');
                $(e.currentTarget).addClass('is-active');
                _this.activeCropVariantTrigger = $(e.currentTarget);
                var cropVariant = _this.data[_this.activeCropVariantTrigger.attr('data-crop-variant-id')];
                var imageData = _this.cropper.cropper('getImageData');
                cropVariant.cropArea = _this.convertRelativeToAbsoluteCropArea(cropVariant.cropArea, imageData);
                _this.currentCropVariant = $.extend(true, {}, cropVariant);
                _this.update(cropVariant);
            });
            /**
             * Assign EventListener to aspectRatioTrigger
             */
            this.aspectRatioTrigger.off('click').on('click', function (e) {
                var ratioId = $(e.currentTarget).attr('data-option');
                var temp = $.extend(true, {}, _this.currentCropVariant);
                var ratio = temp.allowedAspectRatios[ratioId];
                _this.setAspectRatio(ratio);
                // Set data explicitly or setAspectRatio upscales the crop
                _this.setCropArea(temp.cropArea);
                _this.currentCropVariant = $.extend(true, {}, temp, { selectedRatio: ratioId });
                _this.update(_this.currentCropVariant);
            });
            /**
             * Assign EventListener to saveButton
             */
            this.saveButton.off('click').on('click', function () {
                _this.save(_this.data);
            });
            /**
             * Assign EventListener to previewButton if preview url exists
             */
            if (this.trigger.attr('data-preview-url')) {
                this.previewButton.off('click').on('click', function () {
                    _this.openPreview(_this.data);
                });
            }
            else {
                this.previewButton.hide();
            }
            /**
             * Assign EventListener to dismissButton
             */
            this.dismissButton.off('click').on('click', function () {
                _this.currentModal.modal('hide');
            });
            /**
             * Assign EventListener to resetButton
             */
            this.resetButton.off('click').on('click', function (e) {
                var imageData = _this.cropper.cropper('getImageData');
                var resetCropVariantString = $(e.currentTarget).attr('data-crop-variant');
                e.preventDefault();
                e.stopPropagation();
                if (!resetCropVariantString) {
                    throw new TypeError('TYPO3 Cropper: No cropVariant data attribute found on reset element.');
                }
                var resetCropVariant = JSON.parse(resetCropVariantString);
                var absoluteCropArea = _this.convertRelativeToAbsoluteCropArea(resetCropVariant.cropArea, imageData);
                _this.currentCropVariant = $.extend(true, {}, resetCropVariant, { cropArea: absoluteCropArea });
                _this.update(_this.currentCropVariant);
            });
            // If we start without an cropArea, maximize the cropper
            if (ImageManipulation.isEmptyArea(this.currentCropVariant.cropArea)) {
                this.defaultOpts = $.extend({
                    autoCropArea: 1,
                }, this.defaultOpts);
            }
            /**
             * Initialise the cropper
             *
             * Note: We use the extraneous jQuery object here, as CropperJS won't work inside the <iframe>
             * The top.require is now inlined @see ImageManipulationElemen.php:143
             * TODO: Find a better solution for cross iframe communications
             */
            this.cropper = top.TYPO3.jQuery(image).cropper($.extend(this.defaultOpts, {
                built: this.cropBuiltHandler,
                crop: this.cropMoveHandler,
                cropend: this.cropEndHandler,
                cropstart: this.cropStartHandler,
                data: this.currentCropVariant.cropArea,
            }));
        };
        /**
         * @method update
         * @desc Update current cropArea position and size when changing cropVariants
         * @param {CropVariant} cropVariant - The new cropVariant to update the UI with
         */
        ImageManipulation.prototype.update = function (cropVariant) {
            var temp = $.extend(true, {}, cropVariant);
            var selectedRatio = cropVariant.allowedAspectRatios[cropVariant.selectedRatio];
            this.currentModal.find('[data-option]').removeClass('active');
            this.currentModal.find("[data-option=\"" + cropVariant.selectedRatio + "\"]").addClass('active');
            /**
             * Setting the aspect ratio cause a redraw of the crop area so we need to manually reset it to last data
             */
            this.setAspectRatio(selectedRatio);
            this.setCropArea(temp.cropArea);
            this.currentCropVariant = $.extend(true, {}, temp, cropVariant);
            this.cropBox.find(this.coverAreaSelector).remove();
            // If the current container has a focus area element, deregister and cleanup prior to initialization
            if (this.cropBox.has(this.focusAreaSelector).length) {
                this.focusArea.resizable('destroy').draggable('destroy');
                this.focusArea.remove();
            }
            // Check if new cropVariant has focusArea
            if (cropVariant.focusArea) {
                // Init or reinit focusArea
                if (ImageManipulation.isEmptyArea(cropVariant.focusArea)) {
                    this.currentCropVariant.focusArea = $.extend(true, {}, this.defaultFocusArea);
                }
                this.initFocusArea(this.cropBox);
                this.scaleAndMoveFocusArea(this.currentCropVariant.focusArea);
            }
            // Check if new cropVariant has coverAreas
            if (cropVariant.coverAreas) {
                // Init or reinit focusArea
                this.initCoverAreas(this.cropBox, this.currentCropVariant.coverAreas);
            }
            this.updatePreviewThumbnail(this.currentCropVariant, this.activeCropVariantTrigger);
        };
        /**
         * @method initFocusArea
         * @desc Initializes the focus area inside a container and registers the resizable and draggable interfaces to it
         * @param {JQuery} container
         * @private
         */
        ImageManipulation.prototype.initFocusArea = function (container) {
            var _this = this;
            this.focusArea = $('<div id="t3js-cropper-focus-area" class="cropper-focus-area"></div>');
            container.append(this.focusArea);
            this.focusArea
                .draggable({
                containment: container,
                create: function () {
                    _this.scaleAndMoveFocusArea(_this.currentCropVariant.focusArea);
                },
                drag: function () {
                    var _a = container.offset(), left = _a.left, top = _a.top;
                    var _b = _this.focusArea.offset(), fLeft = _b.left, fTop = _b.top;
                    var _c = _this.currentCropVariant, focusArea = _c.focusArea, coverAreas = _c.coverAreas;
                    focusArea.x = (fLeft - left) / container.width();
                    focusArea.y = (fTop - top) / container.height();
                    _this.updatePreviewThumbnail(_this.currentCropVariant, _this.activeCropVariantTrigger);
                    if (_this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
                        _this.focusArea.addClass('has-nodrop');
                    }
                    else {
                        _this.focusArea.removeClass('has-nodrop');
                    }
                },
                revert: function () {
                    var revertDelay = 250;
                    var _a = container.offset(), left = _a.left, top = _a.top;
                    var _b = _this.focusArea.offset(), fLeft = _b.left, fTop = _b.top;
                    var _c = _this.currentCropVariant, focusArea = _c.focusArea, coverAreas = _c.coverAreas;
                    if (_this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
                        _this.focusArea.removeClass('has-nodrop');
                        ImageManipulation.wait(function () {
                            focusArea.x = (fLeft - left) / container.width();
                            focusArea.y = (fTop - top) / container.height();
                            _this.updateCropVariantData(_this.currentCropVariant);
                        }, revertDelay);
                        return true;
                    }
                },
                revertDuration: 200,
                stop: function () {
                    var _a = container.offset(), left = _a.left, top = _a.top;
                    var _b = _this.focusArea.offset(), fLeft = _b.left, fTop = _b.top;
                    var focusArea = _this.currentCropVariant.focusArea;
                    focusArea.x = (fLeft - left) / container.width();
                    focusArea.y = (fTop - top) / container.height();
                    _this.scaleAndMoveFocusArea(focusArea);
                },
            })
                .resizable({
                containment: container,
                handles: 'all',
                resize: function () {
                    var _a = container.offset(), left = _a.left, top = _a.top;
                    var _b = _this.focusArea.offset(), fLeft = _b.left, fTop = _b.top;
                    var _c = _this.currentCropVariant, focusArea = _c.focusArea, coverAreas = _c.coverAreas;
                    focusArea.height = _this.focusArea.height() / container.height();
                    focusArea.width = _this.focusArea.width() / container.width();
                    focusArea.x = (fLeft - left) / container.width();
                    focusArea.y = (fTop - top) / container.height();
                    _this.updatePreviewThumbnail(_this.currentCropVariant, _this.activeCropVariantTrigger);
                    if (_this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
                        _this.focusArea.addClass('has-nodrop');
                    }
                    else {
                        _this.focusArea.removeClass('has-nodrop');
                    }
                },
                stop: function (event, ui) {
                    var revertDelay = 250;
                    var _a = container.offset(), left = _a.left, top = _a.top;
                    var _b = _this.focusArea.offset(), fLeft = _b.left, fTop = _b.top;
                    var _c = _this.currentCropVariant, focusArea = _c.focusArea, coverAreas = _c.coverAreas;
                    if (_this.checkFocusAndCoverAreasCollision(focusArea, coverAreas)) {
                        ui.element.animate($.extend(ui.originalPosition, ui.originalSize), revertDelay, function () {
                            focusArea.height = _this.focusArea.height() / container.height();
                            focusArea.height = _this.focusArea.height() / container.height();
                            focusArea.width = _this.focusArea.width() / container.width();
                            focusArea.x = (fLeft - left) / container.width();
                            focusArea.y = (fTop - top) / container.height();
                            _this.scaleAndMoveFocusArea(focusArea);
                            _this.focusArea.removeClass('has-nodrop');
                        });
                    }
                    else {
                        _this.scaleAndMoveFocusArea(focusArea);
                    }
                },
            });
        };
        /**
         * @method initCoverAreas
         * @desc Initialise cover areas inside the cropper container
         * @param {JQuery} container - The container element to append the cover areas
         * @param {Array<Area>} coverAreas - An array of areas to construxt the cover area elements from
         */
        ImageManipulation.prototype.initCoverAreas = function (container, coverAreas) {
            coverAreas.forEach(function (coverArea) {
                var coverAreaCanvas = $('<div class="cropper-cover-area t3js-cropper-cover-area"></div>');
                container.append(coverAreaCanvas);
                coverAreaCanvas.css({
                    height: ImageManipulation.toCssPercent(coverArea.height),
                    left: ImageManipulation.toCssPercent(coverArea.x),
                    top: ImageManipulation.toCssPercent(coverArea.y),
                    width: ImageManipulation.toCssPercent(coverArea.width),
                });
            });
        };
        /**
         * @method updatePreviewThumbnail
         * @desc Sync the croping (and focus area) to the preview thumbnail
         * @param {CropVariant} cropVariant - The crop variant to preview in the thumbnail
         * @param {JQuery} cropVariantTrigger - The crop variant element containing the thumbnail
         * @private
         */
        ImageManipulation.prototype.updatePreviewThumbnail = function (cropVariant, cropVariantTrigger) {
            var styles;
            var cropperPreviewThumbnailCrop = cropVariantTrigger.find('.t3js-cropper-preview-thumbnail-crop-area');
            var cropperPreviewThumbnailImage = cropVariantTrigger.find('.t3js-cropper-preview-thumbnail-crop-image');
            var cropperPreviewThumbnailFocus = cropVariantTrigger.find('.t3js-cropper-preview-thumbnail-focus-area');
            var imageData = this.cropper.cropper('getImageData');
            // Update the position/dimension of the crop area in the preview
            cropperPreviewThumbnailCrop.css({
                height: ImageManipulation.toCssPercent(cropVariant.cropArea.height / imageData.naturalHeight),
                left: ImageManipulation.toCssPercent(cropVariant.cropArea.x / imageData.naturalWidth),
                top: ImageManipulation.toCssPercent(cropVariant.cropArea.y / imageData.naturalHeight),
                width: ImageManipulation.toCssPercent(cropVariant.cropArea.width / imageData.naturalWidth),
            });
            // Show and update focusArea in the preview only if we really have one configured
            if (cropVariant.focusArea) {
                cropperPreviewThumbnailFocus.css({
                    height: ImageManipulation.toCssPercent(cropVariant.focusArea.height),
                    left: ImageManipulation.toCssPercent(cropVariant.focusArea.x),
                    top: ImageManipulation.toCssPercent(cropVariant.focusArea.y),
                    width: ImageManipulation.toCssPercent(cropVariant.focusArea.width),
                });
            }
            // Destruct the preview container's CSS properties
            styles = cropperPreviewThumbnailCrop.css([
                'width', 'height', 'left', 'top',
            ]);
            /**
             * Apply negative margins on the previewThumbnailImage to make the illusion of an offset
             */
            cropperPreviewThumbnailImage.css({
                height: parseFloat(styles.height) * (1 / (cropVariant.cropArea.height / imageData.naturalHeight)) + "px",
                margin: -1 * parseFloat(styles.left) + "px",
                marginTop: -1 * parseFloat(styles.top) + "px",
                width: parseFloat(styles.width) * (1 / (cropVariant.cropArea.width / imageData.naturalWidth)) + "px",
            });
        };
        /**
         * @method scaleAndMoveFocusArea
         * @desc Calculation logic for moving the focus area given the
         *       specified constrains of a crop and an optional cover area
         * @param {Area} focusArea - The translation data
         */
        ImageManipulation.prototype.scaleAndMoveFocusArea = function (focusArea) {
            this.focusArea.css({
                height: ImageManipulation.toCssPercent(focusArea.height),
                left: ImageManipulation.toCssPercent(focusArea.x),
                top: ImageManipulation.toCssPercent(focusArea.y),
                width: ImageManipulation.toCssPercent(focusArea.width),
            });
            this.currentCropVariant.focusArea = focusArea;
            this.updatePreviewThumbnail(this.currentCropVariant, this.activeCropVariantTrigger);
            this.updateCropVariantData(this.currentCropVariant);
        };
        /**
         * @method updateCropVariantData
         * @desc Immutably updates the currently selected cropVariant data
         * @param {CropVariant} currentCropVariant - The cropVariant to immutably save
         * @private
         */
        ImageManipulation.prototype.updateCropVariantData = function (currentCropVariant) {
            var imageData = this.cropper.cropper('getImageData');
            var absoluteCropArea = this.convertAbsoluteToRelativeCropArea(currentCropVariant.cropArea, imageData);
            this.data[currentCropVariant.id] = $.extend(true, {}, currentCropVariant, { cropArea: absoluteCropArea });
        };
        /**
         * @method setAspectRatio
         * @desc Sets the cropper to a specific ratio
         * @param {ratio} ratio - The ratio value to apply
         * @private
         */
        ImageManipulation.prototype.setAspectRatio = function (ratio) {
            this.cropper.cropper('setAspectRatio', ratio.value);
        };
        /**
         * @method setCropArea
         * @desc Sets the cropper to a specific crop area
         * @param {cropArea} cropArea - The crop area to apply
         * @private
         */
        ImageManipulation.prototype.setCropArea = function (cropArea) {
            var currentRatio = this.currentCropVariant.allowedAspectRatios[this.currentCropVariant.selectedRatio];
            if (currentRatio.value === 0) {
                this.cropper.cropper('setData', {
                    height: cropArea.height,
                    width: cropArea.width,
                    x: cropArea.x,
                    y: cropArea.y,
                });
            }
            else {
                this.cropper.cropper('setData', {
                    height: cropArea.height,
                    x: cropArea.x,
                    y: cropArea.y,
                });
            }
        };
        /**
         * @method checkFocusAndCoverAreas
         * @desc Checks is one focus area and one or more cover areas overlap
         * @param focusArea
         * @param coverAreas
         * @return {boolean}
         */
        ImageManipulation.prototype.checkFocusAndCoverAreasCollision = function (focusArea, coverAreas) {
            if (!coverAreas) {
                return false;
            }
            return coverAreas
                .some(function (coverArea) {
                // noinspection OverlyComplexBooleanExpressionJS
                if (focusArea.x < coverArea.x + coverArea.width &&
                    focusArea.x + focusArea.width > coverArea.x &&
                    focusArea.y < coverArea.y + coverArea.height &&
                    focusArea.height + focusArea.y > coverArea.y) {
                    return true;
                }
            });
        };
        /**
         * @method convertAbsoluteToRelativeCropArea
         * @desc Converts a crop area from absolute pixel-based into relative length values
         * @param {Area} cropArea - The crop area to convert from
         * @param {CropperImageData} imageData - The image data
         * @return {Area}
         */
        ImageManipulation.prototype.convertAbsoluteToRelativeCropArea = function (cropArea, imageData) {
            var height = cropArea.height, width = cropArea.width, x = cropArea.x, y = cropArea.y;
            return {
                height: height / imageData.naturalHeight,
                width: width / imageData.naturalWidth,
                x: x / imageData.naturalWidth,
                y: y / imageData.naturalHeight,
            };
        };
        /**
         * @method convertRelativeToAbsoluteCropArea
         * @desc Converts a crop area from relative into absolute pixel-based length values
         * @param {Area} cropArea - The crop area to convert from
         * @param {CropperImageData} imageData - The image data
         * @return {{height: number, width: number, x: number, y: number}}
         */
        ImageManipulation.prototype.convertRelativeToAbsoluteCropArea = function (cropArea, imageData) {
            var height = cropArea.height, width = cropArea.width, x = cropArea.x, y = cropArea.y;
            return {
                height: height * imageData.naturalHeight,
                width: width * imageData.naturalWidth,
                x: x * imageData.naturalWidth,
                y: y * imageData.naturalHeight,
            };
        };
        /**
         * @method setPreviewImages
         * @desc Updates the preview images in the editing section with the respective crop variants
         * @param {Object} data - The internal crop variants state
         */
        ImageManipulation.prototype.setPreviewImages = function (data) {
            var _this = this;
            var $image = this.cropper;
            var imageData = $image.cropper('getImageData');
            // Iterate over the crop variants and set up their respective preview
            Object.keys(data).forEach(function (cropVariantId) {
                var cropVariant = data[cropVariantId];
                var cropData = _this.convertRelativeToAbsoluteCropArea(cropVariant.cropArea, imageData);
                var $preview = _this.trigger
                    .closest('.form-group')
                    .find(".t3js-image-manipulation-preview[data-crop-variant-id=\"" + cropVariantId + "\"]");
                var $previewSelectedRatio = _this.trigger
                    .closest('.form-group')
                    .find(".t3js-image-manipulation-selected-ratio[data-crop-variant-id=\"" + cropVariantId + "\"]");
                if ($preview.length === 0) {
                    return;
                }
                var previewWidth = $preview.width();
                var previewHeight = $preview.data('preview-height');
                // Adjust aspect ratio of preview width/height
                var aspectRatio = cropData.width / cropData.height;
                var tmpHeight = previewWidth / aspectRatio;
                if (tmpHeight > previewHeight) {
                    previewWidth = previewHeight * aspectRatio;
                }
                else {
                    previewHeight = tmpHeight;
                }
                // preview should never be up-scaled
                if (previewWidth > cropData.width) {
                    previewWidth = cropData.width;
                    previewHeight = cropData.height;
                }
                var ratio = previewWidth / cropData.width;
                var $viewBox = $('<div />').html('<img src="' + $image.attr('src') + '">');
                var $ratioTitleText = _this.currentModal.find(".t3-js-ratio-title[data-ratio-id=\"" + cropVariant.id + cropVariant.selectedRatio + "\"]");
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
        };
        ;
        /**
         * @method openPreview
         * @desc Opens a preview view with the crop variants
         * @param {object} data - The whole data object containing all the cropVariants
         * @private
         */
        ImageManipulation.prototype.openPreview = function (data) {
            var cropVariants = ImageManipulation.serializeCropVariants(data);
            var previewUrl = this.trigger.attr('data-preview-url');
            previewUrl = previewUrl + '&cropVariants=' + encodeURIComponent(cropVariants);
            window.open(previewUrl, 'TYPO3ImageManipulationPreview');
        };
        /**
         * @method save
         * @desc Saves the edited cropVariants to a hidden field
         * @param {object} data - The whole data object containing all the cropVariants
         * @private
         */
        ImageManipulation.prototype.save = function (data) {
            var cropVariants = ImageManipulation.serializeCropVariants(data);
            var hiddenField = $("#" + this.trigger.attr('data-field'));
            this.trigger.attr('data-crop-variants', JSON.stringify(data));
            this.setPreviewImages(data);
            hiddenField.val(cropVariants);
            this.currentModal.modal('hide');
        };
        /**
         * @method destroy
         * @desc Destroy the ImageManipulation including cropper and alike
         * @private
         */
        ImageManipulation.prototype.destroy = function () {
            if (this.currentModal) {
                this.cropper.cropper('destroy');
                this.cropper = null;
                this.currentModal = null;
                this.data = null;
            }
        };
        /**
         * @method resizeEnd
         * @desc Calls a function when the cropper has been resized
         * @param {Function} fn - The function to call on resize completion
         * @private
         */
        ImageManipulation.prototype.resizeEnd = function (fn) {
            var _this = this;
            var timer;
            $(window).on('resize', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fn();
                }, _this.resizeTimeout);
            });
        };
        return ImageManipulation;
    }());
    return new ImageManipulation();
});
