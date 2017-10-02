.. include:: ../../Includes.txt

=========================================================
Deprecation: #52694 - Deprecated GeneralUtility::devLog()
=========================================================

See :issue:`52694`

Description
===========

The PHP method :php:`TYPO3\CMS\Core\Utility\GeneralUtility::devLog()` has been deprecated in favour of the Logging API.

Additionally these PHP symbols have been deprecated as well:

- :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_*` constants
- :php:`TYPO3\CMS\Core\Service\AbstractService::devLog()`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog']`

.. index:: LocalConfiguration, PHP-API, NotScanned
