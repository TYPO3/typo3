
.. include:: ../../Includes.txt

=============================================================
Breaking: #61785 - loadTCA function in GeneralUtility removed
=============================================================

See :issue:`61785`

Description
===========

Method :code:`loadTCA()` from :code:`\TYPO3\CMS\Core\Utility\GeneralUtility` is removed.

Impact
======

Extensions that still use :code:`\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA()` will trigger a fatal error.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension still calls :code:`loadTCA`.


Migration
=========

The method is obsolete, full TCA is always loaded in all context except eID.
It is safe to remove the method call.

