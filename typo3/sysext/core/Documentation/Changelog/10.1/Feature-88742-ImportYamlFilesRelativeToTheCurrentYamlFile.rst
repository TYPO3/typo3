.. include:: /Includes.rst.txt

=====================================================================
Feature: #88742 - Import Yaml files relative to the current yaml file
=====================================================================

See :issue:`88742`

Description
===========

The configuration language YAML (Yet Another Markup Language) is used to configure rich-text editor
configuration, Form Framework form definitions, and site handling configuration files.

TYPO3's internal YAML loader has a special handling for cascading and including other YAML files
into the loaded resource via the following syntax:

.. code-block:: yaml

   imports:
     - { resource: "EXT:rte_ckeditor/Configuration/RTE/Processing.yaml" }

   another:
     option: true


However, the reference to the file was usually handled by referencing other resources in
extensions as in :yaml:`EXT:my_extension/Configuration/MyConfig.yaml`.

This is now optimized to allow imported resources to include files relative
to the current YAML file:

.. code-block:: yaml

   imports:
     - { resource: "misc/my_options.yaml" }
     - { resource: "../path/to/something/within/the/project-folder/generic.yaml" }

   another:
     option: true


Impact
======

Especially when using advanced site handling with multiple sites and similar configuration, it is now
possible to have one base configuration file that is referenced by the specific site configuration files
allowing to keep common config in a single place.

.. index:: PHP-API, RTE
