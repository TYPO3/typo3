.. include:: /Includes.rst.txt

==================================================================================
Feature: #92562 - Frontend groups resolved directly after the Frontend User itself
==================================================================================

See :issue:`92562`

Description
===========

For legacy purposes, the valid frontend user groups were added while resolving
the root line in TypoScriptFrontendController. This is much later in the frontend
request process than the preparation of the Frontend User, which is resolved by
the session or form credentials.

There are several reasons for the historic behavior:

* Special functionality like "pages.fe_login_mode" which can override groups based on the current root line
* Previewing frontend user groups via the Admin Panel for backend users

However, this historic behavior led to inconsistencies, especially with the
Context API to retrieve the correct usergroups in PSR-15 middlewares to build custom APIs.

In addition, this functionality is now extracted from TSFE and into the middleware,
further decoupling the User authentication from the TSFE object.


Impact
======

When the PSR-15 middleware is setting up the FrontendUserAuthentication object
at a very early stage of the frontend request, the groups are resolved directly
afterwards, leaving the FrontendUserAuthentication object in a consistent state
for further middlewares to work with the appropriate groups.

Please note that any existing custom AuthenticationService for resolving frontend
user groups, which might rely on a valid TSFE object will have to be evaluated
if it still works in TYPO3 v11.

Also: Further middlewares and TSFE itself can still override the assigned groups
due to previewing behavior.

.. index:: ext:frontend
