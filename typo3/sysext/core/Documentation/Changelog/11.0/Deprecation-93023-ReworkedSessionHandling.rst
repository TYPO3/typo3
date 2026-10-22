.. include:: /Includes.rst.txt

.. _changelog-Deprecation-93023-ReworkedSessionHandling:

===============================================
Deprecation: #93023 - Reworked session handling
===============================================

See :issue:`93023`

Description
===========

As described in :ref:`changelog-Breaking-93023-ReworkedSessionHandling`
the whole session handling in the TYPO3 Core was reworked by moving it
out of the user authentication classes.

Therefore some properties and methods within :php-short:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication`
and its subclasses have been marked as deprecated:

*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->createSessionId()`
*  :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->fetchUserSession()`


Impact
======

Accessing :php:`id` or calling :php:`isExistingSessionRecord()`
respectively :php:`getSessionId()` will trigger a PHP :php:`E_USER_DEPRECATED` error.

Calling :php:`createSessionId()` or :php:`fetchUserSession()` will not
trigger a PHP :php:`E_USER_DEPRECATED` error but will still be reported by the extension
scanner.


Affected Installations
======================

All TYPO3 installations with custom extensions directly accessing or calling
the deprecated properties or methods.


Migration
=========

Creating a new session is now handled by the :php-short:`\TYPO3\CMS\Core\Session\UserSessionManager`.
Therefore the identifier is set internally on creation of a new session
and should not longer be called directly. Use e.g.
:php-short:`\TYPO3\CMS\Core\Session\UserSessionManager->createAnonymousSession()` or
:php-short:`\TYPO3\CMS\Core\Session\UserSessionManager->regenerateSession()` to create a new session
and then access :php-short:`\TYPO3\CMS\Core\Session\UserSession->getIdentifier()`.

Use :php-short:`\TYPO3\CMS\Core\Session\UserSessionManager->isSessionPersisted()` instead of
:php:`isExistingSessionRecord()` to check if a session is already persisted.

Use the :php-short:`\TYPO3\CMS\Core\Session\UserSessionManager` to create a new session and then directly
access the :php-short:`\TYPO3\CMS\Core\Session\UserSession` instead of calling :php:`fetchUserSession()`.

Use :php-short:`\TYPO3\CMS\Core\Session\UserSession->getIdentifier()` instead of :php:`getSessionId()`. To
access this information from an user authentication object, call
:php:`$userAuthentication->getSession()->getIdentifier()`.

Related
=======

*  :ref:`changelog-Breaking-93023-ReworkedSessionHandling`
*  :ref:`changelog-Feature-93023-IntroduceUserSessionAndUserSessionManager`


.. index:: PHP-API, FullyScanned, ext:core
