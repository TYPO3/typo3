.. include:: ../../Includes.txt

=========================================================
Breaking: #82852 - Exception is thrown on invalid charset
=========================================================

See :issue:`82852`

Description
===========

The method :php:`\TYPO3\CMS\Core\Charset\CharsetConverter::initCharset()` and
consequently all methods in this class calling that method now throw
an :php:`\TYPO3\CMS\Core\Charset\UnknownCharsetException` if an unknown
charset was provided.

The :php:`TypoScriptFrontendController` aka TSFE uses this to throw
a :php:`\RuntimeException` in case of an invalid :ts:`config.metaCharset`.
Before this resulted in a blank page instead.


Impact
======

Third party code directly using the :php:`CharsetConverter` class need to be aware of the new exception in case of an invalid charset.

Sites with an invalid :ts:`config.metaCharset` will now see a clear error message.


Migration
=========

Catch the :php:`UnknownCharsetException` of the :php:`CharsetConverter` if necessary.

Ensure that :ts:`config.metaCharset` is set to a known charset.

.. index:: Frontend, PHP-API, NotScanned