============================================================
Deprecation: #77502 - Extbase: Preparsing of queries removed
============================================================

Description
===========

The following methods and properties within Extbase's persistence query comparison interface have been marked as deprecated:

* Comparison->setParameterIdentifier()
* Comparison->getParameterIdentifier()


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation using custom logic inside Extbase's own Persistence layer with parameters and placeholders within
``Typo3DbBackend`` or ``Typo3DbQueryParser`` and actively overwriting parameter identifiers within Extbase.


Migration
=========

The methods can be removed by simply using the ``DataMapper->getPlainValue()`` functionality.