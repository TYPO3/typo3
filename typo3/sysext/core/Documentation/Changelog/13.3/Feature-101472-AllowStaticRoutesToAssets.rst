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

..  code-block:: yaml
    :caption: config/sites/my-site/config.yaml

    routes:
      -
        route: example.svg
        type: asset
        asset: 'EXT:backend/Resources/Public/Icons/Extension.svg'

Note that the asset URL can be configured on a per-site basis.
This allows to deliver site-dependent custom favicon or manifest
assets, for example.

Impact
======

Static routes to files shipped with extensions can now be configured in the site configuration.

.. index:: Frontend, YAML, ext:frontend
