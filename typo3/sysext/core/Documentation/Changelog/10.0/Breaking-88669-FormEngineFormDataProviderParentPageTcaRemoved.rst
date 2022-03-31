.. include:: /Includes.rst.txt

======================================================================
Breaking: #88669 - FormEngine FormDataProvider "parentPageTca" removed
======================================================================

See :issue:`88669`

Description
===========

FormEngine added :php:`parentPageTca` by default to the result object. It was added in TYPO3 v7 during
refactoring, but already commented that it wasn't used at all in Core, and might not be necessary.

It contained a copy of :php:`$GLOBALS['TCA']['pages']`, which can be obtained directly as well.

The DataProvider and the value within the result key has been removed.


Impact
======

When accessing the :php:`parentPageTca` key within a FormDataProvider or Node (FormEngine-related only),
a PHP notice is given due to a non-existing array key.


Affected Installations
======================

TYPO3 installations with custom FormDataProviders for FormEngine relying on the "parentPageTca"
DataProvider, which is highly unlikely.


Migration
=========

Instead of accessing :php:`$result['parentPageTca']` within a custom FormDataProvider or FormRenderNode,
:php:`$GLOBALS['TCA']['pages']` can be accessed directly.

.. index:: TCA, NotScanned
