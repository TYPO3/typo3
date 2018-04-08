.. include:: ../../Includes.txt

======================================================
Deprecation: #83736 - Deprecated globals TYPO3_REQUEST
======================================================

See :issue:`83736`

Description
===========

The :php:`ServerRequestInterface $request` is available as :php:`$GLOBALS['TYPO3_REQUEST']`
in HTTP requests. This global is available in a transition phase only and will be removed later.

Extension authors are discouraged to use that global and the extension scanner marks any usage as deprecated.


Impact
======

Accessing :php:`$GLOBALS['TYPO3_REQUEST']` is discouraged.


Affected Installations
======================

Instances with extensions using :php:`$GLOBALS['TYPO3_REQUEST']`.


Migration
=========

Controller classes for HTTP requests retrieve the request object. Access should either be done from within controllers
or by passing :php:`$request` to service classes that need to access values from :php:`$request`.

.. index:: PHP-API, FullyScanned
