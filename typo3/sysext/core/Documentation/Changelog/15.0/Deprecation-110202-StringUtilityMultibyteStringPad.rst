..  include:: /Includes.rst.txt

..  _deprecation-110202-1784146595:

===================================================================
Deprecation: #110202 - StringUtility::multibyteStringPad() method
===================================================================

See :issue:`110202`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\StringUtility::multibyteStringPad()`
has been marked as deprecated and will be removed in TYPO3 v16.0.

The method was introduced to provide a multibyte-safe variant of PHP's
:php:`str_pad()`. Since PHP 8.3, the native function :php:`mb_str_pad()`
covers exactly this use case, making the TYPO3 wrapper obsolete.

Impact
======

Calling the method will trigger a PHP deprecation warning. It will continue
to work as before until it is removed in TYPO3 v16.0.

Affected installations
======================

TYPO3 installations with custom extensions or code that directly call
:php:`StringUtility::multibyteStringPad()` are affected.

The extension scanner will report any usage as a **strong match**.

Migration
=========

Use the native PHP function :php:`mb_str_pad()` instead.

Note that :php:`mb_str_pad()` throws a :php:`\ValueError` when an empty pad
string is passed, whereas :php:`StringUtility::multibyteStringPad()` returned
the input unchanged. If an empty pad string can occur, guard against it.

..  code-block:: php
    :caption: Before (deprecated)

    use TYPO3\CMS\Core\Utility\StringUtility;

    $padded = StringUtility::multibyteStringPad($string, 10, $padString, STR_PAD_LEFT);

..  code-block:: php
    :caption: After (recommended)

    $padded = $padString === ''
        ? $string
        : mb_str_pad($string, 10, $padString, STR_PAD_LEFT);

..  index:: PHP-API, FullyScanned, ext:core
