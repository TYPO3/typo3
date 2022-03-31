.. include:: /Includes.rst.txt

=========================================================================
Deprecation: #86323 - Configuration key "site" in YAML site configuration
=========================================================================

See :issue:`86323`

Description
===========

The site configuration is a file called :file:`config` in a folder called :file:`sites` and does not need a ``site`` key
to identify its purpose. To keep writing the config as easy as possible, the site configuration moved one level up and
now resides directly in :file:`config.yaml`.


Impact
======

Having the site configuration below the key "site" has been marked as deprecated and will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Installations with a site config that have a key "site" are affected.


Migration
=========

Remove the "site" key and first level indentation either by directly editing the YAML file or by saving via the sites module.

.. index:: Backend, NotScanned, ext:core
