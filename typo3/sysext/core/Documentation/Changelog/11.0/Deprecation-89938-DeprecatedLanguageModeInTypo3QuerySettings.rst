.. include:: /Includes.rst.txt

=========================================================
Deprecation: #89938 - Language mode in Typo3QuerySettings
=========================================================

See :issue:`89938`

Description
===========

The following methods have been marked as deprecated and will be removed in TYPO3 v12.

- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::setLanguageMode()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::getLanguageMode()`


Impact
======

Calling these methods will trigger a PHP :php:`E_USER_DEPRECATED` error.

Calling these methods as of TYPO3 v12 will result in a fatal error.


Affected Installations
======================

All installations that call the mentioned methods.


Migration
=========

The deprecated methods have been used in combination with the non consistent translation handling of
Extbase. As that handling mode disappeared, there is no need to migrate these method calls and just
stop calling those instead.

For more information regarding this change, see issue :issue:`87264`

.. index:: PHP-API, FullyScanned, ext:extbase
