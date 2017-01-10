.. include:: ../../Includes.txt

==========================================
Breaking: #79242 - Remove l10n_mode noCopy
==========================================

See :issue:`79242`

Description
===========

The setting noCopy is removed from the list of possible values of the TCA column
property l10n_mode without any replacement.


Impact
======

Previously noCopy prevented that values of the parent language record are copied
to a particular localization when this was created. Now, this value is duplicated once during the creation of the localized record and has to be cleaned manually if required.


Affected Installations
======================

All having `$GLOBALS['TCA'][<table-name>]['columns'][<column-name>]['l10n_mode']`
set to `noCopy`.


Migration
=========

Remove setting `$GLOBALS['TCA'][<table-name>]['columns'][<column-name>]['l10n_mode']`
if it is set to `noCopy`.

.. index:: TCA