.. include:: /Includes.rst.txt

.. _feature-91783-1712426102:

=========================================================================
Feature: #91783 - Allow system maintainer to mute disable_functions error
=========================================================================

See :issue:`91783`

Description
===========

Adds a configuration option to adapt the environment check in the Install Tool
for a list of sanctioned :php:`disable_functions`.

With the new configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['allowedPhpDisableFunctions']`,
a system maintainer can add native PHP function names to this list,
which are then reported as environment warnings instead of errors.

Configuration example in :file:`additional.php`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['allowedPhpDisableFunctions']
      = ['set_time_limit', 'set_file_buffer'];

You can also define this in your :file:`settings.php` file manually
or via :guilabel:`Admin Tools > Settings > Configure options`.

Impact
======

Native php function names can be added as an array of function names, which will
not trigger an error but only a warning, if they can also be found in the php.ini
setting :php:`disable_functions`.

.. index:: Backend, ext:core
