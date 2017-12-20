
.. include:: ../../Includes.txt

==========================================================================
Breaking: #45899 - Split class ImportExport into classes Import and Export
==========================================================================

See :issue:`45899`

Description
===========

Class TYPO3\CMS\Impexp\ImportExport (typo3/sysext/impexp/Classes/ImportExport.php)
has been split into a class dedicated for import and another one for export.


Impact
======

Using and extending the class is not possible anymore.


Affected Installations
======================

Extensions using the class and its methods directly or extending the class.


Migration
=========

Use or extend one or both of the new classes (TYPO3\CMS\Impexp\Import and TYPO3\CMS\Impexp\Export).

.. index:: PHP-API, ext:impexp
