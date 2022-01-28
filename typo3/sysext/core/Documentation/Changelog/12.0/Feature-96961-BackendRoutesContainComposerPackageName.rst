.. include:: ../../Includes.txt

==============================================================
Feature: #96961 - Backend routes contain composer package name
==============================================================

See :issue:`96961`

Description
===========

Request objects in the backend already contain the resolved route
object as attribute. These route objects now contain the composer
package name of the package ("extension") that defined the route as option:

.. code-block:: php

    /** @var \TYPO3\CMS\Backend\Routing\Route $route */
    $route = $request->getAttribute('route');
    // Example return: "typo3/cms-backend" when EXT:backend defined that route.
    $packageName = $route->getOption('packageName');


Impact
======

The package name can be useful for filesystem lookups
or to bind configuration based on package name to it.


.. index:: Backend, PHP-API, ext:backend
