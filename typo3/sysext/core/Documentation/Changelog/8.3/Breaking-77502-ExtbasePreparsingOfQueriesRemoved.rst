
.. include:: /Includes.rst.txt

==========================================================
Breaking: #77502 - Extbase: pre-parsing of queries removed
==========================================================

See :issue:`77502`

Description
===========

Extbase's custom implementation to pre-parse and cache queries has been removed in favor of using the RDBMS' native implementation
via Doctrine DBAL.

The following public methods have been removed:

* :php:`Typo3DbBackend->quoteTextValueCallback()`
* :php:`Typo3DbQueryParser->preparseQuery()`
* :php:`Typo3DbQueryParser->normalizeParameterIdentifier()`
* :php:`Typo3DbQueryParser->addDynamicQueryParts()`
* :php:`ComparisonInterface->setParameterIdentifier`
* :php:`ComparisonInterface->getParameterIdentifier`


Impact
======

Calling any of the methods above will result in a fatal PHP error.


Affected Installations
======================

Any TYPO3 installation using custom logic inside Extbase's own Persistence layer within `Typo3DbBackend` or `Typo3DbQueryParser`.


Migration
=========

Remove the functionality and just use :php:`Typo3DbQueryParser->parseQuery()`.

.. index:: Database, PHP-API, ext:extbase
