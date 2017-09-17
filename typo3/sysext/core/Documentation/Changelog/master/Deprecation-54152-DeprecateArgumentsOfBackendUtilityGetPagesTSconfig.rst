.. include:: ../../Includes.txt

=============================================================================
Deprecation: #54152 - Deprecate arguments of BackendUtility::getPagesTSconfig
=============================================================================

See :issue:`54152`

Description
===========

`BackendUtility::getPagesTSconfig($id, $rootLine = null, $returnPartArray = false)` allowed the following arguments:

* `$id`: This argument was and still is required. It's the id of the page the TSconfig is fetched for
* `$rootLine`: This argument was optional and allowed to use that method with a custom rootline. That argument is deprecated now.
* `$returnPartArray`: This argument was optional and allowed to return the TSconfig non parsed. That argument is deprecated now.


Impact
======

Calling `BackendUtility::getPagesTSconfig` with `$rootline` and/or `$returnPartArray` being different than their default value, will write a deprecation log entry and will stop working in TYPO3 v10.


Affected Installations
======================

All installations that call `BackendUtility::getPagesTSconfig` with `$rootline` and/or `$returnPartArray` being different than their default value.


Migration
=========

Calling `BackendUtility::getPagesTSconfig` with just the `id` argument still behaves the way it does. It's the most common use case and there's no migraton needed.

If you called `BackendUtility::getPagesTSconfig` with `$returnPartArray` being `true` in the past, you should now call `BackendUtility::getRawPagesTSconfig`. You will get the non parsed TSconfig, just like before.

If you called `BackendUtility::getPagesTSconfig` providing a custom rootline via `$rootline` in the past, you should now call `BackendUtility::getRawPagesTSconfig($id, $rootLine = null)` with your custom rootline and parse the returned TSconfig yourself, just like `BackendUtility::getPagesTSconfig` does.

.. index:: Backend, TSConfig, NotScanned
