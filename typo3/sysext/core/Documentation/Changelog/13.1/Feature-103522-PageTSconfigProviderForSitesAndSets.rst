.. include:: /Includes.rst.txt

.. _feature-103522-1712323334:

============================================================
Feature: #103522 - Page TSconfig provider for sites and sets
============================================================

See :issue:`103522`

Description
===========

TYPO3 sites have been enhanced to be able to provide page TSconfig on a per-site
basis.

Site page TSconfig is loaded from :file:`page.tsconfig`, if placed next to the
site configuration file :file:`config.yaml` and is scoped to pages within that
site.


Impact
======

Sites and sets can ship page TSconfig without the need for database entries or
by polluting global scope when registering page TSconfig globally via
:file:`ext_localconf.php` or :file:`Configuration/page.tsconfig`.
Dependencies can be expressed via sets, allowing for automatic ordering and
deduplication.


.. index:: Backend, Frontend, PHP-API, TypoScript, YAML, ext:core
