.. include:: /Includes.rst.txt

.. _feature-96515-1657733886:

================================================================
Feature: #96515 - Aliases for Backend Routes and Backend Modules
================================================================

See :issue:`96515`

Description
===========

TYPO3 Backend Module and Routing functionality now allows to define a different
route identifier (e.g. "record_edit") or module identifier (e.g. "web_layout"
as the identifier for the Page Module) while also defining aliases for any
previous identifier.

This is especially important when a module identifier should be changed to use
a proper naming, reflecting the actual module, while keeping any links from
within TYPO3's Backend extensions - e.g. third-party - to continue to work.

An upgrade wizard allows to continuously verify backend user and backend group
permissions when a module identifier has been changed, as long as the previous
identifier is added as an alias to the :doc:`module configuration <../12.0/Feature-96733-NewBackendModuleRegistrationAPI>`.

Impact
======

The new array key :php:`aliases` in module and route configurations can be used
to provide support for different names, which ultimately allows to rename
route and module identifiers, since the old identifier can still be used to
reference them.

Example for a new module identifier within
:file:`Configuration/Backend/Modules.php`:

..  code-block:: php

    return [
        'workspaces_admin' => [
            'parent' => 'web',
            ...
            // choose the previous name or an alternative name
            'aliases' => ['web_WorkspacesWorkspaces'],
        ],
    ];

Example for a route alias identifier within
:file:`Configuration/Backend/Routes.php`:

..  code-block:: php

    return [
        'file_editcontent' => [
            'path' => '/file/editcontent',
            'aliases' => ['file_edit'],
        ],
    ];

.. index:: Backend, PHP-API, ext:backend
