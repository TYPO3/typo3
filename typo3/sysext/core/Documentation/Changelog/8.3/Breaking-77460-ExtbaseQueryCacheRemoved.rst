
.. include:: /Includes.rst.txt

==============================================
Breaking: #77460 - Extbase query cache removed
==============================================

See :issue:`77460`

Description
===========

The PHP-based query cache functionality within the Extbase persistence layer has been removed.

The following public methods within the Extbase persistence layer have been removed:

* :php:`Typo3DbBackend->quoteTextValueCallback()`
* :php:`Typo3DbBackend->initializeObject()`
* :php:`Typo3DbBackend->injectCacheManager()`
* Interface definition in :php:`QuerySettingsInterface->getUseQueryCache()`

The TypoScript configuration :typoscript:`config.tx_extbase.persistence.useQueryCache` has no effect anymore.

Impact
======

The according cache configuration set via :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][cache][cacheConfigurations][extbase_typo3dbbackend_queries]` has no effect anymore.


Affected Installations
======================

Any installation effectively relying on the query cache via a third party extension or explicitly deactivating the query cache of extbase.


Migration
=========

Remove the according lines and migrate to Doctrine.

.. index:: Database, PHP-API, LocalConfiguration, ext:extbase
