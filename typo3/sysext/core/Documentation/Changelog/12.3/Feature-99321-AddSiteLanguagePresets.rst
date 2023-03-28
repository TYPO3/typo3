.. include:: /Includes.rst.txt

.. _feature-99321-1670525282:

================================================
Feature: #99321 - Add presets for site languages
================================================

See :issue:`99321`

Description
===========

When adding a new language to a site, an integrator can now
choose

a) to create a new language by defining all values themselves
b) from a list of default language settings ("presets")
c) to use an existing language if it is already used in a different site

Although c) is always recommended when working with multi-site setups,
to keep language IDs between sites in sync, b) is now a quick start
to setup a new site.

Impact
======

Integrators spend less time adding new site languages.


.. index:: Backend, ext:core
