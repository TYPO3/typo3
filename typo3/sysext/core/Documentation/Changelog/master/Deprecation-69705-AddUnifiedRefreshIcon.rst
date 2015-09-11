==============================================
Deprecation: #69705 - Add unified refresh icon
==============================================

Description
===========

Icon ``actions-system-refresh`` has been deprecated in ``TYPO3\CMS\Core\Imaging\IconRegistry`` and will be removed with TYPO3 CMS 8.
All requests for ``actions-system-refresh`` will now show ``actions-refresh``.


Impact
======

Using IconUtility or IconFactory to fetch the icon ``actions-system-refresh`` logs a message to the deprecation log.


Affected Installations
======================

Installations with third party extensions that use the icon ``actions-system-refresh``.


Migration
=========

Use the icon ``actions-refresh`` instead.
