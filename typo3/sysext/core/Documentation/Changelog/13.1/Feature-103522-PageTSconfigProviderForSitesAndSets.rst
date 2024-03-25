.. include:: /Includes.rst.txt

.. _feature-103522-1712323334:

============================================================
Feature: #103522 - Page TSconfig provider for Sites and Sets
============================================================

See :issue:`103522`

Description
===========

TYPO3 sites have been enhanced to be able to provide Page TSconfig on a per site
basis.

Site Page TSconfig is loaded from :file:`page.tsconfig` if placed next to
site configuration file :file:`config.yaml` and is scoped to pages within that
site.


Impact
======

Sites and sets can ship Page TSconfig without the need for database entries or
by polluting global scope when registering Page TSconfig globally via
:file:`ext_localconf.php` or :file:`Configuration/page.tsconfig`.
Dependencies can expressed via sets, allowing for automatic ordering and
deduplication.


.. index:: Backend, Frontend, PHP-API, TypoScript, YAML, ext:core
