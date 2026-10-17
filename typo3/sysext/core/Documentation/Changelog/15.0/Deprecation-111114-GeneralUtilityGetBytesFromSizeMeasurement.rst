..  include:: /Includes.rst.txt

..  _deprecation-111114-1792241570:

====================================================================
Deprecation: #111114 - GeneralUtility::getBytesFromSizeMeasurement()
====================================================================

See :issue:`111114`

Description
===========

The method
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement()`
has been marked as deprecated and will be removed in TYPO3 v16.0.

The method converted shorthand byte values such as `100k`, `128M` or `2G`,
as used in :file:`php.ini`, into bytes. PHP provides the native function
:php:`ini_parse_quantity()` for exactly this purpose since PHP 8.2. It
follows the same rules PHP itself applies when reading settings like
`memory_limit`, `upload_max_filesize` or `post_max_size`, including
hexadecimal and octal notation, and returns `-1` for unlimited values.
TYPO3 Core now uses the native function everywhere.

In addition, using decimal values like `1.5M` for the options `minimum` and
`maximum` of the file size validators
:php:`\TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator` and
:php:`\TYPO3\CMS\Form\Mvc\Validation\FileSizeValidator` (validator
identifier `FileSize` in EXT:form) has been marked as deprecated. PHP does
not support decimal values in shorthand byte notation either.

Impact
======

Calling the method triggers a PHP deprecation warning. The method continues
to work as before until it is removed in TYPO3 v16.0.

Configuring a file size validator with a decimal value triggers a PHP
deprecation warning when the validator is executed. The value is still
interpreted as before. Starting with TYPO3 v16.0, decimal values will be
rejected as invalid validator options.

Affected installations
======================

TYPO3 installations with custom extensions or code that directly call
:php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement()`
are affected. The extension scanner reports any usage as a **strong match**.

TYPO3 installations using decimal values for the `minimum` or `maximum`
option of a file size validator, for example in Extbase :php:`#[Validate]`
attributes or in EXT:form form definitions, are affected. The extension
scanner does not detect these values.

Migration
=========

Use PHP's native function :php:`ini_parse_quantity()` instead.

..  code-block:: php
    :caption: Before (deprecated)

    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $memoryLimit = GeneralUtility::getBytesFromSizeMeasurement(ini_get('memory_limit'));

..  code-block:: php
    :caption: After (recommended)

    $memoryLimit = ini_parse_quantity(ini_get('memory_limit'));

Note that :php:`ini_parse_quantity()` emits a PHP warning for values it
cannot fully interpret, such as decimal values (`1.5M`), unknown suffixes
(`100B`, `1T`) or values exceeding the integer range, whereas the deprecated
method silently interpreted them. Values that are not
:file:`php.ini` settings, for example sizes entered by editors or
integrators, should be validated before they are passed to the function.

For file size validators, replace decimal values with a whole number of a
smaller unit, for example `1536K` instead of `1.5M`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/MyForm.form.yaml

    validators:
      -
        identifier: FileSize
        options:
          minimum: 0B
          # Before: maximum: 1.5M
          maximum: 1536K

..  index:: PHP-API, PartiallyScanned, ext:core, ext:extbase, ext:form
