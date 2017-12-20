.. include:: ../../Includes.txt

======================================================================================
Breaking: #83241 - Extbase: Removed custom functionality for DataMapper->getPlainValue
======================================================================================

See :issue:`83241`

Description
===========

Extbase's DataMapper allowed for wrapping string values in custom user functions via custom parameters.
This was primarily placed in DataMapper for allowing TYPO3's legacy DBAL / :php:`$GLOBALS['TYPO3_DB']`.

The functionality is now removed, as the Generic Backend is handled via Doctrine DBAL.


Impact
======

Calling :php:`DataMapper->getPlainValue()` with the third or fourth parameter set will have no effect anymore.


Affected Installations
======================

In an VERY unlikely case of using a custom Persistence Backend within Extbase in an extension, some
transformations will not work as expected anymore.


Migration
=========

Use the transformations outside the DataMapper, if still necessary.

.. index:: PHP-API, FullyScanned, ext:extbase