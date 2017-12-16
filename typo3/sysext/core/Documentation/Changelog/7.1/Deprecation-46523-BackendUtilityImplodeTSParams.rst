
.. include:: ../../Includes.txt

=======================================================
Deprecation: #46523 - BackendUtility::implodeTSParams()
=======================================================

See :issue:`46523`

Description
===========

The method `TYPO3\CMS\Backend\Utility\BackendUtility::implodeTSParams()` has been marked as deprecated and will be
removed in TYPO3 CMS 8.


Impact
======

Any installation with third-party extensions using this method will throw a deprecation warning.


Affected installations
======================

Any installation with third-party extensions using the method.
