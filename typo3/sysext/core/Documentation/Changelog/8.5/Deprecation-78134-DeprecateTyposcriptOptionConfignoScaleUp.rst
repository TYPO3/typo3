.. include:: /Includes.rst.txt

==================================================================
Deprecation: #78134 - Deprecate TypoScript option config.noScaleUp
==================================================================

See :issue:`78317`

Description
===========

The TypoScript setting `config.noScaleUp` has been marked as deprecated.


Impact
======

Using this setting `config.noScaleUp` will trigger a deprecation log entry. It will work until it get's removed in TYPO3 v9.


Affected Installations
======================

Instances that use this TypoScript setting.


Migration
=========

Use the provided global TYPO3 configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'];` to allow
upscaling of images on a "per installation" basis.

.. index:: Frontend, TypoScript
