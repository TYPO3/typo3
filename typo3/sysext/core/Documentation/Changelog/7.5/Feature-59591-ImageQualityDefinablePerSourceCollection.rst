
.. include:: ../../Includes.txt

==============================================================
Feature: #59591 - Image quality definable per sourceCollection
==============================================================

See :issue:`59591`

Description
===========

The image quality of each `sourceCollection` entry can be configured.
Integrators can already render images with the predefined quality set by LocalConfiguration.php.
To decrease the quality of larger images (e.g. double density) in order to lower the file size,
integrators can configure the parameter `quality` of the matching `sourceCollection` now.

The TypoScript setup can be configured as followed (e.g.):

.. code-block:: typoscript

	# for small retina images
	tt_content.image.20.1.sourceCollection.smallRetina.quality = 80

	# for large retina images
	tt_content.image.20.1.sourceCollection.largeRetina.quality = 65

If the new parameter is not set, TYPO3 will use to the default quality of LocalConfiguration.


Impact
======

The rendering of `sourceCollection` stays as it is. Users can additionally selectively control the quality of jpeg by TypoScript setup.
