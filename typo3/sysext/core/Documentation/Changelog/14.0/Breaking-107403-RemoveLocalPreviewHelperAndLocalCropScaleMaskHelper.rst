..  include:: /Includes.rst.txt

..  _breaking-107403-1757082780:

========================================================================
Breaking: #107403 - Remove LocalPreviewHelper + LocalCropScaleMaskHelper
========================================================================

See :issue:`107403`

Description
===========

The helper classes for preview and CropScaleMask (CSM) image generation
prevented further unification of the File Abstraction Layer (FAL) image
processing API.

The two helper classes
:php:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper` and
:php:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper`
have been removed. Their functionality has been merged into
:php:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor`.

These helper classes existed for historical reasons and were never intended
to be part of the public API.

Impact
======

Any code that extends or references
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper` or
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper`
will now trigger PHP fatal errors.

Affected installations
======================

Installations with custom extensions that extend or reference either
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper` or
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper`
are affected and will cause PHP fatal errors.

These helper classes were meant to be internal but were never declared as
such. Implementations using them directly instead of the
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor` should
be very rare.

Migration
=========

Remove all references to
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper` or
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper`
from your code.

Use the
:php-short:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor`
directly instead, or implement a custom image processor that executes before
this processor to apply additional functionality.

..  index:: PHP-API, FullyScanned
