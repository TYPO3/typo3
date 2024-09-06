.. include:: /Includes.rst.txt

.. _breaking-102902-1706022423:

============================================================
Breaking: #102902 - Search rules removed from Indexed Search
============================================================

See :issue:`102902`

Description
===========

The "Rules" section in Indexed Search stems from a time when today's knowledge
how a search works was considered "advanced". By today's standards, it can be
considered common sense and therefore the rules and its related TypoScript
configuration have been removed.


Impact
======

The Indexed Search plugin doesn't show the rules anymore. The Fluid partial file
:file:`Resources/Private/Partials/Rules.html` and the related TypoScript
configuration :typoscript`:`plugin.tx_indexedsearch.settings.displayRules`
have been removed.


Affected installations
======================

All installations displaying the Indexed Search search rules are affected.


Migration
=========

Fluid
-----

Remove any overrides for the partial file :file:`Resources/Private/Partials/Rules.html`,
as well as the :html:`<f:render partial="Rules" />` invocation from a potentially
overridden :file:`Resources/Private/Partials/Form.html` partial file.


TypoScript
----------

If configured, remove the :typoscript`:`plugin.tx_indexedsearch.settings.displayRules`
configuration.

.. index:: Frontend, TypoScript, NotScanned, ext:indexed_search
