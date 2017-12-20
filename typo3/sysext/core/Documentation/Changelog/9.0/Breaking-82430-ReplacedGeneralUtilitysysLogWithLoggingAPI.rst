.. include:: ../../Includes.txt

===================================================================
Breaking: #82430 - Replaced GeneralUtility::sysLog with Logging API
===================================================================

See :issue:`82430`

Description
===========

The original sysLog() logging API has been superseded by the Logging API.

Therefore, :php:`GeneralUtility::sysLog` and :php:`GeneralUtility::initSysLog` have been deprecated.

The configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLog']` has been changed to a boolean value.
The option :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['syslogErrorReporting']` has been removed.

Impact
======

The Logging API needs custom writer configuration to send the log entries of your choice to the
PHP error log, the syslog facility or a file.


Affected Installations
======================

Any instance having a configuration set for :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLog']`.


Migration
=========

Add a custom log writer configuration to send log entries to the destination of your choice.

The Logging API provides these writers as replacements for the original configuration options:

- :php:`\TYPO3\CMS\Core\Log\Writer\SyslogWriter`
- :php:`\TYPO3\CMS\Core\Log\Writer\PhpErrorLogWriter`
- :php:`\TYPO3\CMS\Core\Log\Writer\FileWriter`

More details on the configuration of log writers can be found in the Core API Reference
at `<https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/Logging/Writers/Index.html>`__.

.. index:: LocalConfiguration, PHP-API, NotScanned
