.. include:: /Includes.rst.txt

.. _deprecation-104325-1720298173:

=====================================================
Deprecation: #104325 - DiffUtility->makeDiffDisplay()
=====================================================

See :issue:`104325`

Description
===========

Method :php:`\TYPO3\CMS\Core\Utility\DiffUtility->makeDiffDisplay()`
and class property :php:`DiffUtility->stripTags` have been
deprecated in favor of new method :php:`DiffUtility->diff()`.
The new method no longer applies :php:`strip_tags()` to the input strings.

This change makes class :php-short:`\TYPO3\CMS\Core\Utility\DiffUtility` stateless: Property
:php:`$stripTags` will vanish in v14.

Impact
======

Using method :php:`DiffUtility->makeDiffDisplay()` will trigger a
deprecation level error message.


Affected installations
======================

Instances with extensions calling :php:`DiffUtility->makeDiffDisplay()`.


Migration
=========

If :php:`DiffUtility->stripTags` *is not* explicitly set to false, a typical
migration looks like this:

.. code-block:: php

    // before
    $diffUtility->DiffUtility->makeDiffDisplay($from, $to);

    // after
    $diffUtility->DiffUtility->diff(strip_tags($from), stripTags($to));

If :php:`DiffUtility->stripTags = false` is set before calling
:php:`DiffUtility->makeDiffDisplay()`, method :php:`diff()` can be called
as before, and :php:`DiffUtility->stripTags = false` can be removed.

.. index:: PHP-API, FullyScanned, ext:core
