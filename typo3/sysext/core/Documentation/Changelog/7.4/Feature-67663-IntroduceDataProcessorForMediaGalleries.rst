
.. include:: ../../Includes.txt

=============================================================
Feature: #67663 - Introduce DataProcessor for media galleries
=============================================================

See :issue:`67663`

Description
===========

The logic for working with galleries and calculating the maximum asset size is done in a separate GalleryProcessor.
The GalleryProcessor uses the files already present in the processedData array for his calculations. The FilesProcessor
can be used to fetch the files.

.. code-block:: typoscript

	tt_content.textmedia.20 = FLUIDTEMPLATE
	tt_content.textmedia.20 {
		file = EXT:myextension/Resources/Private/Templates/ContentObjects/Image.html

		dataProcessing {

			# Process files
			10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor

			# Calculate gallery info
			20 = TYPO3\CMS\Frontend\DataProcessing\GalleryProcessor
			20 {

				# filesProcessedDataKey :: Key in processedData array that holds the files (default: files) + stdWrap
				filesProcessedDataKey = files

				# mediaOrientation :: Media orientation, see: TCA[tt_content][column][imageorient] (default: data.imageorient) + stdWrap
				mediaOrientation.field = imageorient

				# numberOfColumns :: Number of columns (default: data.imagecols) + stdWrap
				numberOfColumns.field = imagecols

				# equalMediaHeight :: Equal media height in pixels (default: data.imageheight) + stdWrap
				equalMediaHeight.field = imageheight

				# equalMediaWidth :: Equal media width in pixels (default: data.imagewidth) + stdWrap
				equalMediaWidth.field = imagewidth

				# maxGalleryWidth :: Max gallery width in pixels (default: 600) + stdWrap
				maxGalleryWidth = 1000

				# maxGalleryWidthInText :: Max gallery width in pixels when orientation intext (default: 300) + stdWrap
				maxGalleryWidthInText = 1000

				# columnSpacing :: Column spacing width in pixels (default: 0) + stdWrap
				columnSpacing = 0

				# borderEnabled :: Border enabled (default: data.imageborder) + stdWrap
				borderEnabled.field = imageborder

				# borderWidth :: Border width in pixels (default: 0) + stdWrap
				borderWidth = 0

				# borderPadding :: Border padding in pixels  (default: 0) + stdWrap
				borderPadding = 10

				# as :: Name of key in processedData array where result is placed (default: gallery) + stdWrap
				as = gallery
			}
		}
	}


.. index:: TypoScript, Frontend
