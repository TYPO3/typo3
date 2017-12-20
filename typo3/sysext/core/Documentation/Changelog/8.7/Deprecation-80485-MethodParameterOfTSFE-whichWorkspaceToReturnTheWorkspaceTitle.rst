.. include:: ../../Includes.txt

============================================================================================
Deprecation: #80485 - Method parameter of TSFE->whichWorkspace to return the workspace title
============================================================================================

See :issue:`80485`

Description
===========

The method :php:`TypoScriptFrontendController->whichWorkspace()` has an optional first parameter
to return the workspace title of the current workspace instead of the current workspace UID.

This parameter has been marked as deprecated.


Impact
======

When calling the method above with the method parameter set to "true", a deprecation message is
triggered.


Affected Installations
======================

Any installation using this PHP method with the parameter set to "true" via a custom extension which
deals with workspaces for frontend output (e.g. for editors to know in which workspace a user is
currently previewing a page).


Migration
=========

If the workspace title is necessary, a separate SQL call should be done right after
:php:`whichWorkspace()` is called in the extensions' PHP code.

.. index:: Frontend, PHP-API, ext:workspaces
