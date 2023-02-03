.. include:: /Includes.rst.txt

.. _deprecation-99638-1674127318:

===================================================
Deprecation: #99638 - Environment::getBackendPath()
===================================================

See :issue:`99638`

Description
===========

TYPO3's backend path `/typo3` is currently resolved statically in
:php:`Environment::getBackendPath()` to return the full path to the
backend entrypoint.

However, as TYPO3's code base is evolving, the usages to the hardcoded path have been reduced
and the functionality is now migrated into a new :php:`BackendEntryPointResolver` class,
which allows for dynamically adjusting the entry point in the future.


Impact
======

Calling the method will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations with custom extensions using the method in PHP code.


Migration
=========

Check for the extension scanner, and see if the code is necessary, or if any alternative,
such as the :php:`\TYPO3\CMS\Core\Routing\BackendEntryPointResolver`
or :php:`\TYPO3\CMS\Core\Http\NormalizedParams` might be better
suited as third-party extensions should not rely on the hard-coded paths to resources from
:file:`typo3/*` anymore.

.. index:: PHP-API, FullyScanned, ext:core
