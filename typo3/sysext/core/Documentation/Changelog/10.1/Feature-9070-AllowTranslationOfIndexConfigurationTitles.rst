.. include:: /Includes.rst.txt

================================================================
Feature: #9070 - Allow translation of index configuration titles
================================================================

See :issue:`9070`

Description
===========

Indexed search plugin allows to select specifically which index configuration to be queried from in the specific form,
where a dropdown shows all possible indexing configurations.


Impact
======

It is now possible to add a label for each configuration via TypoScript in each language.

.. code-block:: typoscript

   plugin.tx_indexedsearch.settings._LOCAL_LANG {
      de.indexingConfigurations.13 = Mein Titel in Deutsch für Konfiguration 13
      de.indexingConfigurationHeader.13 = Alle Ergebnisse für Konfiguration 13
   }

.. index:: Backend, ext:indexed_search
