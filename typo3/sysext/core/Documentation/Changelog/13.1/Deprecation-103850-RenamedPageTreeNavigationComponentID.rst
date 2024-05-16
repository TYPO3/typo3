.. include:: /Includes.rst.txt

.. _deprecation-103850-1715873982:

================================================================
Deprecation: #103850 - Renamed Page Tree Navigation Component ID
================================================================

See :issue:`103850`

Description
===========

When registering a module in the TYPO3 Backend, using the page tree as navigation component,
the name of the page tree navigation component has been renamed in TYPO3 v13.

Previously, the navigation component was called
:php:`@typo3/backend/page-tree/page-tree-element`, now it is named :php:`@typo3/backend/tree/page-tree-element`.


Impact
======

Using the old navigation ID will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations with custom backend modules utilizing the page tree navigation component.


Migration
=========

Instead of writing this snippet in your :file:`Configuration/Backend/Modules.php`:

.. code-block:: php

    'mymodule' => [
        'parent' => 'web',
        ...
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
    ],

It is now called:

.. code-block:: php

    'mymodule' => [
        'parent' => 'web',
        ...
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
    ],

.. index:: Backend, PHP-API, NotScanned, ext:backend