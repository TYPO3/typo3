.. include:: ../../Includes.txt

==========================================
Feature: #86303 - Variants for site's base
==========================================

See :issue:`86303`

Description
===========

The site configuration allows now to specify variants of the site's base.
Take the following example: The base of a site is set to `https://www.domain.tld` but the staging environment uses
`https://staging.domain.tld` and the local development uses `https://www.domain.local`.

The expression language feature is used to define which variant is taken into account.

Impact
======

The base of a site can be changed depending on a condition. Typical examples are:

- :typoscript:`applicationContext == "Production"`: Check the application context
- :typoscript:`getenv("mycontext") == "production`: Check a custom environment variable

.. index:: Backend, Frontend, TypoScript, ext:core
