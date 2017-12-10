.. include:: ../../Includes.txt

=======================================================================
Feature: #77576 - Introduce device presets and redesign the view module
=======================================================================

See :issue:`77576`

Description
===========

The view module was redesigned to provide a more modern and streamlined look
and feel across the existing backend. With the introduction of named and
categorized device presets we enable users to get a better idea of how the
page will look like on a specific device. For even more easy testing, the
orientation can now be changed without selecting a different device.

Register device presets via PageTsConfig
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

We are introducing a new set of options to have a more clear configuration.
While the old configuration is still valid and can be used we want to
encourage you to use the new configuration options `label`, `type`,
`height` and `width`.

* `label` defines the title shown,
  this option accepts strings and language files
* `width` defines the width of the preview frame in pixel,
  this option does only allow integers
* `height` defines the height of the preview frame in pixel,
  this option does only allow integers
* `type` defines the category of the preset,
  allowed options are `desktop`, `tablet` and `mobile`

.. code-block:: typoscript

   mod.web_view.previewFrameWidths {

      <key>.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
      <key>.type = desktop
      <key>.width = 1024
      <key>.height = 768

   }

Examples
~~~~~~~~

.. code-block:: typoscript

   mod.web_view.previewFrameWidths {

      1024.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
      1024.type = desktop
      1024.width = 1024
      1024.height = 768

      nexus7.label = Nexus 7
      nexus7.type = tablet
      nexus7.width = 600
      nexus7.height = 960

      nexus6p.label = Nexus 6P
      nexus6p.type = mobile
      nexus6p.width = 411
      nexus6p.height = 731

   }

Impact
======

A more streamlined view module will be available to all users that can be
configured more easy and provides a set of more understandable testing presets
to the users.

.. index:: Backend, TSConfig
