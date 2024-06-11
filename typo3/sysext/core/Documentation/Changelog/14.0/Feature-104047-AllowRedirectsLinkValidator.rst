.. include:: /Includes.rst.txt

.. _feature-104047-1717653944:

===============================================================
Feature: #104047 - Option to report redirects in link validator
===============================================================

See :issue:`104047`

Description
===========

A new Page TSconfig option
:typoscript:`mod.linkvalidator.linktypesConfig.external.allowRedirects`
has been added to the link validator to report HTTP redirects
with external links as problems.

.. code-block:: typoscript:

    mod.linkvalidator.linktypesConfig.external.allowRedirects = 0

Redirects are not reported as problems by default.

Impact
======

Integrators can now configure HTTP redirects of external links
to be reported as problems via Page TSconfig.

.. index:: Backend, TSConfig, ext:linkvalidator
