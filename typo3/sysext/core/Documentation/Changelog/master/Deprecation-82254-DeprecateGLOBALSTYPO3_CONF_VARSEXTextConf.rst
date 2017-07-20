.. include:: ../../Includes.txt

=============================================================================
Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']
=============================================================================

See :issue:`82254`

Description
===========

The extension configuration stored in $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'] has been deprecated and replaced by a plain array in $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'].


Affected Installations
======================

All extensions manually getting settings and unserializing them from $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'].


Migration
=========

Switch to the use of $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'] instead and remove all unserialize calls.

.. index:: LocalConfiguration, PHP-API, FullyScanned