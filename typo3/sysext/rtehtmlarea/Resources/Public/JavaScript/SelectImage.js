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
 * Module: TYPO3/CMS/Rtehtmlarea/SelectImage
 * This module is used by the RTE SelectImage module
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
	'use strict';

	/**
	 *
	 * @type {{plugin: Object, initialize: Function, getCurrentImage: Function, setImagesInRTE: Function}}
	 * @exports TYPO3/CMS/Rtehtmlarea/SelectImage
	 */
	var SelectImage = {
		plugin: null,

		initialize: function() {
			SelectImage.plugin = window.parent.RTEarea[LinkBrowser.urlParameters.editorNo].editor.getPlugin("TYPO3Image");
		},

		getCurrentImage: function() {
			return SelectImage.plugin.image;
		},

		setImagesInRTE: function(uidList) {
			var parameters = LinkBrowser.urlParameters;

			parameters.uidList = uidList;

			var selectedImageRef = SelectImage.getCurrentImage();
			if (selectedImageRef) {
				parameters.cWidth = selectedImageRef.style.width;
				parameters.cHeight = selectedImageRef.style.height;
			}

			$.ajax({
				url: TYPO3.settings.ajaxUrls['rte_insert_image'],
				data: parameters,
				method: 'GET',
				success: function(data) {
					if (data.images) {
						SelectImage.plugin.insertImage(data.images);
					}
				}
			});
		}
	};

	$(SelectImage.initialize);

	return SelectImage;
});
