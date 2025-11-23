..  include:: /Includes.rst.txt

..  _breaking-105695-1732540153:

===============================================
Breaking: #105695 - Simplified CharsetConverter
===============================================

See :issue:`105695`

Description
===========

The following methods have been removed from
:php:`\TYPO3\CMS\Core\Charset\CharsetConverter`:

* :php:`CharsetConverter->conv()`
* :php:`CharsetConverter->utf8_encode()`
* :php:`CharsetConverter->utf8_decode()`
* :php:`CharsetConverter->specCharsToASCII()`, use
  :php:`CharsetConverter->utf8_char_mapping()` instead
* :php:`CharsetConverter->sb_char_mapping()`
* :php:`CharsetConverter->euc_char_mapping()`

This removes most helper methods that implemented conversions between different
character sets from the TYPO3 Core. The vast majority of websites now use UTF-8
and no longer require the expensive charset conversions previously provided by
the Core framework.

Impact
======

Calling any of the removed methods will trigger a fatal PHP error.

Affected installations
======================

The TYPO3 Core has not exposed any of this low-level functionality in upper
layers such as TypoScript for quite some time. The removal should therefore
have little to no impact on most installations.

The only cases that *may* be affected are import or export extensions that
perform conversions between legacy character sets (for example, those in the
EUC family). Affected extensions can mitigate this change by copying the
TYPO3 v13 version of the class
:php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter`, including the relevant
files from :file:`core/Resources/Private/Charsets/csconvtbl/`, into their own
codebase.

The extension scanner will detect usages and classify them as weak matches.

Migration
=========

Avoid calling any of the removed methods. Extensions that still require this
functionality should copy the necessary logic into their own codebase or use a
third-party library.

This particular case has a direct substitution:

..  code-block:: php

    // Before
    $charsetConverter->specCharsToASCII('utf-8', $myString);

    // After
    $charsetConverter->utf8_char_mapping($myString);

..  index:: PHP-API, FullyScanned, ext:core
