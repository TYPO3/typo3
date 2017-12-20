
.. include:: ../../Includes.txt

===========================================================
Breaking: #68186 - Adjusted and removed methods in eID area
===========================================================

See :issue:`68186`

Description
===========

eID scripts now use Request and Response objects to retrieve and output data.

Due to adjustments of the Core eID scripts a few methods have been adjusted.

`ExtDirectEidController::routeAction` is now protected and has a changed signature.

The following methods have been removed:

* `ExtDirectEidController::actionIsAllowed()`
* `ExtDirectEidController::render()`
* `EidUtility::isEidRequest()`
* `EidUtility::getEidScriptPath()`

Additionally calling an non-existent eID key will result in a fatal error.

Impact
======

All third party code using those methods will cause a fatal PHP error.


Affected Installations
======================

All installations using third party code accessing one of the adjusted (or removed) methods.


Migration
=========

No replacement for the mentioned methods is provided. Consider migrating your eID scripts to the new PSR-7 compliant model.


.. index:: PHP-API, Frontend, Backend
