.. include:: ../../Includes.txt

===========================================================================
Breaking: #82639 - Logging activated for authentication and Service classes
===========================================================================

See :issue:`82639`

Description
===========

Due to the introduction of TYPO3's Logging API in several places, it is now common to use the logging
API without further options.

Therefore the following configuration options have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLogBE']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLogFE']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']`

The following public properties have no effect anymore and have been removed:

- :php:`AbstractUserAuthentication->writeDevLog`
- :php:`AbstractService->writeDevLog`


Impact
======

Setting any of the options does not have any effect anymore on logging.


Affected Installations
======================

Installations running with `EXT:devlog` or further extensions setting any of the options above.


Migration
=========

Instead of using the mentioned options, TYPO3's Logging API can be configured as stated in the
official documentation to write the logging messages to various places.

.. index:: LocalConfiguration, PHP-API, FullyScanned
