.. include:: /Includes.rst.txt

=================================================================================
Important: #85116 - Changed visibility of CharsetConverter initialization methods
=================================================================================

See :issue:`85116`

Description
===========

Initialization methods within TYPO3's :php:`CharsetConverter` class have changed visibility.

The methods's purpose is to initialize conversion done with public API methods, and have previously
been marked as private already.

The following methods visibility have been changed from :php:`public` to :php:`protected`:

- :php:`CharsetConverter::initCharset()`
- :php:`CharsetConverter::initUnicodeData()`
- :php:`CharsetConverter::initCaseFolding()`
- :php:`CharsetConverter::initToASCII()`

.. index:: PHP-API
