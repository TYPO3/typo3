.. include:: /Includes.rst.txt

========================================================================================
Breaking: #70316 - AbstractUserAuthentication properties and methods dropped and changed
========================================================================================

See :issue:`70316`

Description
===========

The property :php:`AbstractUserAuthentication::session_table` has been dropped.
The property :php:`FrontendUserAuthentication::sessionDataTimestamp` has been dropped.
The property :php:`FrontendUserAuthentication::sesData` has been moved to :php:`AbstractUserAuthentication::sessionData`
and is protected now.

The method :php:`FrontendUserAuthentication::fetchSessionData()` has been removed and its
logic has been integrated into :php:`AbstractUserAuthentication::fetchUserSession()`.


Impact
======

Accessing one of these properties will raise a PHP warning.
Calling the method :php:`fetchSessionData()` will cause a PHP fatal error.

Moreover it is not possible anymore to use the getData function from within TypoScript
to access session data. This functionality is replaced by a new API. (see :issue:`80154`)

Affected Installations
======================

All extensions accessing these properties will most likely not work properly anymore.
Extensions accessing the removed method will not work at all.


Migration
=========

Use configuration from :php:`DatabaseSessionBackend` located in
:php:`$GLOBALS['TYPO3_CONF_VARS]['SYS']['session'][/* Session Identifier */]['table']` or use
:php:`AbstractUserAuthentication::loginType` to distinguish between FE or BE login types.

Session data can be manipulated with the following methods in :php:`AbstractUserAuthentication`

* :php:`getSessionData()`
* :php:`setSessionData()`


Calls to :php:`FrontendUserAuthentication::fetchSessionData()` can safely be removed.

.. index:: PHP-API
