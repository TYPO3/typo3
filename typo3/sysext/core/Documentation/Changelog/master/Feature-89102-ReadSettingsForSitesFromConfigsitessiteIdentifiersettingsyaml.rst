.. include:: ../../Includes.txt

============================================================================================
Feature: #89102 - Read settings for sites from <config>/sites/<siteIdentifier>/settings.yaml
============================================================================================

See :issue:`89102`

Description
===========

The concept of site settings allows the usage of site specific variables independent of the current context (for example: allows the usage of a "storagePid" in CLI and TypoScript context).


Impact
======

To configure settings, a `YAML` file called :file:`settings.yaml` can be stored in the site configuration folder with plain yaml arrays of configuration. The site settings are loaded as property of the site object and can be accessed wherever the site is available. Additionally, they are loaded as constants in TypoScript context - and can be used as such.

Example :file:`settings.yaml`:

.. code-block:: yaml

   MyVendor:
      MyExtension:
         storagePid: 1
         limit: 15

Usage in TypoScript as constant:

.. code-block:: typoscript

   `plugin.tx_myext.storagePid = {$MyVendor.MyExtension.storagePid}`

Usage in PHP via Site object:

.. code-block:: php

   $settings = $site->getSettings();
   $storagePid = $settings['MyVendor']['MyExtension']['storagePid'];

Please use namespaces (vendor/ext name) for your settings to avoid conflicts.

.. index:: Backend, Frontend, PHP-API, ext:core
