.. include:: /Includes.rst.txt

==========================================================
Feature: #93651 - Provide list of available system locales
==========================================================

See :issue:`93651`

Description
===========

Every language of a site requires at least one locale which is used to format times, dates,
currencies and other locale-dependent values. Additional locales can be added as fallback
locales (comma separated).

The site configuration form for site languages provides the available locales as a select field,
enabling easy selection of a value, rather than typing in the expected one, which might or might not
be available.


Impact
======

Providing a list of available locales makes it faster and less error prone to setup a site and its
languages.

.. index:: Backend, ext:backend
