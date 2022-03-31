.. include:: /Includes.rst.txt

===================================================
Feature: #87301 - Secure cookies enabled by default
===================================================

See :issue:`87301`

Description
===========

In previous TYPO3 installations an option existed to define
whether a cookie was shared between HTTP and HTTPS requests.

This allowed to have the same cookie available for HTTPS and non-HTTPS, when a site was available on both ports / protocols.

In order to enhance security, the option is removed and the feature
provides sensible defaults in the current state of the web, where
it is recommended to run sites with HTTPS, or if this is not possible
to use HTTP, but not using a mixed mode, which also has SEO downsides.


Impact
======

The new defaults are:

* If a website is running on HTTPS, the cookie is only exposed via HTTPS.
* If a website is running on HTTP, the cookie is available for HTTPS as well, but not vice-versa.

The TYPO3 Configuration option :php:`$TYPO3_CONF_VARS[SYS][cookieSecure]` is removed when upgrading TYPO3 installations.

.. index:: LocalConfiguration, ext:core
