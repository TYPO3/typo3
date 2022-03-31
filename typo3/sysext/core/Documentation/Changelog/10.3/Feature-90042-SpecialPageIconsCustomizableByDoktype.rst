.. include:: /Includes.rst.txt

=========================================================
Feature: #90042 - Customize special page icons by doktype
=========================================================

See :issue:`90042`

Description
===========

The page icon in the pagetree can now be fully customized for own doktypes.
Before this it was possible to provide one icon. This icon however was not used when the page was in one of the following states:

* Page is hidden in navigation
* Page is site-root
* Page contains content from another page
* Page contains content from another page AND is hidden in navigation

Provide custom icons in TCA like so:

:file:`EXT:my_extension/Configuration/TCA/Overrides/pages.php`

.. code-block:: php

   'ctrl' => [
      'typeicon_classes' => [
          '123' => "your-icon",
          '123-contentFromPid' => "your-icon-contentFromPid",
          '123-root' => "your-icon-root",
          '123-hideinmenu' => "your-icon-hideinmenu",
      ],
   ]

Icons you don't provide will automatically fall back to the variant for regular page doktypes.

.. note::

   Make sure to add the additional icons using the IconRegistry!

.. index:: TCA, ext:core
