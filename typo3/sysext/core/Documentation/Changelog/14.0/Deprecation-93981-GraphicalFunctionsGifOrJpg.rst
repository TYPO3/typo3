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

Due to that, the method `gif_or_jpg()` of
:php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions` is no longer needed,
as fallback of image preview generation can now follow
configured file extensions, and not a hard-coded gif/jpeg
switch.

For this, the newly introduced method
:php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->determineDefaultProcessingFileExtension`
can be used, and just takes the file extension like "pdf" as argument,
and then returns the matching file extension for a possible preview
generation. This method is declared `@internal` for now as it is subject
to getting moved into its own service class.

Generally, third-party consumers should not need to determine the output format
on their own but use the regular image generation functionality as overlaying
API.

Deprecated method:

* :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->gif_or_jpg`


Impact
======

Calling this method raises deprecation level
log errors and will stop working in TYPO3 v15.0.


Affected installations
======================

Instances using the mentioned method directly.


Migration
=========

.. code-block:: php

    // Before
    $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
    $filetype = $graphicalFunctions->gif_or_jpg('pdf', 800, 600);
    // Returned: 'jpg'

    // After
    $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
    $filetype = $graphicalFunctions->determineDefaultProcessingFileExtension('pdf');
    // Returns: 'jpg' (for example, now depends on configuration!)

This is a temporary migration with an `@internal` annotated method, which is
subject to change. Code like the above should be avoided in third-party consumers,
and directly use `GraphicalFunctions->resize()` with the argument `$targetFileExtension`
set to `web` so that actual operations are perfomed with configured target formats.

..  index:: TCA, FullyScanned, ext:core
