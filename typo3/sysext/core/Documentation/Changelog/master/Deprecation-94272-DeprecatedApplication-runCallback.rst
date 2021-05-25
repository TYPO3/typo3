.. include:: ../../Includes.txt

==========================================================
Deprecation: #94272 - Deprecated Application->run callback
==========================================================

See :issue:`94272`

Description
===========

Since the introduction of the :php:`ApplicationInterface` in :issue:`67808`
(TYPO3 v7), which serves as a wrapper for setting up the bootstrap and
calling the request, it was possible to run either the console, the frontend
or the backend by calling :php:`run()` on the corresponding Application class.

The :php:`run()` method also featured the possibility to provide a :php:`callback`
as first argument. This was mainly introduced, because no proper solution for
subrequests existed at this time. Since :issue:`#83725`, the callback is not
longer necessary as such functionality can be handled by a PSR-15
middleware.

Therefore, the :php:`$execute` argument of :php:`ApplicationInterface->run()`
has been deprecated and will be removed in v12.

Impact
======

Calling :php:`ApplicationInterface->run()` with the first argument
:php:`$execute` set, will log a deprecation warning and the argument
will be removed in v12.

Affected Installations
======================

All installations which manually call :php:`ApplicationInterface->run()`,
while providing a :php:`callback` as first argument. The extension scanner
will find those usages as weak match.

Migration
=========

All places in custom extension code, calling this method with the first
argument set, which is rather unlikely, needs to be adapted. Therefore,
you can use PSR-15 middlewares instead. Since console commands do not
feature PSR-15 middlewares, you have to replace the :php:`callback` with
separate chained post-processing commands.

.. index:: CLI, PHP-API, FullyScanned, ext:core
