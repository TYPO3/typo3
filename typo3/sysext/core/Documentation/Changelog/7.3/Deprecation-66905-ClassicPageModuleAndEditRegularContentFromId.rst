
.. include:: ../../Includes.txt

===========================================================================================
Deprecation: #66905 - Deprecate uc->classicPageEditMode and editRegularContentFromId option
===========================================================================================

See :issue:`66905`

Description
===========

The BE-User uc option "classicPageEditMode" which was used prior to TYPO3 CMS 4.0 has been removed some time ago.
The functionality `editRegularContentFromId` which was then triggered in EditDocumentController has been marked
for deprecation.


Impact
======

Any direct calls using `editRegularContentFromId` via GET parameter or calling `editRegularContentFromId()`
directly from a third-party extension will trigger a deprecation message.


Affected Installations
======================

Any installation using third-party code to restore the old behaviour.


Migration
=========

Remove calls to the functionality.


.. index:: PHP-API, Backend
