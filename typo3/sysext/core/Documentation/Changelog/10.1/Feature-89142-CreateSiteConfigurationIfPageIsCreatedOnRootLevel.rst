.. include:: /Includes.rst.txt

============================================================================
Feature: #89142 - Create site configuration if page is created on root level
============================================================================

See :issue:`89142`

Description
===========

When creating a typical new page on the root level of a TYPO3 installation, a new site configuration is now
automatically created as well. This makes it easier to work with multi-sites and get a basic configuration set up
more quickly than before.

Under the hood, a new :php:`DataHandler` hook checks for new pages being one of the following page types:

* Default pages
* Links
* Shortcuts

The entry point consists of the current domain where the configuration has been created, plus a short identifier using
the page uid and the prefix "site", e.g. `https://example.com/site-42`.

The identifier of the site uses the entry point without the domain, and a MD5 hash of the page id to avoid potential
conflicts for existing site configurations. An identifier may look like `site-42-a1d0c6e83f`.

Impact
======

A new site configuration with a pre-defined identifier, entry point and a default language gets created automatically.

Ideally, there are no scenarios anymore where a site needs to be created after a first page is created, avoiding
any issues related to Slug handling for root pages, which are always set to `/` by default.

.. index:: Backend, ext:core
