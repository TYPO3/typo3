==================================================================================================
Breaking: #77390 - Expected return type of hook getResultRows_SQLpointer in Indexed Search changed
==================================================================================================

Description
===========

As part of the migration of the core code to use Doctrine the expected return value of the hook
``getResultRows_SQLpointer`` in Indexed Search has been changed.

It is required that :php:``\Doctrine\DBAL\Driver\Statement`` objects are returned instead of the
previous types :php:``bool`` or :php:``\mysqli_result``.


Impact
======

3rd party extensions implementing the hook :php:``getResultRows_SQLpointer`` need to provide the
correct return type, otherwise fatal errors will occur when processing the search results.


Affected Installations
======================

Installations using 3rd party extensions that implement the hook :php:``getResultRows_SQLpointer``
for Indexed Search.


Migration
=========

Migrate the implementation of the hook to provide the expected Doctrine Statement object.
