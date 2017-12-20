
.. include:: ../../Includes.txt

==================================================
Deprecation: #66065 - Backend Logo View Deprecated
==================================================

See :issue:`66065`

Description
===========

The logo view class responsible for the rendering of the TYPO3 Logo in the left corner of the backend is not in use
anymore and marked for deprecation. The logic for exchanging the logo via TBE_STYLES is still available.


Impact
======

Installations extending `TYPO3\CMS\Backend\View\LogoView` as an XCLASS will not see
any modified output anymore.


Affected Installations
======================

Installations extending `TYPO3\CMS\Backend\View\LogoView` as an XCLASS.


Migration
=========

As the same logic is now done in the BackendController and the main Backend Fluid Template, the template can be
modified to fit the installations' needs.


.. index:: PHP-API, Backend
