.. include:: /Includes.rst.txt

===============================================
Deprecation: #94272 - Application->run callback
===============================================

See :issue:`94272`

Description
===========

Since the introduction of the :php:`\TYPO3\CMS\Core\Core\ApplicationInterface`
in :issue:`67808` (TYPO3 v7), which serves as a wrapper for setting up the bootstrap and
calling the request, it was possible to run either the console, the frontend
or the backend by calling :php:`run()` on the corresponding Application class.

The :php:`run()` method also featured the possibility to provide a :php:`callback`
as first argument. This was mainly introduced, since no proper solution for
sub requests existed at that time. Since :issue:`83725`, the callback is not
longer necessary as such functionality can be handled by a PSR-15
middleware.

Therefore, the :php:`$execute` argument of :php:`ApplicationInterface->run()`
has been deprecated and will be removed in v12.

Impact
======

Calling :php:`\TYPO3\CMS\Core\Core\ApplicationInterface->run()` with the
first argument :php:`$execute` set, triggers a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

All installations which manually call
:php:`\TYPO3\CMS\Core\Core\ApplicationInterface->run()`,
while providing a callback as first argument. The extension scanner
will find those usages as weak match.

Migration
=========

Instances with extensions calling
:php:`\TYPO3\CMS\Core\Core\ApplicationInterface->run()` with a callback
as first argument need to be adapted. If possible use PSR-15 middlewares
instead.

Console commands do not feature PSR-15 middlewares. Therefore, the callback
has to be replaced by separate chained post-processing commands.

.. index:: CLI, PHP-API, FullyScanned, ext:core
