.. include:: ../../Includes.txt

========================================================================
Deprecation: #95320 - Various method arguments in Authentication objects
========================================================================

See :issue:`95320`

Description
===========

The following methods have their first argument deprecated:

* :php:`AbstractUserAuthentication->writeUC()`
* :php:`AbstractUserAuthentication->unpack_uc()`
* :php:`BackendUserAuthentication->backendCheckLogin()`

The following method has its third argument deprecated:

* :php:`BackendUserAuthentication->isInWebMount()`


Impact
======

Calling these methods with an explicit argument of the deprecated
arguments given will trigger a PHP deprecation warning.


Affected Installations
======================

TYPO3 installations with custom extensions calling these methods
with the deprecated arguments which is highly unlikely.


Migration
=========

Call :php:`AbstractUserAuthentication->writeUC()` without an
method argument. If you need to explicitly set a custom UC value
which is not :php:`AbstractUserAuthentication->uc`, you can set this via :php:`AbstractUserAuthentication->uc = $myValue;` in the
line before.

Call :php:`AbstractUserAuthentication->unpack_uc()` without an
method argument. If you need to explicitly set a custom UC value
which is not :php:`AbstractUserAuthentication->uc`, you can set this via :php:`AbstractUserAuthentication->uc = $myValue;` in the
line before.

Call :php:`BackendUserAuthentication->backendCheckLogin()` without
an argument but wrap this call in a :php:`try {} catch (\Throwable $e)` if you need the old behavior and want to avoid a deprecation
message.

Call :php:`BackendUserAuthentication->isInWebMount()` without the
third argument and check for the return value of being `null`
which is the equivalent of the expected `RuntimeException` being
thrown when the third argument was set to `true`.

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:core