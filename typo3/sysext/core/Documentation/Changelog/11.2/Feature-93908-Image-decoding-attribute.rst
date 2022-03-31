.. include:: /Includes.rst.txt

==================================================
Feature: #93908 - Add decoding attribute to images
==================================================

See :issue:`93908`

Description
===========

TYPO3 now supports the :html:`decoding` HTML attribute in :html:`<img>`
tags.

Supported browsers choose to decode these images asynchronously to not
prevent presentation of other content. This has an effect of presenting
non-image content faster. However, the image content is missing on screen until
the decode finishes. Once the decode is finished, the screen is updated with the
image.

The configuration option is available via TypoScript constants and
can be easily adjusted via the TypoScript Constant Editor in the Template
module. The default value is an empty string.

Impact
======

TYPO3 frontend decodes images in content elements asynchronously by default
when using TYPO3 templates from Fluid Styled Content.

Using the TypoScript constant :typoscript:`styles.content.image.imageDecoding`,
the behavior can be modified generally to be either set to :typoscript:`sync`, :typoscript:`async`
:typoscript:`auto` or to an empty value which removes the property.

The Fluid :php:`ImageViewHelper` and :php:`MediaViewHelper` have the possibility to set this
attribute via :html:`<f:image src="{fileObject}" decoding="async">`
and :html:`<f:media file="{fileObject}" decoding="async">`.

.. index:: Frontend, ext:fluid_styled_content
