.. include:: /Includes.rst.txt

.. _breaking-96835:

========================================================
Breaking: #96835 - https as default scheme in PageRouter
========================================================

See :issue:`96835`

Description
===========

The fallback scheme in :php:`\TYPO3\CMS\Core\Routing\PageRouter::generateUri()` is set to `https` instead of `http` when linking to other pages.

Impact
======

If the site configuration does not provide a scheme but only a domain (e.g. `www.domain.tld`), the scheme is set to `https`.

Affected Installations
======================

All installations which use a site configuration without providing a scheme and which must not be delivered through `https`.

Migration
=========

If `https` can't be used, the entry point must define the scheme, e.g. `http://www.domain.tld`.

.. index:: Frontend, NotScanned, ext:core
