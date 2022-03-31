.. include:: /Includes.rst.txt

=======================================================
Breaking: #83122 - Removed stdWrap option TCAselectItem
=======================================================

See :issue:`83122`

Description
===========

The option `TCAselectItem` is rarely used and also does not cover all possibilities of the core like manipulating
entries with TSconfig and the mentioned support of database relations.


Impact
======

The stdWrap option :typoscript:`TCAselectItem` will not work anymore.

Calling :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::stdWrap_TCAselectItem` and
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::TCAlookup` will trigger an error.


Affected Installations
======================

Any instance using the stdWrap option :typoscript:`TCAselectItem` or calls to
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::stdWrap_TCAselectItem` and
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::TCAlookup`.


Migration
=========

Use a custom userFunc to rebuild the functionality.

.. index:: Frontend, TypoScript, PartiallyScanned
