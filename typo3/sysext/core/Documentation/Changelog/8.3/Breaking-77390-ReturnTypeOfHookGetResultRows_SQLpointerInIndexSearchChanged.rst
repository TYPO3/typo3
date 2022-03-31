
.. include:: /Includes.rst.txt

==================================================================================================
Breaking: #77390 - Expected return type of hook getResultRows_SQLpointer in Indexed Search changed
==================================================================================================

See :issue:`77390`

Description
===========

As part of migrating the core code to use Doctrine DBAL the expected return value of the hook
:php:`getResultRows_SQLpointer` in EXT:indexed_search has changed.

It is required that :php:`\Doctrine\DBAL\Driver\Statement` objects are returned instead of the
previous types :php:`bool` or :php:`\mysqli_result`.


Impact
======

3rd party extensions implementing the hook :php:`getResultRows_SQLpointer` need to provide the
correct return type, otherwise fatal errors will occur when processing the search results.


Affected Installations
======================

Installations using 3rd party extensions that implement the hook :php:`getResultRows_SQLpointer`
for Indexed Search.


Migration
=========

Migrate the implementation of the hook to provide the expected Doctrine Statement object.

.. index:: Database, ext:indexed_search, PHP-API
