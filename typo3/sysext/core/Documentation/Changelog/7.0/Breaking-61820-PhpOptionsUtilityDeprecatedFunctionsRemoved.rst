
.. include:: ../../Includes.txt

=================================================================
Breaking: #61820 - deprecated PhpOptionsUtility functions removed
=================================================================

See :issue:`61820`

Description
===========

The :code:`\TYPO3\CMS\Core\Utility\PhpOptionsUtility` functions isSafeModeEnabled and isMagicQuotesGpcEnabled are removed.

Impact
======

Extensions that still use one of the removed functions won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses one of the removed functions.


Migration
=========

Remove the call to :code:`\TYPO3\CMS\Core\Utility\PhpOptionsUtility::isSafeModeEnabled()`
or :code:`\TYPO3\CMS\Core\Utility\PhpOptionsUtility::isMagicQuotesGpcEnabled()` functions.
The Install Tool takes care of the removed checks now.


.. index:: PHP-API
