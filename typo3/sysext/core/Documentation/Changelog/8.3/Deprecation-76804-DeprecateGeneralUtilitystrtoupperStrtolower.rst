
.. include:: ../../Includes.txt

=======================================================================
Deprecation: #76804 - Deprecate GeneralUtility::strtoupper & strtolower
=======================================================================

See :issue:`76804`

Description
===========

The following methods within `GeneralUtility` have been marked as deprecated:

* `strtoupper()`
* `strtolower()`


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any installation with a 3rd party extension calling one of the methods in its PHP code.


Migration
=========

Instead of :php:`GeneralUtility::strtoupper($value)` use:

.. code-block:: php

    $charsetConverter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
    $charsetConverter->conv_case('utf-8', $value, 'toUpper');

Instead of :php:`GeneralUtility::strtolower($value)` use:

.. code-block:: php

    $charsetConverter = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
    $charsetConverter->conv_case('utf-8', $value, 'toLower');

Alternatively use the native implementation of :php:`strtoupper($value)` or :php:`strtolower($value)`
if the handled string consists of ascii characters only and has no multi-byte characters like umlauts.

.. index:: PHP-API