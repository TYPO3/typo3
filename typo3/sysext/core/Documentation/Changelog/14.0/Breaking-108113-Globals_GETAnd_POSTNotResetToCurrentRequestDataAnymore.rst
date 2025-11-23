..  include:: /Includes.rst.txt

..  _breaking-108113-1763081388:

====================================================================================
Breaking: #108113 - Globals _GET and _POST not reset to current Request data anymore
====================================================================================

See :issue:`108113`

Description
===========

The frontend and backend application chain roughly splits like this:

1.  Bootstrap
2.  Create Request object from globals
3.  Start application
4.  Run middleware chain
5.  Run RequestHandler to create a Response by calling controllers (backend) or
    :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`
    (frontend)

There was old compatibility code in :php-short:`\TYPO3\CMS\Core\Http\RequestHandler`
that reset the PHP global variables :php:`_GET`, :php:`_POST`,
:php:`HTTP_GET_VARS` and :php:`HTTP_POST_VARS` to values that may have been
written to their Request object counterparts by middlewares.

This backwards compatibility layer has been removed.

Additionally, in frontend rendering, the global variable
:php:`$GLOBALS['TYPO3_REQUEST']` is no longer populated within the
:php-short:`\TYPO3\CMS\Frontend\Middleware\PrepareTypoScriptFrontendRendering`
middleware. It is now set later in :php-short:`\TYPO3\CMS\Core\Http\RequestHandler`.
:php:`$GLOBALS['TYPO3_REQUEST']` itself is another compatibility layer that the
TYPO3 Core aims to phase out over time.

Impact
======

The impact is twofold:

*   Some TYPO3 Core middlewares manipulate the Request object's "GET"
    parameter list (:php:`$request->getQueryParams()`) to, for example,
    resolve the frontend slug into the page uid. This is itself a
    backwards compatibility layer. Frontend-related code can no longer
    expect these manipulated variables to exist in the globals
    :php:`_GET`, :php:`_POST`, :php:`HTTP_GET_VARS` and
    :php:`HTTP_POST_VARS`.

*   Middlewares that are executed *after*
    :php-short:`\TYPO3\CMS\Frontend\Middleware\PrepareTypoScriptFrontendRendering`
    (middleware key :code:`typo3/cms-frontend/prepare-tsfe-rendering`)
    can no longer rely on :php:`$GLOBALS['TYPO3_REQUEST']` being set.

Affected installations
======================

Instances running code that relies on the removed compatibility layers
may fail or lead to unexpected results.

Migration
=========

Middlewares receive the Request object directly and should use it instead
of fetching it from :php:`$GLOBALS['TYPO3_REQUEST']`. Services triggered by
middlewares that rely on the Request should have it passed in explicitly.
One example frequently used in middlewares is
:php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`:

..  code-block:: php

     use TYPO3\CMS\Core\Utility\GeneralUtility;
     use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

     $cor = GeneralUtility::makeInstance(ContentObjectRenderer::class);
     $cor->setRequest($request);
     $result = $cor->doSomething();

Code should in general never rely on the globals :php:`_GET`,
:php:`_POST`, :php:`HTTP_GET_VARS` and :php:`HTTP_POST_VARS`. Request-related
state should always be fetched from the Request object. Note that the helper
method :php:`GeneralUtility::getIndpEnv()` will also be phased out once the
TYPO3 Core has removed its last remaining usages.

..  index:: PHP-API, NotScanned, ext:core
