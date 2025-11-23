..  include:: /Includes.rst.txt

..  _deprecation-93981-1751961645:

====================================================
Deprecation: #93981 - GraphicalFunctions->gif_or_jpg
====================================================

See :issue:`93981`

Description
===========

Default image formats can now be configured thanks to
:issue:`93981` (see :ref:`feature-93981-1734803053`).

As a result, the method :php:`gif_or_jpg()` of
:php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions` is no longer needed.

Fallback behavior for image preview generation now follows the configured
file extensions rather than a hardcoded GIF/JPEG switch.

A new method,
:php:`GraphicalFunctions->determineDefaultProcessingFileExtension()`,
has been introduced.

It accepts a file extension such as :php:`'pdf'` as an argument and returns
the corresponding default output extension for preview generation.
This method is currently marked as :php:`@internal`, as it may later be moved
to a dedicated service class.

In general, third-party extensions should not determine the output format
manually but rely on TYPO3’s built-in image generation APIs.

Deprecated method:

*   :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->gif_or_jpg()`

Impact
======

Calling this method will trigger a deprecation-level log entry and will stop
working in TYPO3 v15.0.

Affected installations
======================

Instances that directly use the deprecated method.

Migration
=========

..  code-block:: php

    use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);

    // Before
    $filetype = $graphicalFunctions->gif_or_jpg('pdf', 800, 600);
    // Returned: 'jpg'

    // After
    $filetype = $graphicalFunctions->determineDefaultProcessingFileExtension('pdf');
    // Returns: 'jpg' (for example, now depends on configuration!)

This is a temporary migration using an :php:`@internal` method, which is subject
to change. Code like the above should generally be avoided in third-party
extensions.

Instead, use :php:`GraphicalFunctions->resize()` and specify the argument
:php:`$targetFileExtension = 'web'` so that actual operations use the configured
target formats.

..  index:: TCA, FullyScanned, ext:core
