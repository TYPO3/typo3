.. include:: /Includes.rst.txt

.. _extending:

=========================
Extending the Admin Panel
=========================

Extension authors can write their own modules or add submodules to existing
modules.

Creating additional modules
===========================

An admin panel module commonly has:

*  An icon, an identifier, a short info and a label
*  initializeModule and onSubmit methods for initialization (done early in the
   TYPO3 Request) and for reacting to changes (onSubmit is executed when the
   settings are updated)
*  Settings that influence page rendering or page display
*  Methods to provide custom CSS and JavaScript files
*  Submodules

To create your own Admin Panel module
=====================================

#. Create a new PHP class extending
   `\TYPO3\CMS\Adminpanel\Modules\AbstractModule`.

#. Implement at least the following methods:

   *  `getIdentifier` - A unique identifier for your module. For example
      `mynamespace_modulename`
   *  `getIconIdentifier` - An icon identifier which is resolved via the icon
      API. Make sure to use a registered icon here.
   *  `getLabel` - Speaking label for the module. You can access language
      files via `$this->getLanguageService()`
   *  `getShortInfo` - Displayed next to the module label, may contain
      aggregated infos (such as `Total Parse Time: 200ms`)

#. Register your module by adding the following in your `ext_localconf.php`

   .. code-block:: php

      $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['mynamespace_modulename'] = [
          'module' => [
              \Your\Namespace\Adminpanel\Modules\YourModule::class,
              'before' => ['cache'],
          ]
      ];

Using `before` or `after` you can influence where your module will be
displayed in the module menu by referencing the identifier / array key of
other modules.

Modules themselves do provide settings for the page rendering and global
actions (like preview settings, clearing caches or adding action buttons for
editing the page) but do not provide further content.

If you want to display additional content in the Admin Panel (like rendering
times or backtraces), you have to add a submodule to your main module.

To provide settings in a module, implement the method `getSettings`, which has
to return rendered HTML form elements (but without the form tag), ready to be
used in the Admin Panel main form.

Adding a sub-module
===================

An Admin Panel submodule has:

*  An identifier and a label.
*  initializeModule and onSubmit methods for initialization (done early in
   the TYPO3 Request) and reacting to changes (onSubmit is executed when the
   settings are updated).
*  Module content (for example the Info submodules display information about
   the current page or server configuration).
*  Settings influencing their module content (for example the TypoScript
   Time / Rendering sub module has settings that influence whether to display
   messages or not).

As soon as a module has a submodule it will be displayed in the main Admin
Panel. Modules without submodules may only provide settings, and are only
displayed in the Settings overview.

Adding a submodule is similar to adding a module.

#. First, create a new class that extends `AbstractSubModule`. Implement at
   least the following methods:

   *  `getIdentifier` - A unique identifier for your sub module (for example
      `submodulename`)
   *  `getLabel` - Speaking label for the module - you can access language
      files via `$this->getLanguageService()`
   *  `getContent` - The rendered HTML content for your module

#. Register your sub module by adding the following in your
   `ext_localconf.php`

   .. code-block:: php

      $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['mynamespace_modulename']['submodules']['submodulename'] = [
          'module' => \Your\Namespace\Adminpanel\Modules\YourModule\Submodule::class
      ];

Where `mynamespace_modulename` references the main module where you want to
add your submodule, and `submodulename` is the identifier of your sub module.
This way, you can also register new custom sub modules to existing main
modules.

Examples
========

You can find examples for main and sub modules and their registration in the
Admin Panel extension. Short ones for a quick look are:

*  `adminpanel/Classes/Modules/Info/PhpInformation.php` (Submodule)
*  `adminpanel/Classes/Modules/InfoModule.php` (Main module, serves as
   submodule wrapper only)
*  `adminpanel/Classes/Modules/EditModule.php` (Main module, custom rendering
   settings)
