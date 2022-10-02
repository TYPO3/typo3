.. include:: /Includes.rst.txt

.. _deprecation-97531:

=================================================================================
Deprecation: #97531 - Context-related methods within TypoScriptFrontendController
=================================================================================

See :issue:`97531`

Description
===========

One of the main classes within TYPO3 Frontend —
:php:`TypoScriptFrontendController` a.k.a. :php:`$GLOBALS['TSFE']` —
had various short-hand functionality to access the Context API.

This is mainly for historical reasons, before the Context API
was introduced in TYPO3 v9.

For this reason, the related methods have been marked as deprecated:

* :php:`initUserGroups()`
* :php:`isUserOrGroupSet()`
* :php:`isBackendUserLoggedIn()`
* :php:`doWorkspacePreview()`
* :php:`whichWorkspace()`

Impact
======

Calling the methods directly will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

TYPO3 installations with custom extensions using one of the methods.
The extension scanner will report any usage as weak match.

Migration
=========

Migrate towards the Context API instead:

..  code-block:: php

    // Is this request within a Workspace currently
    $context->getPropertyFromAspect('workspace', 'isOffline', false);
    // Is a frontend user logged in
    $context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false);

.. index:: Frontend, FullyScanned, ext:frontend
