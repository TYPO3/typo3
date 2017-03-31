.. include:: ../../Includes.txt

===========================================================================
Feature: #79883 - Add cropVariant support to TypoScript rendering of images
===========================================================================

See :issue:`79883`

Description
===========

The introduction of the new crop variants :issue:`75880` broke the handling of
cropped images when using TypoScript to render file(reference)'s. This feature
fixes this and introduces a new TypoScript option to use a different cropVariant.

To use a different :typoscript:`cropVariant` as default you can provide the
:typoscript:`cropVariant` name now in your TypoScript configuration.
If :typoscript:`cropVariant` isn't provided the default variant will be used.

.. code-block:: typoscript

	# Use specific cropVariant for the images
	tt_content.image.20.1.file.cropVariant = mobile


Impact
======

If multiple cropVariants are available (see :issue:`75880`) you can now configure
which variant to use with the :typoscript:`cropVariant` option of :typoscript:`imgResource`.


.. index:: FAL, Frontend, TypoScript
