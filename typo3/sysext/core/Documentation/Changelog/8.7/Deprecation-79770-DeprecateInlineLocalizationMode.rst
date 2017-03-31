.. include:: ../../Includes.txt

=======================================================
Deprecation: #79770 - Deprecate inline localizationMode
=======================================================

See :issue:`79770`

Description
===========

The `localizationMode` for inline relational record editing types is deprecated.


Impact
======

Using `localizationMode` set to `keep` and having `allowLanguageSynchronization` enabled at the same time is
counter-productive, since it will deny the synchronization process for the affected field. That's why `localizationMode`
is unset only if `allowLanguageSynchronization` is enabled.


Affected Installations
======================

All having :php:`$TCA[<table-name>]['columns'][<field-name>]['config']['behaviour']['localizationMode']` defined for
database tables that support translations.


Migration
=========

Remove :php:`$TCA[<table-name>]['columns'][<field-name>]['config']['behaviour']['localizationMode']` definitions and
make use of either one of the following

* :php:`$TCA[<table-name>]['columns'][<field-name>]['config']['behaviour']['allowLanguageSynchronization'] = true` if editors can decide whether to provide custom child references or synchronize all references from the language parent record - this comes close to `localizationMode=select` without having the possibility to selectively translate child references
* :php:`$TCA[<table-name>]['columns'][<field-name>]['l10n_mode'] = 'exclude'` if editors don't have a choice to translate child references - this corresponds to `localizationMode=keep`

.. index:: Backend, TCA
