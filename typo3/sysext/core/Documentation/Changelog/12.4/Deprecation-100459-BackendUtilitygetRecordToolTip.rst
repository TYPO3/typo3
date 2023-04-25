.. include:: /Includes.rst.txt

.. _deprecation-100459-1680683235:

=========================================================
Deprecation: #100459 - BackendUtility::getRecordToolTip()
=========================================================

See :issue:`100459`

Description
===========

The method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordToolTip()`
has been marked as deprecated.


Impact
======

Calling this method will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations with custom extensions using this method. This is usually
the case for old installations where Fluid templates or Extbase backend modules
were not common.


Migration
=========

As this method is just a wrapper around :php:`BackendUtility::getRecordIconAltText()`
with a "title" attribute for the markup, the replacement is straightforward:

Before:

..  code-block:: php

    $link = '<a href="..." ' . BackendUtility::getRecordToolTip(...) . '>my link</a>';

After:

..  code-block:: php

    $link = '<a href="..." title="' . BackendUtility::getRecordIconAltText(...) . '">my link</a>';

.. index:: Backend, PHP-API, FullyScanned, ext:backend
