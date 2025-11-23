.. include:: /Includes.rst.txt

.. _feature-104047-1717653944:

===============================================================
Feature: #104047 - Option to report redirects in link validator
===============================================================

See :issue:`104047`

Description
===========

A new Page TSconfig option
:tsconfig:`mod.linkvalidator.linktypesConfig.external.allowRedirects`
has been introduced to the link validator. It allows redirects
(HTTP 3xx responses) to be reported as problems when validating external links.

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    mod.linkvalidator.linktypesConfig.external.allowRedirects = 0

By default, redirects are *not* reported as problems.

Impact
======

Integrators can now configure whether HTTP redirects of external links
should be reported as problems in the Link Validator via page TSconfig.

.. index:: Backend, TSConfig, ext:linkvalidator
