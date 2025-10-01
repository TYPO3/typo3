.. include:: /Includes.rst.txt

.. _breaking-107403-1757082780:

========================================================================
Breaking: #107403 - Remove LocalPreviewHelper + LocalCropScaleMaskHelper
========================================================================

See :issue:`107403`

Description
===========

The helper classes for Preview and CropScaleMask (CSM) for generating images
blocked a proper unification of the File Abstraction Layer Image Processing API.

The two helper classes :php:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper`
and :php:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper` have
now been removed, as their functionality has been merged into
:php:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor`.

The helper classes existed due to legacy reasons, but were never intended
to be Public API.

Impact
======

Any code that extends or references :php:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper`
or :php:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper`
will result in PHP fatal errors.

Affected installations
======================

Installations with custom extensions that extend or reference the
:php:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper`
or :php:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper`
will cause PHP fatal errors.

The helper classes were meant to be internal, but were not declared
as such. Implementations utilizing the helpers outside the use
of the :php:`LocalImageProcessor` should be rare.

Migration
=========

Remove any references to :php:`\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper`
or :php:`\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper`
from your code.

Utilize the :php:`\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor` processor
directly instead or implement a custom image processor that is executed before
this processor with custom functionality.

.. index:: PHP-API, FullyScanned
