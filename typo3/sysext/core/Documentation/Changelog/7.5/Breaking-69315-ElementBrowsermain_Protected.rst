
.. include:: ../../Includes.txt

===================================================
Breaking: #69315 - ElementBrowser::main_* protected
===================================================

See :issue:`69315`

Description
===========

The `ElementBrowser::main_*` methods have been marked protected as the new `render` method is the main entry point to the class.
Additionally the public member `ElementBrowserController::mode` has been protected as well.

The `ElementBrowserController::content` member and the `ElementBrowserController::printContent()` method have been removed.

Impact
======

Any code calling the protected or removed methods or using the protected member will fail with a fatal error.
Any code using the removed member will receive only an empty value. (PHP fallback for non-existing class members)

Affected Installations
======================

Any installation using third party code calling the mentioned methods or member.


Migration
=========

Ensure the intended mode is passed in via the `mode` GET-parameter and call the new `ElementBrowser::render` method.


.. index:: PHP-API, Backend
