.. include:: /Includes.rst.txt

=======================================================================================
Breaking: #92801 - Removed "Failed Login" functionality from User Authentication object
=======================================================================================

See :issue:`92801`

Description
===========

The functionality to send an email to a defined sender was previously hard-coded
into the API class :php:`AbstractUserAuthentication` and activated specifically for
Backend Users via the option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr']`.

With some custom implementation it was also possible to use a hook to
enable this for frontend users, but the API was not clean.

The backend-user specific logic is now extracted into a hook, so it is possible
to replace this functionality with a custom notification API.

For this reason, the following public properties and methods within
:php:`AbstractUserAuthentication` and its subclasses have been removed:

* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->warningEmail`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->warningPeriod`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->warningMax`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->checkLogFailures()`

Impact
======

Using one of the public properties in custom PHP will trigger a PHP Warning.

Calling the public PHP method will result in a fatal PHP error.


Affected Installations
======================

TYPO3 installations with third-party extensions and custom PHP code that is
related to failed login notifications, and rely on the existing login
notification code.


Migration
=========

As the properties were public, they made it possible to override the
warningMax / warningPeriod values via hooks and middlewares in PHP.

Instead it is recommended to override this functionality via a hook the same way
the new hook in EXT:backend is registered within PHP.

.. index:: Backend, PHP-API, FullyScanned, ext:core
