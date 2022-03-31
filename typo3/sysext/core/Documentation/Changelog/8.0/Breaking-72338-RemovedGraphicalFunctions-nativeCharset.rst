
.. include:: /Includes.rst.txt

============================================================
Breaking: #72338 - Removed GraphicalFunctions->nativeCharset
============================================================

See :issue:`72338`

Description
===========

The property `nativeCharset` to allow GifBuilder to use other character-sets than UTF-8 for rendering text
for has been removed. The default behaviour is now to always consider multi-byte strings via CharsetConverter,
as the data is expected to be UTF-8 at all times.

Additionally the methods `recodeString()` and `singleChars()` have been removed as the direct equivalent from
CharsetConverter is used.


Impact
======

Calling any of the two methods above directly in PHP will result in a fatal error.

Setting `$nativeCharset` to something else than UTF-8 will have no effect anymore.


Affected Installations
======================

Installations with custom setups and third-party PHP code using GifBuilder or GraphicalFunctions and the `$nativeCharset` option.


Migration
=========

Use `CharsetConverter->utf8_to_numberarray()` instead of the method `singleChars()`.

.. index:: PHP-API
