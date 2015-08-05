==============================================
Feature: #68756 - Add config "base" to stdWrap
==============================================

Description
===========

Follow up: #22175

The following function was updated and added with a new optional parameter $base:
TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($sizeInBytes, $labels = '', $base = 0)

This impacts the function:
TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_bytes

It is not possible now, to set the base parameter via TypoScript if you add custom labels.


Impact
======

Ability to set the base (1000 or 1024) via TypoScript configuration was added.

The following lines should be added to the file "TYPO3CMS-Reference-Typoscript/Documentation/Functions/Stdwrap/Index.rst" in the "Property bytes container".

With the ``base`` property it can be defined whether to use a base of 1000 or 1024 to calculate with

Thus::
    bytes.labels = " | K| M| G"
    bytes.base = 1000