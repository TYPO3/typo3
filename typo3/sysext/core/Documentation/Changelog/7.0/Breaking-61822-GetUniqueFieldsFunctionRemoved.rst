
.. include:: ../../Includes.txt

================================================================
Breaking: #61822 - deprecated function getUniqueFields() removed
================================================================

See :issue:`61822`

Description
===========

The function :code:`getUniqueFields()` from :code:`\TYPO3\CMS\Core\DataHandling\DataHandler` has been removed.
The function is available in :code:`\TYPO3\CMS\Version\Hook\DataHandlerHook`.

Impact
======

Extensions that still use the function :code:`getUniqueFields()` won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed function.


Migration
=========

Replace all calls to :code:`\TYPO3\CMS\Core\DataHandling\DataHandler::getUniqueFields()`
with calls to :code:`\TYPO3\CMS\Version\Hook\DataHandlerHook::getUniqueFields()`
