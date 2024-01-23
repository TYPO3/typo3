.. include:: /Includes.rst.txt

.. _breaking-102590-1701598566:

==============================================================
Breaking: #102590 - TSFE->generatePage_preProcessing() removed
==============================================================

See :issue:`102590`

Description
===========

Frontend-related method :php:`TypoScriptFrontendController->generatePage_preProcessing()`
has been removed without substitution.


Impact
======

Calling the methods will raise a fatal PHP error.


Affected installations
======================

There is little to no need for extensions to call or override this method and it
should have been marked as :php:`@internal` already. It was part of a removed
"safety net" when extensions did set :php:`TypoScriptFrontendController->no_cache`
to :php:`false` after it has been set to :php:`true` already, which is not allowed.


Migration
=========

No migration, do not call the method anymore.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
