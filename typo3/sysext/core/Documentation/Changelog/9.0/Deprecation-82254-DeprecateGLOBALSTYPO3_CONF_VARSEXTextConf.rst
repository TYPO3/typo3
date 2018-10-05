.. include:: ../../Includes.txt

=============================================================================
Deprecation: #82254 - Deprecate $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']
=============================================================================

See :issue:`82254`

Description
===========

The extension configuration stored in :php:`$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']` has been
deprecated and replaced by a plain array in :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']`. A new
API has been introduced to retrieve extension configuration.


Affected Installations
======================

All extensions manually getting settings and unserializing them
from :php:`$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']`.


Migration
=========

Use a new API to retrieve extension configuration, examples:

.. code-block:: php

    // Retrieve a single key
    $backendFavicon = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('backend', 'backendFavicon');

    // Retrieve whole configuration
    $backendConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('backend');

    // Fully qualified class names for usage in ext_localconf.php / ext_tables.php
    $backendConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get('backend');



.. index:: LocalConfiguration, PHP-API, FullyScanned
