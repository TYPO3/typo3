.. include:: /Includes.rst.txt

===================================================================
Deprecation: #82669 - Streamline Backend route path inconsistencies
===================================================================

See :issue:`82669`

Description
===========

When registering Backend modules, it is already possible to define a custom route path, via the :php:`path`
option in the module configuration section within :file:`ext_tables.php`.

Backend Routes to modules without path configurations are now named using the pattern
"/module/<main-module-name>/<submodule-name>" e.g. `/module/web/ts`.

Old route paths for modules are called "/web/ts/" will still work but are discouraged to use.


Impact
======

Creating modules without a defined "path" option will now have two path routes available to be
resolved, whereas the old path will be removed in TYPO3 v10.0.


Affected Installations
======================

Any installation using TYPO3 Backend Links via :php:`TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromRoutePath()` in custom extensions.


Migration
=========

TYPO3 Backend Links via :php:`TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromRoutePath()` should be used with the new module name as
described above.

.. index:: Backend, NotScanned, ext:backend
