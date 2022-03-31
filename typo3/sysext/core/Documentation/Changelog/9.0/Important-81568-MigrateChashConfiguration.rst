.. include:: /Includes.rst.txt

===============================================
Important: #81568 - Migrate cHash configuration
===============================================

See :issue:`81568`

Description
===========

All cHash-related configuration options have been previously migrated on every TYPO3 request
into an array-structured form.

The following cHash-related configuration entries below have been migrated:

- :php:`$TYPO3_CONF_VARS['FE']['cHashExcludedParameters']` is now an array instead of a
  comma-separated list, and migrated to :php:`$TYPO3_CONF_VARS['FE']['cacheHash']['excludedParameters']`

- :php:`$TYPO3_CONF_VARS['FE']['cHashOnlyForParameters']` is now an array instead of a
  comma-separated list, and migrated to :php:`$TYPO3_CONF_VARS['FE']['cacheHash']['cachedParametersWhiteList']`

- :php:`$TYPO3_CONF_VARS['FE']['cHashRequiredParameters']` is now an array instead of a
  comma-separated list, and migrated to :php:`$TYPO3_CONF_VARS['FE']['cacheHash']['requireCacheHashPresenceParameters']`

- :php:`$TYPO3_CONF_VARS['FE']['cHashExcludedParametersIfEmpty']`

  * If the old value was ``*``, the following parameter is now set to true to the
    option :php:`$TYPO3_CONF_VARS['FE']['cacheHash']['excludeAllEmptyParameters']`

  * If the old values were a comma-separated list, they are now migrated as an array to
    :php:`$TYPO3_CONF_VARS['FE']['cacheHash']['excludedParametersIfEmpty']`

These values are now migrated as a "silent upgrade wizard" via the Install Tool to the format
that TYPO3 uses internally since several versions.

Impact
======

Any values set in :file:`LocalConfiguration.php` are migrated automatically. If any of the options were
overridden in :file:`AdditionalConfiguration.php` these need to be adapted manually.

Changes within extensions are not affected.

.. index:: LocalConfiguration, Frontend
