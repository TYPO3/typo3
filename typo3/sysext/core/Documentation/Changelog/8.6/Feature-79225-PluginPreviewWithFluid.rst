.. include:: ../../Includes.txt

===========================================
Feature: #79225 - Plugin preview with Fluid
===========================================

See :issue:`79225`

Description
===========

The page TSconfig to render a preview of a single content element in the Backend has been improved
by allowing the rendering of plugins as well.

The following option allows to override the default output of a plugin via page TSconfig:

.. code-block:: typoscript

   mod.web_layout.tt_content.preview.list.example = EXT:site_mysite/Resources/Private/Templates/Preview/ExamplePlugin.html

All properties of the tt_content record are available in the template directly.
Any data of the flexform field `pi_flexform` is available with the property `pi_flexform_transformed` as an array.

.. note::

   If a PHP hook already is set to render the element, it will take precedence over the Fluid-based preview.

.. index:: Backend, Fluid