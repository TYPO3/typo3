.. include:: /Includes.rst.txt

.. _deprecation-104325-1720298173:

=====================================================
Deprecation: #104325 - DiffUtility->makeDiffDisplay()
=====================================================

See :issue:`104325`

Description
===========

Method :php:`\TYPO3\CMS\Core\Utility\DiffUtility->makeDiffDisplay()`
and class property :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->stripTags` have been
deprecated in favor of new method :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->diff()`.
The new method no longer applies :php:`strip_tags()` to the input strings.

This change makes class :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility` stateless: Property
:php:`$stripTags` will vanish in v14.

Impact
======

Using method :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->makeDiffDisplay()` will trigger a
deprecation level error message.


Affected installations
======================

Instances with extensions calling :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->makeDiffDisplay()`.


Migration
=========

If :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->stripTags` *is not* explicitly set to false, a typical
migration looks like this:

.. code-block:: php

    // before
    $diffUtility->DiffUtility->makeDiffDisplay($from, $to);

    // after
    $diffUtility->DiffUtility->diff(strip_tags($from), stripTags($to));

If :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->stripTags = false` is set before calling
:php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->makeDiffDisplay()`, method :php:`diff()` can be called
as before, and :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility->stripTags = false` can be removed.

.. index:: PHP-API, FullyScanned, ext:core
