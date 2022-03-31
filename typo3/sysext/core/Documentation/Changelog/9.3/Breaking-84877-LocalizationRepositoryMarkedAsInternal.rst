.. include:: /Includes.rst.txt

============================================================
Breaking: #84877 - LocalizationRepository marked as internal
============================================================

See :issue:`84877`

Description
===========

The class :php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository` has been marked as internal, as
it's supposed to be used within the TYPO3 Core only.


Impact
======

The class :php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository` may change its behavior at any
point.


Affected Installations
======================

Every 3rd party extension using :php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository` is affected.

.. index:: Backend, NotScanned, ext:backend
