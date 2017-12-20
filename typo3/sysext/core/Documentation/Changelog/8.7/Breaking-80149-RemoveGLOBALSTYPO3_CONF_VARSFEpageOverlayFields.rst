.. include:: ../../Includes.txt

================================================================================
Breaking: #80149 - Remove $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']
================================================================================

See :issue:`80149`

Description
===========

The configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']` is
removed from the default configuration as well as from the overlay handling in
PageRepository and RootlineUtility.

This setting has been used to determine overlay fields in the table
:sql:`pages_language_overlay` at a time in the runtime processing when the
complete TCA was not fully available. Since the `allowLanguageSynchronization`
possibility has been integrated into TYPO3 CMS 8, `l10n_mode` was available
already and the TCA is loaded as well, the `pageOverlayFields` settings
are superfluous.


Impact
======

Since :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']` was used as a
filter for field names to be taken from :sql:`pages_language_overlay` and merged
onto those fields in :sql:`pages`, all fields are overlaid per default.


Affected Installations
======================

All installations having custom fields in table :sql:`pages_language_overlay` and
custom settings in :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']`.


Migration
=========

Check the TCA of :sql:`pages_language_overlay` and remove l10n_mode for those fields
that previously were not defined in :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']`
and thus should not be overlaid.

.. index:: Frontend, TCA, LocalConfiguration
