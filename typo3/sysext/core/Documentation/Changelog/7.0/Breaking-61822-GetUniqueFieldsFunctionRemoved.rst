================================================================
Breaking: #61822 - deprecated function getUniqueFields() removed
================================================================

Description
===========

The function :php:`getUniqueFields()` from :php:`\TYPO3\CMS\Core\DataHandling\DataHandler` has been removed.
The function is available in :php:`\TYPO3\CMS\Version\Hook\DataHandlerHook`.

Impact
======

Extensions that still use the function :php:`getUniqueFields()` won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed function.


Migration
=========

Replace all calls to :php:`\TYPO3\CMS\Core\DataHandling\DataHandler::getUniqueFields()`
with calls to :php:`\TYPO3\CMS\Version\Hook\DataHandlerHook::getUniqueFields()`