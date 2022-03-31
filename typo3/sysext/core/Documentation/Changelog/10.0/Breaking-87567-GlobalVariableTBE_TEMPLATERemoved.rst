.. include:: /Includes.rst.txt

========================================================
Breaking: #87567 - Global variable $TBE_TEMPLATE removed
========================================================

See :issue:`87567`

Description
===========

The global variable :php:`$GLOBALS[TBE_TEMPLATE]` used in TYPO3 Backend which was available
for legacy reasons for old backend modules as an instance of :php:`DocumentTemplate` a.k.a. `alt_doc`
has been removed.

The according PSR-15 middleware, which was marked as internal, is also removed.


Impact
======

Calling any method or property on :php:`$GLOBALS[TBE_TEMPLATE]` will trigger a PHP :php:`E_ERROR` error.


Affected Installations
======================

TYPO3 installations with older extensions using the global variable.


Migration
=========

Instantiate the :php:`DocumentTemplate` class directly in the controller of the module, or migrate
to :php:`ModuleTemplate` which is available since TYPO3 v7.

.. index:: PHP-API, FullyScanned, ext:backend
