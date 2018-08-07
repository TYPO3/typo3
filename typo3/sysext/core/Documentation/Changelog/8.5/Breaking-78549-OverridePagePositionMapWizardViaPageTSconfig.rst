.. include:: ../../Includes.txt

======================================================================
Breaking: #78549 - Override New Page Creation Wizard via page TSconfig
======================================================================

See :issue:`78549`

Description
===========

In the past it was possible to override the "New Page Creation Wizard" via custom scripts
when using page TSconfig via :ts:`mod.web_list.newPageWiz.overrideWithExtension = myextension` to define an extension,
which then needed a file placed under :file:`mod1/index.php`. The script was then called with certain parameters instead
of the wizard.

The new way of handling entry-points and custom scripts is now built via modules and routes. The former option
:ts:`mod.web_list.newPageWiz.overrideWithExtension` has been removed and a new option
:ts:`mod.newPageWizard.override` has been introduced instead. Instead of setting the option to a certain extension key,
a custom module or route has to be specified.

Example:

.. code-block:: typoscript

	mod.newPageWizard.override = my_custom_module


Impact
======

Using the old TSconfig option :ts:`mod.web_list.newPageWiz.overrideWithExtension` has no effect anymore and
will fallback to the regular new page creation wizard provided by the TYPO3 Core.


Affected Installations
======================

Any installation using this option with extensions providing custom New Page Wizards, e.g. EXT:templavoila.


Migration
=========

The extension providing the script must be changed to register a route or module and set the TSconfig option to the route identifier,
instead of a raw PHP script. Any usages in TSconfig need to be adapted to use the new TSconfig option.

.. index:: Backend, TSConfig
