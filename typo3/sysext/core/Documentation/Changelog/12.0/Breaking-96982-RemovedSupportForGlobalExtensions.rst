.. include:: /Includes.rst.txt

.. _breaking-96982:

========================================================
Breaking: #96982 - Removed support for global extensions
========================================================

See :issue:`96982`

Description
===========

Historically, TYPO3 installations could load extensions from :file:`typo3/ext/`
where developers and site administrators could share extensions through
multiple installations on the same server via symlinks.

This feature was possible by enabling
:php:`$GLOBALS[TYPO3_CONF_VARS][EXT][allowGlobalInstall]` which was disabled
by default since TYPO3 4.0, as using this feature had several downsides with
Non-Composer based installations. Features such as "Automatic Updates" are
not possible having this functionality enabled.

In Composer-based installations, this functionality was never supported in
a proper way.

This functionality including the feature toggle have been removed in TYPO3 v12.0.

Impact
======

Extensions within the folder :file:`typo3/ext/` will be ignored in TYPO3 v12.0
and will be automatically disabled.

The global option to enable this feature will be removed from
:file:`LocalConfiguration.php` automatically once the Install Tool / Maintenance module
is loaded the next time, if the option is activated.

Affected Installations
======================

TYPO3 installations having the global option enabled, and have loaded extensions
in :file:`typo3/ext/`, which is unlikely in 2022.

Migration
=========

It is recommended to either migrate to Composer Mode, or to use symlinks
into :file:`typo3conf/ext/` (Local Extensions) to load the same extension for
multiple TYPO3 installations at once.

.. index:: PHP-API, FullyScanned, ext:core
