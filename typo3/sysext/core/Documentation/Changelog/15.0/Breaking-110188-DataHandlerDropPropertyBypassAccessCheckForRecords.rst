..  include:: /Includes.rst.txt

..  _breaking-110188-1783851114:

==========================================================================
Breaking: #110188 - DataHandler: Drop property bypassAccessCheckForRecords
==========================================================================

See :issue:`110188`

Description
===========

The property :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->bypassAccessCheckForRecords` has been removed.


Impact
======

Using :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->bypassAccessCheckForRecords`  will raise a PHP warning or a fatal error.
There will be no effect on access checks anymore.


Affected installations
======================

All installations using :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->bypassAccessCheckForRecords`.
This could be in DataHandler hooks or in custom DataHandler calls.


Migration
=========

Use a proper BackendUserAuthentication with sufficient access rights in :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->start()` instead for custom DataHandler calls.
Check for :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->BE_USER` rights in DataHandler hooks.

..  index:: PHP-API, FullyScanned, ext:core
