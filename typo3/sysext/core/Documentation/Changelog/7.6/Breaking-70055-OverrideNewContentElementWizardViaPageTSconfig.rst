
.. include:: ../../Includes.txt

========================================================================
Breaking: #70055 - Override New Content Element Wizard via page TSConfig
========================================================================

See :issue:`70055`

Description
===========

In the past it was possible to override the "New Content Element Wizard" via custom scripts
when using page TSconfig via `mod.web_list.newContentWiz.overrideWithExtension = myextension` to define an extension,
which then needed a file placed under `mod1/db_new_content_el.php`. The script was then called with certain parameters instead
of the wizard.

The new way of handling entry-points and custom scripts is now built via modules and routes. The former option
`mod.web_list.newContentWiz.overrideWithExtension` has been removed and a new option
`mod.newContentElementWizard.override` has been introduced instead. Instead of setting the option to a certain extension key,
a custom module or route has to be specified.

Example:

.. code-block:: typoscript

	mod.newContentElementWizard.override = my_custom_module


Impact
======

Using the old TSconfig option `mod.web_list.newContentWiz.overrideWithExtension` has no effect anymore and
will fallback to the regular new content element wizard provided by the TYPO3 Core.


Affected Installations
======================

Any installation using this option with extensions providing custom New Content Element Wizards, e.g. templavoila.


Migration
=========

The extension providing the script must be changed to register a route or module and set the TSconfig option to the route identifier,
instead of a raw PHP script. Any usages in TSconfig need to be adapted to use the new TSconfig option.


.. index:: TSConfig, PHP-API, Backend
