.. include:: /Includes.rst.txt

.. _changelog-Feature-93023-IntroduceUserSessionAndUserSessionManager:

==============================================================
Feature: #93023 - Introduce UserSession and UserSessionManager
==============================================================

See :issue:`93023`

Description
===========

As described in :ref:`changelog-Deprecation-93023-ReworkedSessionHandling`
the whole session handling in the TYPO3 Core was restructured by moving it
out of the user authentication objects into dedicated classes, namely
:php:`UserSession` and :php:`UserSessionManager`.

The :php:`UserSession` object contains of all necessary information
regarding a users session, for website visitors with session data (e.g.
shopping basket for anonymous / not-logged-in users), for frontend users as well as
authenticated backend users. These are for example the session id,
the session data, if a session was updated, if the session is anonymous,
or if it is marked permanent and so on. This replaces the so called
:php:`sessionRecord` which was an :php:`array` used in the user authentication objects.

This means, there is now a proper object which can be used to change and
retrieve information in an object-oriented way. It also features a
:php:`toArray()` function to obtain these information in the "old" format.

Using the static factory methods :php:`createFromRecord()` and
:php:`createNonFixated()` one can easily create a new session object.

Public Methods within `UserSession`
-----------------------------------

+---------------------+-------------+-----------------------------------------------------------------------------------+
| Method              | Return type | Description                                                                       |
+=====================+=============+===================================================================================+
| getIdentifier()     | String      | Returns the session id. This is the :php:`ses_id` respectively the                |
|                     |             | :php:`AbstractUserAuthentication->id`.                                            |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| getUserId()         | Int or NULL | Returns the user id the session belongs to. Can also return `0` or NULL           |
|                     |             | which indicates an anonymous session. This is the :php:`ses_userid`.              |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| getLastUpdated()    | Int         | Returns the timestamp of the last session data update. This is the                |
|                     |             | :php:`ses_tstamp`.                                                                |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| set($key, $value)   | Void        | Set or update session data value for a given key. It's also internally used       |
|                     |             | if calling :php:`AbstractUserAuthentication->setSessionData()`.                   |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| get($key)           | Mixed       | Returns the session data for the given key or NULL if the key does not            |
|                     |             | exist. It's internally used if calling                                            |
|                     |             | :php:`AbstractUserAuthentication->getSessionData()`.                              |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| getData()           | Array       | Returns the whole data array.                                                     |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| hasData()           | Bool        | Checks whether the session has some data assigned.                                |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| overrideData($data) | Void        | Overrides the whole data array. Can also be used to unset the array. This         |
|                     |             | also sets the :php:`$wasUpdated` pointer to :php:`TRUE`                           |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| dataWasUpdated()    | Bool        | Checks whether the session data has been updated.                                 |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| isAnonymous()       | Bool        | Check if the user session is an anonymous one. This means, the session does       |
|                     |             | not belong to a logged-in user.                                                   |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| getIpLock()         | string      | Returns the ipLock state of the session                                           |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| isNew()             | Bool        | Checks whether the session is new.                                                |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| isPermanent()       | Bool        | Checks whether the session was marked as permanent on creation.                   |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| needsUpdate()       | Bool        | Checks whether the session has to be updated.                                     |
+---------------------+-------------+-----------------------------------------------------------------------------------+
| toArray()           | Array       | Returns the session and its data as array in the old :php:`sessionRecord` format. |
+---------------------+-------------+-----------------------------------------------------------------------------------+

It should however be always considered to use the :php:`UserSessionManager`
for creating new sessions since this manager acts as the main factory for user
sessions and handles all necessary tasks like fetching, evaluating
and persisting them. Effectively encapsulating all calls to the
:php:`SessionManager` which is used for the Session Backend.

The :php:`UserSessionManager` can be retrieved using it's static factory
method :php:`create()`.

As already mentioned you can then use the :php:`UserSessionManager` to work
with user sessions. A couple of public methods are available.

Public Methods within `UserSessionManager`
------------------------------------------

+---------------------------------------------------------------+-----------------------------------------------------------------------+
| Method                                                        | Description                                                           |
+===============================================================+=======================================================================+
| createFromRequestOrAnonymous($request, $cookieName)           | Creates and returns a session from the given request. If the given    |
|                                                               | :php:`cookieName` can not be obtained from the request an anonymous   |
|                                                               | session will be returned.                                             |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| createFromGlobalCookieOrAnonymous($cookieName)                | Creates and returns a session from a global cookie (:php:`$_COOKIE`). |
|                                                               | If no cookie can be found for the given name, an anonymous session    |
|                                                               | will be returned.                                                     |
|                                                               | It is recommended to use the PSR-7 Request based method instead.      |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| createAnonymousSession()                                      | Creates and returns an anonymous session object (not persisted).      |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| createSessionFromStorage($sessionId)                          | Creates and returns a new session object for a given session id.      |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| hasExpired($session)                                          | Checks whether a given user session object has expired.               |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| willExpire($session, $gracePeriod)                            | Checks whether a given user session will expire within the given      |
|                                                               | grace period.                                                         |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| fixateAnonymousSession($session, $isPermanent)                | Persists an anonymous session without a user logged in, in order to   |
|                                                               | store session data between requests.                                  |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| elevateToFixatedUserSession($session, $userId, $isPermanent)  | Removes existing entries, creates and returns a new user session      |
|                                                               | object. See regenerateSession() below.                                |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| regenerateSession($sessionId, $sessionRecord, $anonymous)     | Regenerates the given session. This method should be used whenever a  |
|                                                               | user proceeds to a higher authorization level, e.g. when an           |
|                                                               | anonymous session is now authenticated.                               |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| updateSessionTimestamp($session)                              | Updates the session timestamp for the given user session if the       |
|                                                               | session is marked as "needs update" (which means the current          |
|                                                               | timestamp is greater than "last updated + a specified gracetime").    |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| isSessionPersisted($session)                                  | Checks whether a given session is already persisted.                  |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| removeSession($session)                                       | Removes a given session from the session backend.                     |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| updateSession($session)                                       | Updates the session data + timestamp in the session backend.          |
+---------------------------------------------------------------+-----------------------------------------------------------------------+
| collectGarbage(garbageCollectionProbability)                  | Calls the session backends :php:`collectGarbage()` method.            |
+---------------------------------------------------------------+-----------------------------------------------------------------------+


Impact
======

The user authentication classes such as
:php:`BackendUserAuthentication`, :php:`FrontendUserAuthentication` and their abstract parent class
:php:`AbstractUserAuthentication`, do now not longer
directly manage the corresponding user session. Therefore these objects
do not longer include the session data and do not know about the specific
session backend implementation.

The main benefit is the centralized handling of sessions via the new
:php:`UserSession` object which contains of all relevant information
and the :php:`UserSessionManager`. Latter should be used as factory
to create new sessions for various use-cases.

Related
=======

- :ref:`changelog-Breaking-93023-ReworkedSessionHandling`
- :ref:`changelog-Deprecation-93023-ReworkedSessionHandling`

.. index:: PHP-API, ext:core
