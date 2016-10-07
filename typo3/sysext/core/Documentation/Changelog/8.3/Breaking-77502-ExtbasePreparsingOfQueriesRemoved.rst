
.. include:: ../../Includes.txt

==========================================================
Breaking: #77502 - Extbase: pre-parsing of queries removed
==========================================================

See :issue:`77502`

Description
===========

Extbase's custom implementation to pre-parse and cache queries has been removed in favor of using the RDBMS' native implementation
via Doctrine DBAL.

The following public methods have been removed:
* `Typo3DbBackend->quoteTextValueCallback()`
* `Typo3DbQueryParser->preparseQuery()`
* `Typo3DbQueryParser->normalizeParameterIdentifier()`
* `Typo3DbQueryParser->addDynamicQueryParts()`
* `ComparisonInterface->setParameterIdentifier`
* `ComparisonInterface->getParameterIdentifier`


Impact
======

Calling any of the methods above will result in a fatal PHP error.


Affected Installations
======================

Any TYPO3 installation using custom logic inside Extbase's own Persistence layer within `Typo3DbBackend` or `Typo3DbQueryParser`.


Migration
=========

Remove the functionality and just use `Typo3DbQueryParser->parseQuery()`.

.. index:: Database, PHP-API, ext:extbase
