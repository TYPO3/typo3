.. include:: /Includes.rst.txt

.. _important-85116:

=================================================================================
Important: #85116 - Changed visibility of CharsetConverter initialization methods
=================================================================================

See :issue:`85116`

Description
===========

Initialization methods within TYPO3's :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter` class have changed visibility.

The methods's purpose is to initialize conversion done with public API methods, and have previously
been marked as private already.

The following methods visibility have been changed from :php:`public` to :php:`protected`:

- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter::initCharset()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter::initUnicodeData()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter::initCaseFolding()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter::initToASCII()`

.. index:: PHP-API
