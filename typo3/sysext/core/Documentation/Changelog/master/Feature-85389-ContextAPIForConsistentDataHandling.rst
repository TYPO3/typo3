.. include:: ../../Includes.txt

==========================================================
Feature: #85389 - Context API for consistent data handling
==========================================================

See :issue:`85389`

Description
===========

A new Context API is introduced, which encapsulates various information for data retrieval (e.g. inside
the database) and analysis of current permissions and caching information.

Previously, various information was distributed inside globally accessible objects (:php:`$TSFE` or :php:`$BE_USER`)
like the current workspace ID or if a frontend or backend user is authenticated. Having a global object
available was also dependent on the current request type (frontend or backend), instead of having
one consistent place where all this data is located.

The context is currently instantiated at the very beginning of each TYPO3 entry point, keeping track
of the current time (formally known as :php:`$GLOBALS['EXEC_TIME']`, and if a user is logged in,
and which workspace is currently accessed.

This information is separated in so-called "Aspects", being responsible for a certain area:

- VisibilityAspect, holding information if hidden/deleted records should be fetched from the database
- DateTimeAspect, keeping the current date as immutable datetime object
- UserAspect, holding frontend/backend user IDs, usernames and usergroups
- WorkspaceAspect, holding the currently visible workspace (default to "0"/ live)

Further aspects related to the current request (e.g. selected language information and fallback strategy)
will follow, but extensions can add their own Aspects as well, as they only need to implement the AspectInterface.

The Context object is used as a Singleton, available via :php:`GeneralUtility::makeInstance(Context::class)`.

Adding or replacing an aspect has implications on the whole further request. The recommended way on doing
so is done in a PSR-15 middleware. In the future (TYPO3 v10), the global context will have a "frozen" state
after all PSR-15 middlewares are run through, to ensure a consistent object throughout all renderings
within a backend.

However, if, for a certain retrieval part a custom context is needed, the necessary PHP classes, like
:php:`PageRepository` can receive a custom Context object. For this to work, a new Context object can be
created via :php:`new Context()`  and or cloned from the master context via
:php:`$myContext = clone GeneralUtility::makeInstance(Context::class);` to keep all existing aspects but only to
override a certain aspect locally.

A huge benefit when using the Context API is a strong decoupling of various architectural failures within
TYPO3 Core, which are now "Context aware" and not depending if a certain global object is available.

This will not unify the code quality, but also introduce a better standard, where hard intermingling within
Extbase, PageRepository and TypoScriptFrontendController can be found.

Impact
======

The new Context API replaces lots of places known for a very long time:

* DateTimeAspect replaces :php:`$GLOBALS['SIM_EXEC_TIME']` and :php:`$GLOBALS['EXEC_TIME']`
* VisibilityAspect replaces :php:`$GLOBALS['TSFE']->showHiddenPages` and :php:`$GLOBALS['TSFE']->showHiddenRecords`
* WorkspaceAspect replaces :php:`$GLOBALS['BE_USER']->workspace`
* UserAspect replaces various calls and checks on :php:`$GLOBALS['BE_USER']` and :php:`$GLOBALS['TSFE']->fe_user`
options when only some information is needed.


TYPO3 Core comes with the following Aspects within the global context:

* date
* frontend.user
* backend.user
* workspace
* visibility

Usage
=====

As for TYPO3 v9, the old properties can be used the same way as before, but will throw a deprecation warning.

It is recommended to read data from the current global Context for custom extensions:

.. code-block:: php

    $context = GeneralUtility::makeInstance(Context::class);

    // Reading the current data instead of $GLOBALS['EXEC_TIME']
    $currentTimestamp = $context->getPropertyFromAspect('date', 'timestamp');

    // Checking if a user is logged in
    $userIsLoggedIn = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');


Additionally, if custom DB queries need to be made, this can be solved via cloning the Context API

.. code-block:: php

    // Current global context
    $context = GeneralUtility::makeInstance(Context::class);
    $localContextWithoutFrontendUser = clone $context;
    $localContextWithoutFrontendUser->setAspect('frontend.user', GeneralUtility::makeInstance(UserAspect::class, null);

    // Fetch a page which is publically available, but not accessible when logged in
    $sysPage = GeneralUtility::makeInstance(PageRepository::class, $localContextWithoutFrontendUser);
    $pageRow = $sysPage->getPage($pageId);


Further development
===================

There will be additional aspects that will be introduced in TYPO3 Core. Also see PSR-15 middlewares shipped with TYPO3
Frontend or Backend to see how aspects can be modified.

Aspects eventually will become the successor of Database Restrictions, as they contain all information
necessary to restrict a database query.

.. index:: PHP-API, ext:core
