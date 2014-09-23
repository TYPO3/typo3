==============================================================
Breaking: #61822 - deprecated getUniqueFields function removed
==============================================================

Description
===========

The getUniqueFields function from \TYPO3\CMS\Core\DataHandling\DataHandler is removed.
The function is available in \TYPO3\CMS\Version\Hook\DataHandlerHook

Impact
======

Extensions that still use the function getUniqueFields won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed function.


Migration
=========

Replace all calls to \TYPO3\CMS\Core\DataHandling\DataHandler::getUniqueFields
with calls to \TYPO3\CMS\Version\Hook\DataHandlerHook::getUniqueFields