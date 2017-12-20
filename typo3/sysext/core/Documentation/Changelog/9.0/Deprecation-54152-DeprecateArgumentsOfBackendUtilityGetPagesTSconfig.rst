.. include:: ../../Includes.txt

=============================================================================
Deprecation: #54152 - Deprecate arguments of BackendUtility::getPagesTSconfig
=============================================================================

See :issue:`54152`

Description
===========

:php:`BackendUtility::getPagesTSconfig($id, $rootLine = null, $returnPartArray = false)` allowed the following arguments:

* :php:`$id`: This argument was and still is required. It's the id of the page the TSconfig is fetched for
* :php:`$rootLine`: This argument was optional and allowed to use that method with a custom rootline. That argument is deprecated now.
* :php:`$returnPartArray`: This argument was optional and allowed to return the TSconfig non parsed. That argument is deprecated now.


Impact
======

Calling :php:`BackendUtility::getPagesTSconfig` with `$rootline` and/or `$returnPartArray` being different than their
default value, will write a deprecation log entry and will stop working in TYPO3 v10.


Affected Installations
======================

All installations that call :php:`BackendUtility::getPagesTSconfig` with :php:`$rootline` and/or :php:`$returnPartArray` being
different than their default value.


Migration
=========

Calling :php:`BackendUtility::getPagesTSconfig` with just the :php:`id` argument still behaves the way it does.
It's the most common use case and there's no migraton needed.

If you called :php:`BackendUtility::getPagesTSconfig` with :php:`$returnPartArray` being :php:`true` in the past,
you should now call `BackendUtility::getRawPagesTSconfig`. You will get the non parsed TSconfig, just like before.

If you called :php:`BackendUtility::getPagesTSconfig` providing a custom rootline via :php:`$rootline` in the past,
you should now call :php:`BackendUtility::getRawPagesTSconfig($id, $rootLine = null)` with your custom rootline
and parse the returned TSconfig yourself, just like :php:`BackendUtility::getPagesTSconfig` does.

.. index:: Backend, TSConfig, PHP-API, NotScanned
