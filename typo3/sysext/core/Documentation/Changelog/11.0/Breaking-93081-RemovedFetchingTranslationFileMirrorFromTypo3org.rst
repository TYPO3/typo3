.. include:: /Includes.rst.txt

==========================================================================
Breaking: #93081 - Removed fetching translation file mirror from typo3.org
==========================================================================

See :issue:`93081`

Description
===========

The process of downloading translation of XLF files has been simplified.
The URL `https://localize.typo3.org/xliff/` is always used instead of download a static XML
file from typo3.org and persisting the URL in the registry.


Impact
======

The URL `https://localize.typo3.org/xliff/` is always used and typo3.org is not contacted anymore.

If any extension has overridden the information in the registry, this path won't be taken into account anymore.


Affected Installations
======================

Any TYPO3 installation which uses a different URL to fetch translations of TYPO3 core or any extension.


Migration
=========

Use the existing event :php:`ModifyLanguagePackRemoteBaseUrlEvent` to change the URL used to fetch translations.

.. index:: Backend, Frontend, NotScanned, ext:install
