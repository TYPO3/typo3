.. include:: /Includes.rst.txt

=========================================================
Deprecation: #78670 - Deprecated CharsetConverter methods
=========================================================

See :issue:`78670`

Description
===========

The `symfony/polyfill-mbstring` package provides us with `mb_string` functionality in all installations.
Therefore some methods of :php:`CharsetConverter` have been marked as deprecated, since the equivalent `mb_string` functions can be used directly:

- :php:`strlen()`: use :php:`mb_strlen()` directly
- :php:`substr()`: use :php:`mb_substr()` directly
- :php:`strtrunc()`: use :php:`mb_strcut()` directly
- :php:`convCapitalize()`: use :php:`mb_convert_case()` directly
- :php:`conv_case()`: use :php:`mb_strtolower()` or :php:`mb_strtoupper()` directly
- :php:`utf8_substr()`: use :php:`mb_substr()` directly
- :php:`utf8_strlen()`: use :php:`mb_strlen()` directly
- :php:`utf8_strtrunc()`: use :php:`mb_strcut()` directly
- :php:`utf8_strpos()`: use :php:`mb_strpos()` directly
- :php:`utf8_strrpos()`: use :php:`mb_strrpos()` directly
- :php:`utf8_byte2char_pos()`: no replacement
- :php:`euc_strtrunc()`: use :php:`mb_strcut()` directly
- :php:`euc_substr()`: use :php:`mb_substr()` directly
- :php:`euc_strlen()`: use :php:`mb_strlen()` directly
- :php:`euc_char2byte_pos()`: no replacement
- :php:`$fourByteSets`: no replacement

Impact
======

Calling the deprecated :php:`CharsetConverter` methods will trigger a deprecation log entry.


Affected Installations
======================

Any installation using third party extensions leveraging the mentioned :php:`CharsetConverter` functionality.


Migration
=========

Use the equivalent mb_string methods directly as denoted above.

.. index:: PHP-API
