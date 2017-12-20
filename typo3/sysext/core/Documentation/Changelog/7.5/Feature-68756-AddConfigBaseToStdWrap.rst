
.. include:: ../../Includes.txt

==============================================
Feature: #68756 - Add config "base" to stdWrap
==============================================

See :issue:`68756`

Description
===========

The following function was updated and added with a new optional parameter $base:
`TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($sizeInBytes, $labels = '', $base = 0)`

This affects the function:
`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_bytes`

Until now it was not possible to set the base parameter via TypoScript if you add custom labels.


Impact
======

The ability to set the base (1000 or 1024) via TypoScript configuration has been added.

With the `base` property it can be defined whether to use a base of 1000 or 1024 to calculate with

Thus::
    bytes.labels = " | K| M| G"
    bytes.base = 1000


.. index:: PHP-API, TypoScript
