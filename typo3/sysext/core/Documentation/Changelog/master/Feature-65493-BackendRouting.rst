=========================================
Feature: #58621 - Unified Backend Routing
=========================================

Description
===========

A new Routing component was added to the TYPO3 Backend which handles addressing different pages / modules inside TYPO3.

A Route is the smallest entity consisting of a path (e.g. "/records/edit/") as well as an identifier for addressing
the route, and the information about how to dispatch the route to a PHP class and a method.

A Route can be a module, wizard or any page inside the TYPO3 Backend. The Router contains the public API for matching
paths to fetch a Route, and to generate an URL to resolve a route.

Routes are defined inside the file "Configuration/Backend/Routes.php" of any extension.

An example can be found within EXT:backend.

The entry point for Routes is typo3/index.php/myroute/?token=.... The main RequestHandler for all Backend requests
detects where a PATH_INFO from the server is given and uses this as the route identifier and then resolves to a
controller/action defined inside the Route.

See http://wiki.typo3.org/Blueprints/BackendRouting for more details.

The API is not public and should not be used for 3rd-party code except for the registration of new routes. The Routing
concept is built so it works solely inside the Bootstrap and in the URL generation, which is centralized through
``BackendUtility::getModuleUrl``.

Impact
======

Handling of existing modules works the same as before and fully transparent. Any existing registration of entrypoints
can be moved to the new registration file in Configuration/Backend/Routes.php.
