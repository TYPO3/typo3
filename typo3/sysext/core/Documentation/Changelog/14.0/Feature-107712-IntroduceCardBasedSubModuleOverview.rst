..  include:: /Includes.rst.txt

..  _feature-107712-1760548718:

===========================================================
Feature: #107712 - Introduce card-based sub module overview
===========================================================

See :issue:`107712`

Description
===========

Backend modules can now display a card-based overview of their submodules
instead of automatically redirecting to the first available submodule. This
new :php:`showSubmoduleOverview` configuration option enables a more
user-friendly navigation experience, similar to the Install Tool's maintenance
card layout.

When enabled, clicking on a second-level module displays an overview
page with cards for each accessible submodule. Each card shows the module's
icon, title, description, and an "Open module" button, allowing users to make
an informed choice about which submodule to access.

Example configuration:

..  code-block:: php

    return [
        'web_info' => [
            'parent' => 'web',
            'showSubmoduleOverview' => true,
        ],
        'web_info_overview' => [
            'parent' => 'web_info',
            'access' => 'user',
            'path' => '/module/web/info/overview',
            'iconIdentifier' => 'module-info',
            'labels' => [
                'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page',
                'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page.description',
            ],
            // ... routes and other configuration
        ],
        'web_info_translations' => [
            'parent' => 'web_info',
            'access' => 'user',
            'path' => '/module/web/info/translations',
            'iconIdentifier' => 'module-info',
            'labels' => [
                'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_lang',
                'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_lang.description',
            ],
            // ... routes and other configuration
        ],
    ];

In this example, the :guilabel:`Web > Info` module displays a card-based
overview showing both :guilabel:`Pagetree Overview` and
:guilabel:`Localization Overview` submodules. Users can read the description
of each module before deciding which one to open.

The feature works seamlessly with the existing module permission system. Only
submodules that the current user has access to are displayed in the overview.
If no accessible submodules exist, a helpful information message is shown
instead of an empty page.

Implementation Details
======================

The :php:`showSubmoduleOverview` option modifies the behavior in several key areas:

1. **Module Routing** - When set to :php:`true`, the module's default route
   targets :php:`SubmoduleOverviewController` instead of automatically
   redirecting to the first available submodule.

2. **Middleware Behavior** - The :php:`BackendModuleValidator` middleware
   skips its automatic submodule redirection logic when this option is enabled,
   allowing the overview page to be displayed.

3. **Navigation Enhancement** - The system provides automatic navigation
   capabilities:

   - The :php:`SubmoduleOverviewController` displays the submodule jump menu,
     allowing quick access to all available submodules
   - When :php:`showSubmoduleOverview` is activated, the :php:`ModuleTemplate`
     automatically adds a "Module Overview" menu item to the submodule dropdown
   - This allows users to easily navigate back to the overview from any
     submodule, especially useful when a submodule does not manually provide
     a "go back" button

To provide meaningful descriptions on the overview cards, modules should define
a :php:`description` or :php:`shortDescription` in their labels configuration.
These are displayed in the card body to help users understand each submodule's
purpose.

Impact
======

Backend navigation becomes more intuitive and self-documenting. Container
modules using :php:`showSubmoduleOverview` provide users with a clear overview
of available functionality, eliminating the confusion of being automatically
redirected to an arbitrary first submodule.

The :guilabel:`Web > Info` module now uses this feature to present its
submodules in an accessible, visually organized manner. Users can quickly
understand what each submodule offers before navigating to it, improving
discoverability and user experience in the TYPO3 backend.

..  index:: Backend, PHP-API, ext:backend
