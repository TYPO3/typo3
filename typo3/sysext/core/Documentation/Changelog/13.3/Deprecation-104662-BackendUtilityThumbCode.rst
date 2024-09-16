.. include:: /Includes.rst.txt

.. _deprecation-104662-1724058079:

===============================================
Deprecation: #104662 - BackendUtility thumbCode
===============================================

See :issue:`104662`

Description
===========

The method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::thumbCode()` has been deprecated since the
method is no longer used in TYPO3 anymore. Additionally, due to multiple changes
to file processing over the years, e.g. introducing of FAL, the method's
signature changed a couple of times leading to a couple of method arguments
are being unused, which is quite a bad API.

Impact
======

Calling the PHP method :php:`BackendUtility::thumbCode()` will
trigger a PHP deprecation warning.

Affected installations
======================

TYPO3 installations with custom extensions using this method. The extension
scanner will report any usage as strong match.


Migration
=========

Remove any usage of this method. In case you currently rely on the
functionality, you can copy it to your custom extension. However, you might
want to consider refactoring the corresponding code places.

The method basically resolved given :php-short:`\TYPO3\CMS\Core\Resource\FileReference` objects. In case
a file could not be resolved, a special icon has been rendered. Otherwise,
the cropping configuration has been applied and the file's :php:`process()`
has been called to get the thumbnail, which has been wrapped in corresponding
thumbnail markup. This might has been extended to also open the information
modal on click.

This means the relevant parts are:

.. code-block:: php

    // Get file references
    $fileReferences = BackendUtility:resolveFileReferences($table, $field, $row);

    // Check for existence of the file
    $fileReference->getOriginalFile()->isMissing()

    // Render special icon if missing
    $iconFactory
        ->getIcon('mimetypes-other-other', IconSize::MEDIUM, 'overlay-missing')
        ->setTitle(static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing') . ' ' . $fileObject->getName())
        ->render()

    // Process file with cropping configuration if not missing
    $fileReference->getOriginalFile()->process(
        ProcessedFile::CONTEXT_IMAGEPREVIEW,// ProcessedFile::CONTEXT_IMAGECROPSCALEMASK if cropArea is defiend
        [
            'width' => '...',
            'height' => '...',
            'crop' // If cropArea is defined
        ]
    )

    // Use cropped file and create <img> tag
    <img src="' . $fileReference->getOriginalFile()->process()->getPublicUrl() . '"/>

    // Wrap the info popup via <a> around the thumbnail
    <a href="#" data-dispatch-action="TYPO3.InfoWindow.showItem" data-dispatch-args-list="_FILE,' . (int)$fileReference->getOriginalFile()->getUid() . '">


Example of the HTML markup for a thumbnail:

.. code-block:: html

    <div class="preview-thumbnails" style="--preview-thumbnails-size: 64px">
        <div class="preview-thumbnails-element">
            <div class="preview-thumbnails-element-image">
                <img src="' . $fileReference->getOriginalFile()->process()->getPublicUrl() . '" width="64px" height="64px" alt="' . $fileReference->getAlternative() ?: $fileReference->getName()  . '" loading="lazy"/>
            </div>
        </div>
    </div>


.. index:: PHP-API, FullyScanned, ext:backend
