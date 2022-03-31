.. include:: /Includes.rst.txt

===============================================================================
Deprecation: #92551 - GeneralUtility methods related to pages.l18n_cfg behavior
===============================================================================

See :issue:`92551`

Description
===========

The methods

* :php:`GeneralUtility::hideIfNotTranslated()`
* :php:`GeneralUtility::hideIfDefaultLanguage()`

have been marked as deprecated in favor of a new BitSet-based PHP class
:php:`TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility`.


Impact
======

Calling both methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installation with custom third-party extensions calling
these methods for explicit and special page translation handling.


Migration
=========

Instead of :php:`GeneralUtility::hideIfDefaultLanguage()` use

.. code-block:: php

   $pageTranslationVisibility = new PageTranslationVisibility((int)$page['l18n_cfg'] ?? 0)
   $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()


Instead of :php:`GeneralUtility::hideIfNotTranslated()` use

.. code-block:: php

   $pageTranslationVisibility = new PageTranslationVisibility((int)$page['l18n_cfg'] ?? 0)
   $pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()

.. index:: PHP-API, FullyScanned, ext:core
