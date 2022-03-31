
.. include:: /Includes.rst.txt

=============================================================
Deprecation: #77502 - Extbase: pre-parsing of queries removed
=============================================================

See :issue:`77502`

Description
===========

The following methods and properties within Extbase's persistence query comparison interface have been marked as deprecated:

* :php:`Comparison->setParameterIdentifier()`
* :php:`Comparison->getParameterIdentifier()`


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation using custom logic inside Extbase's own persistence layer with parameters and placeholders within
`Typo3DbBackend` or `Typo3DbQueryParser` and actively overwriting parameter identifiers within Extbase.


Migration
=========

Usage of these methods can be replaced by simply using the `DataMapper->getPlainValue()` functionality.

.. index:: PHP-API, Database, ext:extbase
