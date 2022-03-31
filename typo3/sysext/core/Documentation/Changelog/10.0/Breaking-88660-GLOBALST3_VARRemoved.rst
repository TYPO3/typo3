.. include:: /Includes.rst.txt

===========================================
Breaking: #88660 - $GLOBALS[T3_VAR] removed
===========================================

See :issue:`88660`

Description
===========

The global variable :php:`$GLOBALS['T3_VAR']` previously used to hold global state for special
use cases - previously used within Service API via :php:`GeneralUtility::makeInstanceService()`
and to magically inject special hard-coded local indexed search files, has been removed.

The overall goal of TYPO3's application is to not keep any state within global variables, and
the :php:`T3_VAR` ("TYPO3 Various") has not been actively used for that anymore since TYPO3 6.0, and
has been kept only for backwards-compatibility of the existing solutions.

The initialization of the global variable during TYPO3 Bootstrap, any usages of :php:`T3_VAR`,
especially within "indexed search" has been removed.


Impact
======

Accessing :php:`$GLOBALS['T3_VAR']` is fully custom and not evaluated by TYPO3 Core anymore.

Using the variable to modify any global state for e.g. Indexed Search's indexer via
:php:`$GLOBALS['T3_VAR']['ext']['indexed_search']['indexLocalFiles']` is not respected anymore
and has no effect.


Affected Installations
======================

TYPO3 installations with third-party extensions or code within :file:`AdditionalConfiguration.php`
that actively set or read values from the global variable.


Migration
=========

Use your own custom global namespace to identify that your specific extension code has nothing
to do with TYPO3's legacy work.

Use specific hooks for indexing local files used by download extensions in conjunction with
Indexed Search.

.. index:: PHP-API, FullyScanned, ext:indexed_search
