.. include:: /Includes.rst.txt

===============================================================
Breaking: #38496 - Shortcut redirects append all URL parameters
===============================================================

See :issue:`38496`

Description
===========

When a user accesses a shortcut page, all provided URL parameters are appended to
the target URL.

**Example:**

Page with UID 2 is a shortcut to page with UID 1 and these `linksVars` are configured:

.. code-block:: typoscript

   config.linkVars = L

..

Old behavior:

http://mydomain.tld?id=2&L=1&customparam=X will redirect to http://mydomain.tld?id=1&L=1

New behavior:

http://mydomain.tld?id=2&L=1&customparam=X will redirect to http://mydomain.tld?id=1&L=1&customparam=X


Impact
======

The target URL of a shortcut may change when additional parameters are provided in the URL.


Affected Installations
======================

All installations using shortcut pages are affected.


Migration
=========

There is no migration available.

.. index:: Frontend, TypoScript
