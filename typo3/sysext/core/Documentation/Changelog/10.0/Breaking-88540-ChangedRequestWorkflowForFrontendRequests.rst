.. include:: /Includes.rst.txt

=================================================================
Breaking: #88540 - Changed Request Workflow for Frontend Requests
=================================================================

See :issue:`88540`

Description
===========

The "Frontend Request Workflow" is the PHP code responsible for
setting up various functionality when the TYPO3 Frontend (= rendering of the website)
is booted and the content is built. This includes Login/Permission Check, resolving
the current site + language, and checking the page + rootline, then
parsing TypoScript, which will then lead to building content (or taken from
cache), until the actual output is returned.

Since TYPO3 v9, this is all built via PSR-15 middlewares, the PSR-15 Request Handler,
and the global TypoScriptFrontendController (TSFE).

For TYPO3 v10.0, various changes were made in order to separate concerns / logic
from each other, allowing to easily exchange certain components with
other / extended functionality.

The following changes have been made:

Storing session data from a Frontend User Session / Anonymous session is now triggered within the Frontend User
(`typo3/cms-frontend/authentication`) Middleware, at a later point - once the page was generated. Up until TYPO3 v9, this
was part of the RequestHandler logic right after content was put together. This was due to legacy reasons of the
previous hook execution order.

Resolving the actual site - that is the site configuration plus the language - now happens before Frontend
and Backend User Authentication. This is important to understand to be able to define further settings within
Site Handling configuration in the future. Site and Site Language Resolving is now 100% independent of any permission
settings. Evaluating if a language is active is evaluated separately.

Backend User Authentication (:php:`$BE_USER`) is now started before Frontend User Authentication (`fe_user`), previously
this was the other way around. Frontend Users are now stored in the request object via the `frontend.user` attribute,
instead of :php:`$TSFE->fe_user`, until :php:`$TSFE` is instantiated.

Once all site + permission/authentication functionality has been set up, Routing now tries to detect
the target page ID and the URL parameters (`PageResolver` middleware) and evaluates the result, so-called
"Page Arguments" directly afterwards (`PageArgumentValidator` middleware). This effectively validates the cHash
logic.

All of the mentioned parts above do not depend on :php:`TSFE` anymore. In fact, they are 100% independent of
any TSFE-related code. :php:`TSFE` is instantiated after all site resolving, authentication, page resolving and argument
validation is done.

The new request workflow looks like this (simplified):

#. Evaluation of Normalized Parameters (a.k.a. :php:`getIndpEnv`) & Evaluation of "Maintenance Mode" functionality
#. Handling registered eID scripts depending on GET parameter `eID`
#. Resolving Site configuration and Language from URL if possible
#. Resolving logged-in Backend User Authentication for previewing hidden pages or languages
#. Authentication of Website Users ("Frontend Users")
#. Executing various static routes and redirect functionality
#. Resolving Target Page ID and URL parameters based on Routing, Validation of Page Arguments based on "cHash"
#. Setting up global :php:`$TSFE` object, injecting previously resolved settings into TSFE.
#. Resolving the Rootline for the page
#. Parsing and Evaluation of TypoScript Instructions to render the page content
#. Build the content (cached / uncached)
#. Return the Response (PSR-7) to the base application and output headers + content.

In addition, :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` now expects the following constructor arguments:

#. Context API object (previously a copy of :php:`$TYPO3_CONF_VARS`, until TYPO3 v8, then, unused)
#. :php:`TYPO3\CMS\Core\Site\Entity\SiteInterface` object (previously the Page ID)
#. :php:`TYPO3\CMS\Core\Site\Entity\SiteLanguage` object (previously the Page Type)
#. :php:`TYPO3\CMS\Core\Routing\PageArguments` object (previously the no_cache GET parameter)
#. :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication` object (previously the cHash parameter)

Impact
======

Hooks that depend on certain functionality being made before or after a hook is
called will likely have a different behavior when a Frontend Session is used within Hooks.

Anything related to regular plugins / content / TypoScript is not affected.


Affected Installations
======================

Any hooks from third party extensions that run
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']`
and depend on the frontend session data being written.

Any TYPO3 extensions using middlewares in the frontend.

Migration
=========

Consider using a PSR-15 middleware instead of using a hook, or explicitly call :php:`storeSessionData()` within
the PHP hook if necessary.

If an existing middleware was used, ensure that it's loaded in TYPO3 v10 at the proper location, as the
`typo3-cms/frontend/tsfe` middleware is loaded at a very late point.

Ensure to use proper objects for the constructor arguments on :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` when instantiating
the object on your own.

.. index:: Frontend, PHP-API, NotScanned
