..  include:: /Includes.rst.txt

..  _breaking-108113-1763081388:

====================================================================================
Breaking: #108113 - Globals _GET and _POST not reset to current Request data anymore
====================================================================================

See :issue:`108113`

Description
===========

The Frontend and Backend application chain roughly splits like this:

bootstrap -> create Request object from globals -> start application
-> run middleware chain -> run RequestHandler to create a Response by
calling controllers (Backend) or ContentObjectRenderer (Frontend).

There was old compatibility code in :php:`RequestHandler` that did reset
the PHP global variables :php:`_GET`, :php:`_POST`, :php:`HTTP_GET_VARS`
and :php:`HTTP_POST_VARS` to current values that may have been written
to the according Request object counterparts by middlewares.

This backwards compatible layer has been removed.

Additionally, in the frontend rendering, the global variable
:php:`$GLOBALS['TYPO3_REQUEST']` is no longer populated within the
:php:`PrepareTypoScriptFrontendRendering` middleware anymore, but a little
later by :php:`RequestHandler`. :php:`$GLOBALS['TYPO3_REQUEST']` is another
compatibility layer the TYPO3 core aims to phase out slowly.


Impact
======

The impact is two fold:

* Some core middlewares manipulate the Request object "GET" parameter
  list (:php:`$request->getQueryParams()`) to for instance resolve the
  frontend slug into the page uid (which is a backwards compatible layer
  as well). Frontend related code can no longer expect these manipulated
  variables to exist in globals :php:`_GET`, :php:`_POST`, :php:`HTTP_GET_VARS`
  and :php:`HTTP_POST_VARS`.

* Middlewares that are executed *after* :php:`PrepareTypoScriptFrontendRendering`
  (middleware key :code:`typo3/cms-frontend/prepare-tsfe-rendering`) can no
  longer rely on :php:`$GLOBALS['TYPO3_REQUEST']` being set.


Affected installations
======================

Instances running code that relies on the removed compatibility layers
may fail or lead to unexpected results.


Migration
=========

Middlewares receive the Request and should use it instead of fetching it from
:php:`$GLOBALS['TYPO3_REQUEST']`. Services triggered by middlewares that rely
on the request should get it hand over. One particular example frequently
called by middleware classes is the :php:`ContentObjectRenderer`:

.. code-block:: php

    $cor = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    $cor->setRequest($request);
    $result = $cor->doSomething();

Code should in general never rely on globals :php:`_GET`, :php:`_POST`,
:php:`HTTP_GET_VARS` and :php:`HTTP_POST_VARS` anymore. Request related state
should always be fetched from the Request object. Note the helper method
:php:`GeneralUtility::getIndpEnv()` will be phased out as well when the TYPO3
core removed last remaining usages.


..  index:: PHP-API, NotScanned, ext:core
