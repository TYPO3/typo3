.. include:: ../../Includes.txt

======================================================================
Important: #83724 - API and behavior change in request handler classes
======================================================================

See :issue:`83724`

Description
===========

In preparation for a better PSR-7 and a new PSR-15 integration the internal request handler classes have been changed:

* All methods gained strict argument type and return type declarations.
* Instead of calling :php:`HttpUtility::redirect()` a :php:`RedirectResponse` is returned.
* Instead of returning :php:`null` a :php:`NullResponse` is returned.

Impact
======

Extending one of the core request handlers without adding type declarations (to overloaded methods),
will trigger a PHP fatal error.

Affected Installations
======================

All 3rd party extensions extending one of the core request handlers.

.. index:: PHP-API, NotScanned
