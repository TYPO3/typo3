.. include:: ../../Includes.txt

====================================================================
Deprecation: #89938 - Deprecated language mode in Typo3QuerySettings
====================================================================

See :issue:`89938`

Description
===========

The following methods have been deprecated and will be removed in TYPO3 12.0.

- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::setLanguageMode`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::getLanguageMode`


Impact
======

Calling these methods will trigger a deprecation warning with TYPO3 11.0.

Calling these methods as of 12.0 will result in a fatal error.


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
