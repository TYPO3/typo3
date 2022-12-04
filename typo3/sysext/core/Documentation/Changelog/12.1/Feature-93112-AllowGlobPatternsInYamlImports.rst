.. include:: /Includes.rst.txt

.. _feature-93112-1667904722:

=====================================================
Feature: #93112 - Allow glob patterns in yaml imports
=====================================================

See :issue:`93112`

Description
===========

The TYPO3 :php:`YamlFileLoader` (used, for example, when loading site configurations) does
now support importing files with glob patterns. This will simplify the configuration
and allow for more compact configuration files.

To enable globbing, set the option :yaml:`glob: true` on the import level.


Impact
======

You can now use `glob()` syntax when importing configuration files in YAML.

Example:

..  code-block:: yaml

    imports:
      - { resource: "./**/*.yaml", glob: true }
      - { resource: "EXT:core/Tests/**/Configuration/**/SiteConfigs/*.yaml", glob: true }

.. index:: PHP-API, YAML, ext:core
