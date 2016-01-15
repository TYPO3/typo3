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
 * Module: TYPO3/CMS/Backend/ImageManipulation
 * Contains all logic for the image crop GUI
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function ($, Modal, Severity) {

	/**
	 *
	 * @type {{margin: number, currentModal: null, cropperSelector: string, $trigger: null}}
	 * @exports TYPO3/CMS/Backend/ImageManipulation
	 */
	var ImageManipulation = {
		margin: 20,
		currentModal: null,
		cropperSelector: '.t3js-cropper-image-container > img',
		$trigger: null
	};

	/**
	 * Initialize triggers
	 */
	ImageManipulation.initializeTrigger = function() {
		var $triggers = $('.t3js-image-manipulation-trigger');
		// Remove existing bind function
		$triggers.off('click', ImageManipulation.buttonClick);
		// Bind new function
		$triggers.on('click', ImageManipulation.buttonClick);
	};

	/**
	 * Functions that should be bind to the trigger button
	 *
	 * @param {Event} e click event
	 */
	ImageManipulation.buttonClick = function(e) {
		e.preventDefault();
		// Prevent double trigger
		if (ImageManipulation.$trigger !== $(this)) {
			ImageManipulation.$trigger = $(this);
			ImageManipulation.show();
		}
	};

	/**
	 * Open modal with image to crop
	 */
	ImageManipulation.show = function() {
		ImageManipulation.currentModal = Modal.loadUrl(
			ImageManipulation.$trigger.data('image-name'),
			Severity.notice,
			[],
			ImageManipulation.$trigger.data('url'),
			ImageManipulation.initializeCropperModal,
			'.modal-content'
		);
		ImageManipulation.currentModal.addClass('modal-dark');
	};

	/**
	 * Initialize the cropper modal
	 */
	ImageManipulation.initializeCropperModal = function() {
		top.require(['cropper', 'imagesloaded'], function(cropperJs, imagesLoaded) {
			var $image = ImageManipulation.getCropper();

			// wait until image is loaded
			imagesLoaded($image, function() {
				var $modal = ImageManipulation.currentModal.find('.modal-dialog');
				var $modalContent = $modal.find('.modal-content');
				var $modalPanelSidebar = $modal.find('.modal-panel-sidebar');
				var $modalPanelBody = $modal.find('.modal-panel-body');
				// Let modal auto-fill width
				$modal.css({width:'auto', marginLeft: ImageManipulation.margin, marginRight: ImageManipulation.margin})
					  .addClass('modal-image-manipulation modal-resize');

				$modalContent.addClass('cropper-bg');

				// Determine available height
				var height = top.TYPO3.jQuery(window).height()
						- (ImageManipulation.margin * 4);
				$image.css({maxHeight: height});

				// Wait a few microseconds before calculating available width (DOM isn't always updated direct)
				setTimeout(function() {
					$modalPanelBody.css({width: $modalContent.innerWidth() - $modalPanelSidebar.outerWidth() - (ImageManipulation.margin * 2)});

					setTimeout(function() {
						// Shrink modal when possible (the set left/right margin + width auto above makes it fill 100%)
						var minWidth = Math.max(500, $image.outerWidth() + $modalPanelSidebar.outerWidth() + (ImageManipulation.margin * 2));
						var width = $modal.width() > minWidth ? minWidth : $modal.width();
						$modal.width(width);
						$modalPanelBody.width(width - $modalPanelSidebar.outerWidth() - (ImageManipulation.margin * 4));

						var modalBodyMinHeight = $modalContent.height() -
							($modalPanelSidebar.find('.modal-header').outerHeight() + $modalPanelSidebar.find('.modal-body-footer').outerHeight());
						$modalPanelSidebar.find('.modal-body').css('min-height', modalBodyMinHeight);

						// Center modal horizontal
						$modal.css({marginLeft: 'auto', marginRight: 'auto'});

						// Center modal vertical
						Modal.center();

						// Wait a few microseconds to let the modal resize
						setTimeout(ImageManipulation.initializeCropper, 100);
					}, 100);

				}, 100);
			});

		});
	};

	/**
	 * Initialize cropper
	 */
	ImageManipulation.initializeCropper = function() {
		var $image = ImageManipulation.getCropper(), cropData;

		// Give img-container same dimensions as the image
		ImageManipulation.currentModal.find('.t3js-cropper-image-container').
		css({width: $image.width(), height: $image.height()});

		var $trigger = ImageManipulation.$trigger;
		var jsonString = $trigger.parent().find('#' + $trigger.data('field')).val();
		if (jsonString.length) {
			cropData = $.parseJSON(jsonString);
		}

		var $infoX = ImageManipulation.currentModal.find('.t3js-image-manipulation-info-crop-x');
		var $infoY = ImageManipulation.currentModal.find('.t3js-image-manipulation-info-crop-y');
		var $infoWidth = ImageManipulation.currentModal.find('.t3js-image-manipulation-info-crop-width');
		var $infoHeight = ImageManipulation.currentModal.find('.t3js-image-manipulation-info-crop-height');

		$image.cropper({
			autoCropArea: 0.5,
			strict: false,
			zoomable: ImageManipulation.currentModal.find('.t3js-setting-zoom').length > 0,
			built: function() {
				if (cropData) {
					// Dimensions CropBox need to be the real visible dimensions
					var ratio = $image.cropper('getImageData').width / $image.data('original-width');
					var cropBox = {};
					cropBox.left = cropData.x * ratio;
					cropBox.top = cropData.y * ratio;
					cropBox.width = cropData.width * ratio;
					cropBox.height = cropData.height * ratio;
					$image.cropper('setCropBoxData', cropBox);
				}
			},
			crop: function (data) {
				var ratio = $image.cropper('getImageData').naturalWidth / $image.data('original-width');
				$infoX.text(Math.round(data.x / ratio) + 'px');
				$infoY.text(Math.round(data.y / ratio) + 'px');
				$infoWidth.text(Math.round(data.width / ratio) + 'px');
				$infoHeight.text(Math.round(data.height / ratio) + 'px');
			}
		});

		// Destroy cropper when modal is closed
		ImageManipulation.currentModal.on('hidden.bs.modal', function() {
			$image.cropper('destroy');
		});

		ImageManipulation.initializeCroppingActions();
	};

	/**
	 * Get image to be cropped
	 *
	 * @returns {Object} jQuery object
	 */
	ImageManipulation.getCropper = function() {
		return ImageManipulation.currentModal.find(ImageManipulation.cropperSelector);
	};

	/**
	 * Bind buttons from cropper tool panel
	 */
	ImageManipulation.initializeCroppingActions = function() {
		ImageManipulation.currentModal.find('[data-method]').click(function(e) {
			e.preventDefault();
			var method = $(this).data('method');
			var options = $(this).data('option') || {};
			if (typeof ImageManipulation[method] === 'function') {
				ImageManipulation[method](options);
			}
		});
	};

	/**
	 * Change the aspect ratio of the crop box
	 *
	 * @param {Number} aspectRatio
	 */
	ImageManipulation.setAspectRatio = function(aspectRatio) {
		var $cropper = ImageManipulation.getCropper();
		$cropper.cropper('setAspectRatio', aspectRatio);
	};

	/**
	 * Set zoom ratio
	 *
	 * Zoom in: requires a positive number (ratio > 0)
	 * Zoom out: requires a negative number (ratio < 0)
	 *
	 * @param {Number} ratio
	 */
	ImageManipulation.zoom = function(ratio) {
		var $cropper = ImageManipulation.getCropper();
		$cropper.cropper('zoom', ratio);
	};

	/**
	 * Save crop values in form and close modal
	 */
	ImageManipulation.save = function() {
		var $image = ImageManipulation.getCropper();
		var $trigger = ImageManipulation.$trigger;
		var formFieldId = $trigger.data('field');
		var $formField = $trigger.parent().find('#' + formFieldId);
		var $formGroup = $formField.closest('.form-group');
		var cropData = $image.cropper('getData');
		var newValue = '';
		$formGroup.addClass('has-change');
		if (cropData.width > 0 && cropData.height > 0) {
			var ratio = $image.cropper('getImageData').naturalWidth / $image.data('original-width');
			cropData.x = cropData.x / ratio;
			cropData.y = cropData.y / ratio;
			cropData.width = cropData.width / ratio;
			cropData.height = cropData.height / ratio;
			newValue = JSON.stringify(cropData);
			$formGroup.find('.t3js-image-manipulation-info').removeClass('hide');
			$formGroup.find('.t3js-image-manipulation-info-crop-x').text(Math.round(cropData.x) + 'px');
			$formGroup.find('.t3js-image-manipulation-info-crop-y').text(Math.round(cropData.y) + 'px');
			$formGroup.find('.t3js-image-manipulation-info-crop-width').text(Math.round(cropData.width) + 'px');
			$formGroup.find('.t3js-image-manipulation-info-crop-height').text(Math.round(cropData.height) + 'px');
			$formGroup.find('.t3js-image-manipulation-preview').removeClass('hide');
			ImageManipulation.setPreviewImage();
		} else {
			$formGroup.find('.t3js-image-manipulation-info').addClass('hide');
			$formGroup.find('.t3js-image-manipulation-preview').addClass('hide');
		}
		$formField.val(newValue);
		ImageManipulation.dismiss();
	};

	/**
	 * Reset crop selection
	 */
	ImageManipulation.reset = function() {
		var $image = ImageManipulation.getCropper();
		$image.cropper('clear');
	};

	/**
	 * Close the current open modal
	 */
	ImageManipulation.dismiss = function() {
		if (ImageManipulation.currentModal) {
			ImageManipulation.currentModal.modal('hide');
			ImageManipulation.currentModal = null;
		}
	};

	/**
	 * Set preview image
	 */
	ImageManipulation.setPreviewImage = function() {
		var $preview = ImageManipulation.$trigger.closest('.form-group').find('.t3js-image-manipulation-preview');
		if ($preview.length === 0) {
			return;
		}
		var $image = ImageManipulation.getCropper();
		var imageData = $image.cropper('getImageData');
		var cropData = $image.cropper('getData');
		var previewWidth = $preview.data('preview-width');
		var previewHeight = $preview.data('preview-height');

		// Adjust aspect ratio of preview width/height
		var aspectRatio = cropData.width / cropData.height;
		var tmpHeight = previewWidth / aspectRatio;
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

		var ratio = previewWidth / cropData.width;

		var $viewBox = $('<div />').html('<img src="' + $image.attr('src') + '">');
		$viewBox.addClass('cropper-preview-container');
		$preview.empty().append($viewBox);
		$viewBox.wrap('<span class="thumbnail thumbnail-status"></span>');

		$viewBox.width(previewWidth).height(previewHeight).find('img').css({
			width: imageData.naturalWidth * ratio,
			height: imageData.naturalHeight * ratio,
			left: -cropData.x * ratio,
			top: -cropData.y * ratio
		});
	};

	return ImageManipulation;
});
