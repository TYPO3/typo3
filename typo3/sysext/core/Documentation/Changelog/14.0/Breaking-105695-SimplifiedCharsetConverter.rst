..  include:: /Includes.rst.txt

..  _breaking-105695-1732540153:

===============================================
Breaking: #105695 - Simplified CharsetConverter
===============================================

See :issue:`105695`

Description
===========

The following methods have been removed:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->conv()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_encode()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_decode()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->specCharsToASCII()`, use
  :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char_mapping()` instead
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->sb_char_mapping()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->euc_char_mapping()`

This removes most helper methods that implemented conversions between different
charsets from the core codebase: The vast majority of web sites is based
on utf-8 nowadays and needs no expensive conversion provided as core
framework functionality anymore.


Impact
======

Calling one of the above methods will raise fatal PHP errors.


Affected installations
======================

The core does not surface any of this removed low level functionality in upper
layers like TypoScript for a while already. The removed methods should have little
to no impact to casual instances. The only use cases that *may* be affected are
probably import and export extensions that had to convert between nowadays rather
obscure character sets like those of the EUC family. Affected extensions could
mitigate the removal by copying the TYPO3 v13 version of class :php:`CharsetConverter`
including affected files from :file:`core/Resources/Private/Charsets/csconvtbl/` to
their own codebase. The extension scanner will find usages and classify as weak match.


Migration
=========

Avoid calling above methods. Extensions that still need above functionality should
copy consumed functionality to their own codebase, or use some third party library.

This detail has a direct substitution:

.. code-block:: php

    // Before
    $charsetConverter->specCharsToASCII('utf-8', $myString);
    // After
    $charsetConverter->utf8_char_mapping($myString);


..  index:: PHP-API, FullyScanned, ext:core
