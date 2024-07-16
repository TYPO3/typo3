.. include:: /Includes.rst.txt

.. _feature-101472-1721137289:

================================================
Feature: #101472 - Allow static routes to assets
================================================

See :issue:`101472`

Description
===========

It is now possible to configure static routes with the type `asset` to link to
resources which are typically located in the directory
:file:`EXT:my_extension/Resources/Public/`.

.. code-block:: yaml

    routes:
      -
        route: example.svg
        type: asset
        asset: 'EXT:backend/Resources/Public/Icons/Extension.svg'

Impact
======

Static routes to files shipped with extensions can now be configued in the site configuration.

.. index:: Frontend, YAML, ext:frontend
