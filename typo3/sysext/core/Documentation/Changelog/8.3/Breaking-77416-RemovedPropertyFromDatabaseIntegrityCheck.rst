
.. include:: ../../Includes.txt

===============================================================
Breaking: #77416 - Removed property from DatabaseIntegrityCheck
===============================================================

See :issue:`77416`

Description
===========

The property :php:`$perms_clause` has been removed from class :php:`DatabaseIntegrityCheck`.


Impact
======

Setting or reading this property on an instance of :php:`TYPO3\CMS\Core\Integrity\DatabaseIntegrityCheck` will
result in a fatal PHP error.


Affected Installations
======================

All installations with a 3rd party extension using this class.


Migration
=========

No migration available.

.. index:: PHP-API, Database
