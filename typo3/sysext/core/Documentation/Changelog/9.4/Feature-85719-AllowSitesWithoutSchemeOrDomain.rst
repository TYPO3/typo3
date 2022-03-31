.. include:: /Includes.rst.txt

======================================================
Feature: #85719 - Allow sites without scheme or domain
======================================================

See :issue:`85719`

Description
===========

Since the inception of site handling, the definition of a site base - the URL prefix - was limited to
only allow a full URI with scheme (HTTP/HTTPS) and domain. This didn't allow to run TYPO3 on multiple
domains, basically limiting the URL-resolving ("Site Routing") compared to previous URL handling
solutions in the past.

A new site routing based on symfony/routing component allows to have a flexible routing based on
specific schemes.


Impact
======

It is now possible to set a site base prefix to just "/site1" and "/site2" or "www.mydomain.com" instead
of entering a full URI.

This allows to have a Site base e.g. `www.mydomain.com` to be detected with http and https protocols,
although it is recommended to do a HTTP to HTTPS redirect either on the webserver level, via a
.htaccess rewrite rule, or by adding a redirect in TYPO3.

Please also note that this improved flexibility will introduce side-effects when having multiple sites
with mixed configuration settings as Site base:

- Site 1: `/mysite/`
- Site 2: `www.mydomain.com`

will be unspecific when detecting a URL like `www.mydomain/mysite/` and can lead to side-effects.

In this case, it is necessary by the Site Administrator to define unique Site base prefixes.

.. index:: Frontend
