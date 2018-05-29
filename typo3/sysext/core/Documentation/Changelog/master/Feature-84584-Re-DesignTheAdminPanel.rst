.. include:: ../../Includes.txt

===========================================
Feature: #84584 - Re-Design the admin panel
===========================================

See :issue:`84584`

Description
===========

The admin panel got a complete overhaul regarding its design as well as the underlying code and extensibility.

The panel is now structurally separated in modules and submodules. Each module is completely responsible for its own rendering, collection of information, settings and behaviour - dependencies to other parts of the core were resolved. Extension authors can now write their own modules or add submodules to existing modules.

An admin panel module commonly has
- an icon, identifier, a short info and a label
- initializeModule and onSubmit methods for initialization (done early in the TYPO3 Request) and reacting to changes (onSubmit is executed when the settings are updated)
- settings that influence page rendering or page display
- methods to provide custom CSS and JavaScript files
- submodules

An admin panel submodule has
- an identifier and a label
- initializeModule and onSubmit methods for initialization (done early in the TYPO3 Request) and reacting to changes (onSubmit is executed when the settings are updated)
- module content (for example the Info submodules display information about the current page or server configuration)
- settings influencing their module content (for example the TypoScript Time / Rendering sub module has settings that influence whether to display messages or not)

UI wise the following changes were done
- Ajax is used to save configuration options, so only a single reload is triggered even if multiple settings changed.
- Settings influencing page rendering should be grouped together
- Most important info should be available at a glance with possibilities to show extended information
- Complete design overhaul


Impact
======

The new admin panel provides a better look and feel as well as more convenient access to information and more flexible extensibility. For backwards compatibility enabling and disabling modules or options for editors is still possible via User TSConfig.

Creating additional modules
===========================

To create your own admin panel module, create a new PHP class extending `\TYPO3\CMS\Adminpanel\Modules\AbstractModule`.

Implement at least the following methods:
- `getIdentifier` - A unique identifier for your module (for example `mynamespace_modulename`)
- `getIconIdentifier` - An icon identifier (resolved via the icon API, make sure to use a registered icon here)
- `getLabel` - Speaking label for the module - you can access language files via `$this->getLanguageService()`
- `getShortInfo` - Displayed next to the module label, may contain aggregated infos (such as `Total Parse Time: 200ms`)

Register your module by adding the following in your `ext_localconf.php`::

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['mynamespace_modulename'] => [
        'module' => \Your\Namespace\Adminpanel\Modules\YourModule::class,
        'before' => ['cache'],
    ],

via `before` or `after` you can influence where your module will be displayed in the module menu by referencing the
identifier / array key of other modules.

Modules themselves do provide settings for the page rendering or global actions (like preview settings, clearing caches or
adding action buttons for editing the page) but do not provide further content. If you want to display additional content
in the admin panel (like rendering times or backtraces or whatever you wish), you have to add a submodule to your main
module.

To provide settings in a module, implement the method `getSettings`, which has to return rendered HTML form elements
(but without the form tag), ready to be used in the admin panel main form.

Adding a sub-module
===================

First of all: As soon as a module has a submodule it will be displayed in the main admin panel. Modules without submodules
may only provide settings and aren't displayed in the module bar but only in the settings overview.

Adding a submodule is similar to adding a module. First, create a new class that extends `AbstractSubModule`. Implement
at least the following methods:

- `getIdentifier` - A unique identifier for your sub module (for example `submodulename`)
- `getLabel` - Speaking label for the module - you can access language files via `$this->getLanguageService()`
- `getContent` - The rendered HTML content for your module

Register your sub module by adding the following in your `ext_localconf.php`::

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['mynamespace_modulename']['submodules']['submodulename'] => [
      'module' => \Your\Namespace\Adminpanel\Modules\YourModule\Submodule::class
    ]

where `mynamespace_modulename` references the main module where you want to add your submodule, and `submodulename` is the
identifier of your sub module.

This way, you can also register new custom sub modules to existing main modules.

Advanced Usage
--------------

You can also implement the logic completely on your own not using the `AbstractModule` / `AbstractSubModule` by implementing
the corresponding interfaces `AdminPanelModuleInterface` and `AdminPanelSubModuleInterface`.

Examples
========

You can find examples for main and sub modules and their registration in the admin panel extension. Short ones for a quick
look are:

- `adminpanel/Classes/Modules/Info/PhpInformation.php` (Submodule)
- `adminpanel/Classes/Modules/InfoModule.php` (Main module, serves as submodule wrapper only)
- `adminpanel/Classes/Modules/EditModule.php` (Main module, custom rendering settings)


.. index:: Frontend, PHP-API, ext:adminpanel
