===========================================================
Breaking: #68186 - Adjusted and removed methods in eID area
===========================================================

Description
===========

eID scripts now use Request and Response objects to retrieve and output data.

Due to adjustments of the Core eID scripts a few methods have been adjusted.

``ExtDirectEidController::routeAction`` is now protected and has a changed signature.

The following methods are removed:

* ``ExtDirectEidController::actionIsAllowed``
* ``ExtDirectEidController::render``
* ``EidUtility::isEidRequest``
* ``EidUtility::getEidScriptPath``

Additionally every call to a not registered eID key will result in a fatal error.

Impact
======

All third party code using those methods will cause a fatal PHP error.


Affected Installations
======================

All installations using third party code accessing one of the adjusted (removed) methods.


Migration
=========

No replacement for the mentioned methods is provided. Consider migrating your eID script to the new PSR-7 compliant model.
