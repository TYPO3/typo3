.. include:: ../../Includes.txt

=================================================================================
Feature: #75880 - Implement multiple cropping variants in image manipulation tool
=================================================================================

See :issue:`75880`

Description
===========

The `imageManipulation` TCA type is now capable to handle multiple crop variants if configured.

The default configuration is to have only one variant with the same possible aspect ratios
like in older TYPO3 versions.

For that the TCA configuration has been extended.
The following example configures two crop variants, one with the id "mobile",
one with the id "desktop". The array key defines the crop variant id, which will be used
when rendering an image with the image view helper.

The allowed crop areas are now also configured differently.
The array key is used as identifier for the ratio and the label is specified with the "title"
and the actual (floating point) ratio with the "value" key.
The value **must** be of PHP type float, not only a string.

.. code-block:: php

	'config' => [
	     'type' => 'imageManipulation',
	     'cropVariants' => [
	         'mobile' => [
	             'title' => 'LLL:EXT:ext_key/Resources/Private/Language/locallang.xlf:imageManipulation.mobile',
	             'allowedAspectRatios' => [
	                 '4:3' => [
	                     'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.4_3',
	                     'value' => 4 / 3
	                 ],
	                 'NaN' => [
	                     'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
	                     'value' => 0.0
	                 ],
	             ],
	         ],
	         'desktop' => [
	             'title' => 'LLL:EXT:ext_key/Resources/Private/Language/locallang.xlf:imageManipulation.desktop',
	             'allowedAspectRatios' => [
	                 '4:3' => [
	                     'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.4_3',
	                     'value' => 4 / 3
	                 ],
	                 'NaN' => [
	                     'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
	                     'value' => 0.0
	                 ],
	             ],
	         ],
	     ]
	 ]


It is now also possible to define an initial crop area. If no initial crop area is defined, the default selected crop area will cover the complete image.
Crop areas are defined relatively with floating point numbers. The x and y coordinates and width and height must be specified for that.
The below example has an initial crop area in the size the previous image cropper provided by default.

.. code-block:: php

	'config' => [
	    'type' => 'imageManipulation',
	    'cropVariants' => [
	        'mobile' => [
	            'title' => 'LLL:EXT:ext_key/Resources/Private/Language/locallang.xlf:imageManipulation.mobile',
	            'cropArea' => [
	                'x' => 0.1,
	                'y' => 0.1,
	                'width' => 0.8,
	                'height' => 0.8,
	            ],
	        ],
	    ],
	]

Users can also select a focus area, when configured. The focus area is always **inside**
the crop area and mark the area in the image which must be visible for the image to transport
its meaning. The selected area is persisted to the database but will have no effect on image processing.
The data points are however made available as data attribute when using the `<f:image />` view helper.

The below example adds a focus area, which is initially one third of the size of the image
and centered.

.. code-block:: php

	'config' => [
	    'type' => 'imageManipulation',
	    'cropVariants' => [
	        'mobile' => [
	            'title' => 'LLL:EXT:ext_key/Resources/Private/Language/locallang.xlf:imageManipulation.mobile',
	            'focusArea' => [
	                'x' => 1 / 3,
	                'y' => 1 / 3,
	                'width' => 1 / 3,
	                'height' => 1 / 3,
	            ],
	        ],
	    ],
	]

Very often images are used in a context, where there are overlayed with other DOM elements
like a headline. To give editors a hint which area of the image is affected, when selecting a crop area,
it is possible to define multiple so called cover areas. These areas are shown inside
the crop area. The focus area cannot intersect with any of the cover areas.

.. code-block:: php

	'config' => [
	    'type' => 'imageManipulation',
	     'coverAreas' => [
	         [
	             'x' => 0.05,
	             'y' => 0.85,
	             'width' => 0.9,
	             'height' => 0.1,
	         ]
	     ],
	    ],
	]

To render crop variants, the variants can be specified as argument to the image view helper:

.. code-block:: html

	<f:image image="{data.image}" cropVariant="mobile" width="800" />

Impact
======

TCA configuration for field type "imageManipulation" has changed. Old configuration options
still work but are deprecated and issue a warning when used.

The TCA configuration option `enableZoom` has been removed for now. It wasn't really usable
anyway and will need some proper UX design before re-implementation. Setting the option
will have no effect.

.. index:: Backend, TCA
