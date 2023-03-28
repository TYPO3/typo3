.. include:: /Includes.rst.txt

.. _feature-100284-1679681558:

===============================================================
Feature: #100284 - Add CKEditor Inspector for backend RTE forms
===============================================================

See :issue:`100284`

Description
===========

This feature introduces the ability to show the CKEditor Inspector for backend RTE forms.

With CKEditor 5 and the introduction of the intermediate CKEditor model, knowing
the internals is a requirement to build plugins. The best way to debug during the plugin
development is the `CKEditor Inspector <https://ckeditor.com/docs/ckeditor5/latest/framework/development-tools.html#ckeditor-5-inspector>`_.

For regular pages, there is a simple bookmarklet that can be included to show
the Inspector, but in the TYPO3 backend the usage of frames does not allow this
option. Giving developers a config option in the RTE simplifies this process.

The Inspector can be activated in two different ways:

*   By enabling :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['debug']` and
    being in the `Development` context
*   By setting the option :yaml:`editor.config.debug` to :yaml:`true` in your
    CKEditor configuration

Example for setting the CKEditor configuration:

..  code-block:: yaml

    editor:
      config:
        debug: true


Impact
======

Being in the right context or enabling the given option, it is now possible
to debug CKEditor instances for plugin development in an easier way.

.. index:: Backend, RTE, YAML, ext:rte_ckeditor
