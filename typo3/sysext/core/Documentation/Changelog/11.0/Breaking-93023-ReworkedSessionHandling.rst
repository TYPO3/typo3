.. include:: /Includes.rst.txt

.. _changelog-Breaking-93023-ReworkedSessionHandling:

============================================
Breaking: #93023 - Reworked session handling
============================================

See :issue:`93023`

Description
===========

The overall session handling withing TYPO3 Core has been overhauled. This was
done to separate the actual User object, the Authentication process and the
session handling.

The main result of this refactoring is the user authentication objects such as
:php:`BackendUserAuthentication` and :php:`FrontendUserAuthentication`
do not longer contain the session data directly. Instead this is now encapsulated
in a :php:`UserSession` object which is handled by the new
:php:`UserSessionManager`.

Furthermore the user authentication objects internally do not longer know about
a specific session backend implementation, since this is also wrapped by the
:php:`UserSessionManager`. This also means it is not possible to create sessions
outside of the new session manager anymore.

For this purpose there are several changes within the user authentication
classes which are described below.

The array :php:`AbstractUserAuthentication->user` previously contained the logged-in
user record (from be_users / fe_users database table) AND the session record
prefixed via :php:`ses_*` array properties. This has been removed, to separate
the functionality. Instead, all session properties are placed inside the
:php:`UserSession` object, accessible via e.g. :php:`$GLOBALS[BE_USER]->getSession()`.

The following public properties within :php:`AbstractUserAuthentication` and
its subclasses have been removed:

*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->id`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->hash_length`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->sessionTimeout`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->gc_time`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->gc_probability`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->newSessionID`

The following public methods within :php:`AbstractUserAuthentication` and its
subclasses have been removed:

*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->getNewSessionRecord()`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->getSessionId()`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->isExistingSessionRecord()`

The following public property within :php:`AbstractUserAuthentication` has
changed their visibility to :php:`protected`:

*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->lifetime`

The following public methods within :php:`AbstractUserAuthentication` and its
subclasses have changed their return type:

*  :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->createUserSession()`
   now returns :php:`TYPO3\CMS\Core\Session\UserSession` and the first parameter
   :php:`$tempuser` is now type-hinted :php:`array`.

The following public properties within :php:`FrontendUserAuthentication` have
been removed:

*  :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->sesData_change`

The following database fields have been removed:

*  :sql:`be_sessions.ses_backuserid`
*  :sql:`fe_sessions.ses_anonymous`


Impact
======

Accessing a dropped property or calling a dropped method will raise a fatal PHP
error.

Accessing a property whose visibility was changed to :php:`protected` will also
raise a fatal PHP error if no deprecation functionality is in place. See
:ref:`changelog-Deprecation-93023-ReworkedSessionHandling` for more information.

Calling a method whose parameter signature changed with a wrong type will raise
a PHP type error.

Directly querying a dropped database field will raise a doctrine dbal exception.


Affected Installations
======================

All TYPO3 installations with custom extensions directly accessing or calling
the changed properties or methods.


Migration
=========

The :php:`sessionTimeout` property is now set internally to the value of the
global configuration :php:`(int)$GLOBALS['TYPO3_CONF_VARS'][$loginType]['sessionTimeout'];`.
This value can also be set dynamically in e.g. a middleware if needed. Because
it is only needed for User Session objects, it is now resolved within
the :php:`UserSessionManager` object.

:php:`gc_time` is still set to `86400` per default and will be overwritten
with the value from :php:`sessionTimeout` (see above) if greater than `0`.

Since it's very unlikely that :php:`gc_probability` will be changed in
custom code there is no direct way to set a custom value anymore. It's now
directly set to `1` in the consuming method
:php:`UserSessionManager->collectGarbage()`. If your custom code however rely
on another value you can call :php:`UserSessionManager->collectGarbage()`
in your code by providing a custom value as first argument for
:php:`$garbageCollectionProbability`.

The property :php:`newSessionID` is now available in :php:`UserSession->isNew()`.

Use the :php:`UserSessionManager->elevateToFixatedUserSession()` as a
replacement for :php:`getNewSessionRecord()` to migrate an anonymous session
to a user-bound session.

If you directly call :php:`createUserSession()` in your custom code make sure
to pass an :php:`array` as argument for :php:`$tempuser` and to handle the
returned :php:`UserSession` object accordingly.

Use :php:`UserSession->dataWasUpdated()` as replacement for
:php:`FrontendUserAuthentication->sesData_change`.

The :sql:`be_sessions.ses_backuserid` field was migrated into the session data
and is now available inside :php:`UserSession->data`, which can be accessed
using :php:`get()` or :php:`getAll()`. Since this value is only present in
"switch-user" sessions, it's very unlikely that custom code is directly
accessing it. If you however perform database queries using this field,
then they have to be adjusted accordingly.

The :sql:`fe_sessions.ses_anonymous` field is not needed anymore since this
information can also be obtained using the :sql:`fe_sessions.ses_userid` field.
If it's lower or equals `0` the session is an anonymous one. If you perform
database queries using this field, change it to use use :sql:`ses_userid` instead.
If a session is anonymous can furthermore be checked using
:php:`UserSession->isAnonymous()`.

Related
=======

-  :ref:`changelog-Deprecation-93023-ReworkedSessionHandling`
-  :ref:`changelog-Feature-93023-IntroduceUserSessionAndUserSessionManager`

.. index:: PHP-API, FullyScanned, ext:core
