.. include:: /Includes.rst.txt

==========================================================================
Breaking: #82406 - Routing: Backend Modules run through regular dispatcher
==========================================================================

See :issue:`82406`

Description
===========

Calling Backend modules was previously handled via a special `BackendModuleRequestHandler` which has
been removed.

When registering a Backend module, a route with the name of the module is automatically added to the
Backend Router.

When generating URLs for modules, the module is not added via the GET Parameter `&M=moduleName`
anymore, but built like any other Backend Route (currently with the "route" and "token" parameters)

All request handling functionality is now done by the regular Backend RequestHandler,
which checks if the Route to be targeted is a module, and does extra module permission checks.


Impact
======

Handling with the "&M" GET parameter in backend modules won't deliver the correct result anymore.

Instantiating `BackendModuleRequestHandler` will result in a fatal PHP error.


Affected Installations
======================

Installations with custom extensions including backend modules which work directly with the GET
parameter "M".


Migration
=========

If extensions use API methods like ``BackendUtility::getModuleUrl()`` are used, nothing needs to be
modified.

If a backend module is using the GET parameter "M" currently, the code needs to be adjusted to the
GET "route" or use the UriBuilder directly.

.. index:: Backend, PHP-API, PartiallyScanned
