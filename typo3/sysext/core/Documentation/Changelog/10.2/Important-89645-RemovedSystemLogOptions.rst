.. include:: /Includes.rst.txt

=============================================
Important: #89645 - Removed systemLog options
=============================================

See :issue:`89645`

Description
===========

The systemLog API has been changed in TYPO3 v9.0 to use the Logging API as a breaking change. The relevant systemLog
options have been kept in TYPO3 v9 for backwards-compatibility of existing extensions, however have no use in TYPO3 v10
anymore.

The affected options are:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLog']`

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel']`

Impact
======

The options have been removed from the TYPO3's default configuration. When the options have been set, they are
automatically removed in TYPO3 v10.0 when accessing the Install Tool or System Maintenance area.

For extension authors, the Logging API should be used starting with TYPO3 v9. The usage of the systemLog options
should then be removed from the extensions' code.

.. index:: LocalConfiguration, FullyScanned, ext:core
