
.. include:: ../../Includes.txt

============================================
Breaking: #65939 - Backend Login Refactoring
============================================

See :issue:`65939`

Description
===========

For the refactoring of the backend login we introduce Fluid as template engine and remove the old marker template.

It was necessary to remove the signal `LoginController::SIGNAL_RenderLoginForm` which will no longer be emitted.

Additionally the following methods of `LoginController` have been removed:

* `LoginController::makeLoginBoxImage`
* `LoginController::wrapLoginForm`
* `LoginController::makeLoginNews`
* `LoginController::makeLoginForm`
* `LoginController::makeLogoutForm`


Impact
======

The mentioned methods are no longer available and a fatal error will be triggered if used.


Affected installations
======================

All installations which make use of the `LoginController::SIGNAL_RenderLoginForm` signal or use the removed methods:

* `LoginController::makeLoginBoxImage`
* `LoginController::wrapLoginForm`
* `LoginController::makeLoginNews`
* `LoginController::makeLoginForm`
* `LoginController::makeLogoutForm`


Migration
=========

Use the introduced Fluid view to adapt the login screen to your demands.


.. index:: PHP-API, Backend
