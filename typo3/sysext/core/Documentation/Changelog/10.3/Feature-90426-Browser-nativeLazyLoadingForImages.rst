.. include:: /Includes.rst.txt

========================================================
Feature: #90426 - Browser-native lazy loading for images
========================================================

See :issue:`90426`

Description
===========

TYPO3 now supports the browser-native :html:`loading` HTML attribute in :html:`<img>` tags.

It is set to "lazy" by default for all images within Content Elements rendered
with Fluid Styled Content. Supported browsers then choose to load these
images at a later point when the image is within the browsers' viewport.

The configuration option is available via TypoScript constants and
can be easily adjusted via the TypoScript Constant Editor in the Template module.

Please note that not all browsers support this option yet, but adding
this property will just be skipped for unsupported browsers.


Impact
======

TYPO3 Frontend now renders images in content elements with the :html:`"loading=lazy"`
attribute by default when using TYPO3's templates from Fluid Styled Content.

Using the TypoScript constant :typoscript:`styles.content.image.lazyLoading`,
the behavior can be modified generally to be either set to :html:`eager`,
:html:`auto` or to an empty value, removing the property directly.

The Fluid ImageViewHelper has the possibility to set this option
via :html:`<f:image src="{fileObject}" treatIdAsReference="true" loading="lazy" />`
to hint the browser on how the prioritization of image loading should be used.

.. index:: Frontend, ext:fluid_styled_content
